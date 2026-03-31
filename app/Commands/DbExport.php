<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DbExport extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'app:db-export';
    protected $description = 'Export database to SQL file using mysqldump (from .env)';
    protected $usage       = 'app:db-export [path/to/file.sql] [--data-only] [--schema-only]';

    protected $options = [
        '--data-only'   => 'Dump data only (no CREATE TABLE)',
        '--schema-only' => 'Dump schema only (no INSERT)',
    ];

    public function run(array $params)
    {
        $host   = (string) env('database.default.hostname', 'localhost');
        $user   = (string) env('database.default.username', 'root');
        $pass   = (string) env('database.default.password', '');
        $port   = (int) env('database.default.port', 3306);
        $dbName = (string) env('database.default.database', 'nurse_ward');

        if ($dbName === '') {
            CLI::error('Missing database.default.database in .env');
            return;
        }

        $dataOnly   = in_array('--data-only', $params, true);
        $schemaOnly = in_array('--schema-only', $params, true);
        if ($dataOnly && $schemaOnly) {
            CLI::error('Use only one of --data-only or --schema-only');
            return;
        }

        $outPath = null;
        foreach ($params as $p) {
            if ($p !== '--data-only' && $p !== '--schema-only' && $p !== '' && $p[0] !== '-') {
                $outPath = $p;
                break;
            }
        }

        if ($outPath === null) {
            $dir = WRITEPATH . 'exports';
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $outPath = $dir . DIRECTORY_SEPARATOR . 'nurse_ward_' . date('Y-m-d_His') . '.sql';
        }

        $cnfPath = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mysqldump_' . uniqid('', true) . '.cnf';
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

        $mysqldump = $this->findMysqldump();
        if ($mysqldump === null) {
            @unlink($cnfPath);
            CLI::error('mysqldump not found in PATH. Install MariaDB/MySQL client tools or add to PATH.');
            return;
        }

        $cmd = escapeshellarg($mysqldump)
            . ' --defaults-extra-file=' . escapeshellarg($cnfPath)
            . ' --single-transaction --routines --default-character-set=utf8mb4';
        if ($dataOnly) {
            $cmd .= ' --no-create-info';
        }
        if ($schemaOnly) {
            $cmd .= ' --no-data';
        }
        $cmd .= ' ' . escapeshellarg($dbName);
        CLI::write('Exporting ' . $dbName . ' ...', 'yellow');

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($cmd, $descriptors, $pipes, null, null);
        if (! is_resource($process)) {
            @unlink($cnfPath);
            CLI::error('Could not start mysqldump');
            return;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);
        @unlink($cnfPath);

        if ($code !== 0) {
            CLI::error('mysqldump failed (exit ' . $code . '): ' . trim($stderr ?: $stdout));
            return;
        }

        if (file_put_contents($outPath, $stdout) === false) {
            CLI::error('Could not write: ' . $outPath);
            return;
        }

        CLI::write('Wrote: ' . realpath($outPath), 'green');
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

    private function findMysqldump(): ?string
    {
        $candidates = ['mysqldump'];
        if (DIRECTORY_SEPARATOR === '\\') {
            $candidates[] = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        }

        foreach ($candidates as $bin) {
            if ($bin === 'mysqldump' || $bin === 'mysqldump.exe') {
                $out = [];
                $ret = 1;
                @exec('where mysqldump 2>NUL', $out, $ret);
                if ($ret === 0 && ! empty($out[0]) && is_file($out[0])) {
                    return $out[0];
                }
                if (PHP_OS_FAMILY !== 'Windows') {
                    $which = shell_exec('command -v mysqldump 2>/dev/null');
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
