<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DbImport extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'app:db-import';
    protected $description = 'Import SQL file into database (from .env)';
    protected $usage       = 'app:db-import path/to/dump.sql';

    public function run(array $params)
    {
        $sqlFile = $params[0] ?? null;
        if ($sqlFile === null || $sqlFile === '' || str_starts_with($sqlFile, '-')) {
            CLI::error('Usage: php spark app:db-import path/to/dump.sql');
            return;
        }

        if (! is_file($sqlFile)) {
            CLI::error('File not found: ' . $sqlFile);
            return;
        }

        $host   = (string) env('database.default.hostname', 'localhost');
        $user   = (string) env('database.default.username', 'root');
        $pass   = (string) env('database.default.password', '');
        $port   = (int) env('database.default.port', 3306);
        $dbName = (string) env('database.default.database', 'nurse_ward');

        if ($dbName === '') {
            CLI::error('Missing database.default.database in .env');
            return;
        }

        $mysql = $this->findMysql();
        if ($mysql === null) {
            CLI::error('mysql client not found in PATH. Install MariaDB/MySQL client tools.');
            return;
        }

        $cnfPath = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mysqlimport_' . uniqid('', true) . '.cnf';
        $ini     = "[client]\n"
            . 'host=' . $this->iniValue($host) . "\n"
            . 'user=' . $this->iniValue($user) . "\n"
            . 'password=' . $this->iniPassword($pass) . "\n"
            . 'port=' . $port . "\n"
            . "default-character-set=utf8mb4\n";
        if (! is_dir(dirname($cnfPath))) {
            mkdir(dirname($cnfPath), 0755, true);
        }
        file_put_contents($cnfPath, $ini);
        @chmod($cnfPath, 0600);

        CLI::write('Importing into ' . $dbName . ' ...', 'yellow');

        $code = $this->runMysqlStdin($mysql, $cnfPath, $dbName, $sqlFile);

        @unlink($cnfPath);

        if ($code !== 0) {
            CLI::error('Import failed (exit ' . $code . ')');
            return;
        }

        CLI::write('Import complete.', 'green');
    }

    private function runMysqlStdin(string $mysql, string $cnfPath, string $dbName, string $sqlFile): int
    {
        $cmd = escapeshellarg($mysql)
            . ' --defaults-extra-file=' . escapeshellarg($cnfPath)
            . ' ' . escapeshellarg($dbName);
        $descriptors = [
            0 => ['file', realpath($sqlFile) ?: $sqlFile, 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($cmd, $descriptors, $pipes);
        if (! is_resource($process)) {
            return 1;
        }
        stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);
        if ($code !== 0 && $err !== '') {
            CLI::error(trim($err));
        }

        return $code;
    }

    private function iniValue(string $v): string
    {
        return str_replace(["\r", "\n"], '', $v);
    }

    private function iniPassword(string $pass): string
    {
        $pass = str_replace(["\r", "\n"], '', $pass);
        if (preg_match('/[#;"\s]/', $pass)) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $pass) . '"';
        }

        return $pass;
    }

    private function findMysql(): ?string
    {
        $candidates = ['mysql'];
        if (DIRECTORY_SEPARATOR === '\\') {
            $candidates[] = 'C:\\xampp\\mysql\\bin\\mysql.exe';
        }

        foreach ($candidates as $bin) {
            if ($bin === 'mysql' || $bin === 'mysql.exe') {
                $out = [];
                $ret = 1;
                @exec('where mysql 2>NUL', $out, $ret);
                if ($ret === 0 && ! empty($out[0]) && is_file($out[0])) {
                    return $out[0];
                }
                if (PHP_OS_FAMILY !== 'Windows') {
                    $which = shell_exec('command -v mysql 2>/dev/null');
                    if ($which !== null && $which !== '') {
                        return trim($which);
                    }
                }
            } elseif (is_file($bin)) {
                return $bin;
            }
        }

        return null;
    }
}
