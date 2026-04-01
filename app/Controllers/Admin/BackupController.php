<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class BackupController extends BaseController
{
    private string $backupDir;

    public function __construct()
    {
        $this->backupDir = WRITEPATH . 'backups/';

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    // ─── INDEX ────────────────────────────────────────────────────────────────

    public function index()
    {
        $files = $this->getBackupFiles();

        return view('admin/backup/index', [
            'title'   => 'สำรองข้อมูล (Database Backup)',
            'files'   => $files,
            'dirSize' => $this->dirSize(),
        ]);
    }

    // ─── CREATE BACKUP ────────────────────────────────────────────────────────

    /**
     * Generate SQL dump and save to writable/backups/, then return download.
     * POST admin/backup/create
     */
    public function create()
    {
        $label    = preg_replace('/[^a-zA-Z0-9_\-]/', '', $this->request->getPost('label') ?? '');
        $label    = $label !== '' ? '_' . $label : '';
        $filename = 'backup_' . date('Y-m-d_H-i-s') . $label . '.sql';
        $filepath = $this->backupDir . $filename;

        try {
            $sql = $this->generateDump();
            file_put_contents($filepath, $sql);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'สำรองข้อมูลไม่สำเร็จ: ' . $e->getMessage());
        }

        return redirect()->to('admin/backup')
            ->with('message', 'สำรองข้อมูลสำเร็จ: ' . $filename);
    }

    // ─── DOWNLOAD ─────────────────────────────────────────────────────────────

    /**
     * Stream a saved backup file as download.
     * GET admin/backup/download?file=filename.sql
     */
    public function download()
    {
        $filename = basename($this->request->getGet('file') ?? '');
        $filepath = $this->backupDir . $filename;

        if (!$filename || !file_exists($filepath) || !str_ends_with($filename, '.sql')) {
            return redirect()->back()->with('error', 'ไม่พบไฟล์ที่ต้องการดาวน์โหลด');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/octet-stream')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Content-Length', (string) filesize($filepath))
            ->setHeader('Cache-Control', 'no-store')
            ->setBody(file_get_contents($filepath));
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    /**
     * Delete a saved backup file.
     * POST admin/backup/delete
     */
    public function delete()
    {
        $filename = basename($this->request->getPost('file') ?? '');
        $filepath = $this->backupDir . $filename;

        if (!$filename || !file_exists($filepath) || !str_ends_with($filename, '.sql')) {
            return redirect()->back()->with('error', 'ไม่พบไฟล์ที่ต้องการลบ');
        }

        unlink($filepath);

        return redirect()->back()->with('message', 'ลบไฟล์ "' . $filename . '" สำเร็จ');
    }

    // ─── IMPORT SQL ──────────────────────────────────────────────────────────

    /**
     * Upload and execute a .sql file against the current database.
     * POST admin/backup/import
     */
    public function importSql()
    {
        $file = $this->request->getFile('sql_file');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'กรุณาเลือกไฟล์ SQL ที่ถูกต้อง');
        }

        if (strtolower($file->getClientExtension()) !== 'sql') {
            return redirect()->back()->with('error', 'รองรับเฉพาะไฟล์ .sql เท่านั้น');
        }

        if ($file->getSize() > 50 * 1024 * 1024) {
            return redirect()->back()->with('error', 'ไฟล์ขนาดใหญ่เกิน 50 MB');
        }

        $tmpPath = WRITEPATH . 'uploads/' . $file->getRandomName();
        $file->move(WRITEPATH . 'uploads/', basename($tmpPath));

        $sql = file_get_contents($tmpPath);
        unlink($tmpPath);

        if ($sql === false || trim($sql) === '') {
            return redirect()->back()->with('error', 'ไม่สามารถอ่านไฟล์ได้หรือไฟล์ว่างเปล่า');
        }

        $db = \Config\Database::connect();

        // Split SQL into individual statements (handle delimiter ; safely)
        $statements = $this->splitSql($sql);

        $db->transStart();

        $executed = 0;
        $errors   = [];

        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') {
                continue;
            }

            try {
                $db->query($stmt);
                $executed++;
            } catch (\Throwable $e) {
                $errors[] = substr($stmt, 0, 80) . '… → ' . $e->getMessage();
                // Stop on first error to avoid corrupted state
                break;
            }
        }

        if (!empty($errors)) {
            $db->transRollback();
            return redirect()->back()
                ->with('error', 'นำเข้าไม่สำเร็จ (rollback แล้ว): ' . $errors[0]);
        }

        $db->transComplete();

        return redirect()->back()
            ->with('message', 'นำเข้า SQL สำเร็จ ' . $executed . ' statements');
    }

    /**
     * Split a full SQL dump into individual executable statements.
     * Handles multi-line INSERT, comments, and SET commands correctly.
     */
    private function splitSql(string $sql): array
    {
        // Strip BOM
        $sql = ltrim($sql, "\xEF\xBB\xBF");

        $statements = [];
        $current    = '';
        $inString   = false;
        $strChar    = '';
        $len        = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];

            // Skip single-line comments (-- ... \n)
            if (!$inString && $char === '-' && isset($sql[$i + 1]) && $sql[$i + 1] === '-') {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }

            // Skip block comments (/* ... */)
            if (!$inString && $char === '/' && isset($sql[$i + 1]) && $sql[$i + 1] === '*') {
                $i += 2;
                while ($i < $len - 1 && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                    $i++;
                }
                $i += 2;
                continue;
            }

            // Track string literals to avoid splitting on ; inside strings
            if (!$inString && ($char === "'" || $char === '"' || $char === '`')) {
                $inString = true;
                $strChar  = $char;
            } elseif ($inString && $char === $strChar) {
                // Handle escaped quote ('' or \')
                if (isset($sql[$i + 1]) && $sql[$i + 1] === $strChar) {
                    $current .= $char;
                    $i++;
                } elseif ($i > 0 && $sql[$i - 1] === '\\') {
                    // escaped with backslash — stay in string
                } else {
                    $inString = false;
                }
            }

            if (!$inString && $char === ';') {
                $statements[] = trim($current);
                $current      = '';
                continue;
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $statements[] = trim($current);
        }

        return $statements;
    }

    // ─── QUICK DOWNLOAD (backup + download in one step) ──────────────────────

    /**
     * Generate SQL dump and immediately stream to browser as download.
     * GET admin/backup/download-now
     */
    public function downloadNow()
    {
        $label    = preg_replace('/[^a-zA-Z0-9_\-]/', '', $this->request->getGet('label') ?? '');
        $label    = $label !== '' ? '_' . $label : '';
        $filename = 'backup_' . date('Y-m-d_H-i-s') . $label . '.sql';

        try {
            $sql = $this->generateDump();
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'สำรองข้อมูลไม่สำเร็จ: ' . $e->getMessage());
        }

        return $this->response
            ->setHeader('Content-Type', 'application/octet-stream')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Content-Length', (string) strlen($sql))
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($sql);
    }

    // ─── PURE-PHP SQL DUMP ────────────────────────────────────────────────────

    private function generateDump(): string
    {
        $db     = \Config\Database::connect();
        $dbName = $db->getDatabase();
        $tables = $db->listTables();

        $lines = [];
        $lines[] = '-- ==========================================================';
        $lines[] = '-- Nurse Ward Database Backup';
        $lines[] = '-- Database : ' . $dbName;
        $lines[] = '-- Generated: ' . date('Y-m-d H:i:s');
        $lines[] = '-- Generated by: ' . (auth()->user()->username ?? 'superadmin');
        $lines[] = '-- ==========================================================';
        $lines[] = '';
        $lines[] = 'SET FOREIGN_KEY_CHECKS = 0;';
        $lines[] = 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";';
        $lines[] = 'SET time_zone = "+07:00";';
        $lines[] = '';

        foreach ($tables as $table) {
            $lines[] = '-- ----------------------------------------------------------';
            $lines[] = '-- Table: `' . $table . '`';
            $lines[] = '-- ----------------------------------------------------------';
            $lines[] = '';

            // DROP + CREATE TABLE
            $createResult = $db->query('SHOW CREATE TABLE `' . $table . '`')->getRowArray();
            $createSql    = $createResult['Create Table'] ?? $createResult[array_key_last($createResult)];
            $lines[] = 'DROP TABLE IF EXISTS `' . $table . '`;';
            $lines[] = $createSql . ';';
            $lines[] = '';

            // Data rows
            $rows = $db->query('SELECT * FROM `' . $table . '`')->getResultArray();

            if (!empty($rows)) {
                $columns    = array_keys($rows[0]);
                $colList    = '`' . implode('`, `', $columns) . '`';
                $insertHead = 'INSERT INTO `' . $table . '` (' . $colList . ') VALUES';

                $chunks = array_chunk($rows, 100);
                foreach ($chunks as $chunk) {
                    $valueSets = [];
                    foreach ($chunk as $row) {
                        $vals = array_map(function ($v) use ($db) {
                            if ($v === null) {
                                return 'NULL';
                            }
                            return "'" . $db->escapeString((string) $v) . "'";
                        }, array_values($row));
                        $valueSets[] = '(' . implode(', ', $vals) . ')';
                    }
                    $lines[] = $insertHead;
                    $lines[] = implode(",\n", $valueSets) . ';';
                    $lines[] = '';
                }
            }

            $lines[] = '';
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
        $lines[] = '';
        $lines[] = '-- End of backup';

        return implode("\n", $lines);
    }

    // ─── HELPERS ──────────────────────────────────────────────────────────────

    private function getBackupFiles(): array
    {
        $files = glob($this->backupDir . '*.sql') ?: [];

        $result = [];
        foreach ($files as $path) {
            $result[] = [
                'name'     => basename($path),
                'size'     => filesize($path),
                'modified' => filemtime($path),
            ];
        }

        // Newest first
        usort($result, fn($a, $b) => $b['modified'] - $a['modified']);

        return $result;
    }

    private function dirSize(): int
    {
        $total = 0;
        foreach (glob($this->backupDir . '*.sql') ?: [] as $f) {
            $total += filesize($f);
        }
        return $total;
    }
}
