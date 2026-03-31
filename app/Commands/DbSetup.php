<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

class DbSetup extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'app:db-setup';
    protected $description = 'Create database (if missing) and run migrations';
    protected $usage       = 'app:db-setup [--migrate] [--seed]';

    protected $options = [
        '--migrate' => 'Run migrations after creating the database (default: yes)',
        '--seed'    => 'Run DatabaseSeeder after migrations (default: no)',
    ];

    public function run(array $params)
    {
        $host = (string) env('database.default.hostname', 'localhost');
        $user = (string) env('database.default.username', 'root');
        $pass = (string) env('database.default.password', '');
        $port = (int) env('database.default.port', 3306);
        $dbName = (string) env('database.default.database', 'nurse_ward');

        if ($dbName === '') {
            CLI::error('Missing database.default.database in .env');
            return;
        }

        CLI::write("Connecting to MySQL at {$host}:{$port} ...", 'yellow');
        $mysqli = null;
        $effectiveHost = $host;
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $mysqli = new \mysqli($host, $user, $pass, '', $port);
        } catch (\mysqli_sql_exception $e) {
            // On Linux, host "localhost" commonly attempts a UNIX socket connection.
            // If the socket path is missing/mismatched, retry with TCP.
            if ($host === 'localhost' && str_contains($e->getMessage(), 'No such file or directory')) {
                $tcpHost = '127.0.0.1';
                CLI::write("Socket connection failed; retrying via TCP at {$tcpHost}:{$port} ...", 'yellow');
                try {
                    $mysqli = new \mysqli($tcpHost, $user, $pass, '', $port);
                    $effectiveHost = $tcpHost;
                } catch (\mysqli_sql_exception $e2) {
                    CLI::error('Connection failed: ' . $e2->getMessage());
                    return;
                }
            } else {
                CLI::error('Connection failed: ' . $e->getMessage());
                return;
            }
        }

        // Check database existence first (then create only if missing)
        $dbNameForSql = $mysqli->real_escape_string($dbName);
        $existsResult = $mysqli->query("SHOW DATABASES LIKE '{$dbNameForSql}'");
        $exists = $existsResult && $existsResult->num_rows > 0;

        if ($exists) {
            CLI::write("Database exists: {$dbName}", 'green');
        } else {
            CLI::write("Database not found; creating: {$dbName}", 'yellow');
            $escapedDb = str_replace('`', '``', $dbName);
            $sql = "CREATE DATABASE `{$escapedDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
            if (! $mysqli->query($sql)) {
                CLI::error('Error creating database: ' . $mysqli->error);
                $mysqli->close();
                return;
            }
            CLI::write("Database created: {$dbName}", 'green');
        }
        $mysqli->close();

        // Ensure CI4 DB connection uses same host (avoid localhost socket on Linux)
        if ($effectiveHost !== $host) {
            putenv("database.default.hostname={$effectiveHost}");
            $_ENV['database.default.hostname'] = $effectiveHost;
            $_SERVER['database.default.hostname'] = $effectiveHost;
        }

        $migrate = true;
        $seed = false;

        foreach ($params as $p) {
            if ($p === '--migrate') {
                $migrate = true;
            }
            if ($p === '--seed') {
                $seed = true;
            }
        }

        if ($migrate) {
            CLI::write('Running migrations ...', 'yellow');
            $migrations = Services::migrations();
            $result = $migrations->latest();
            if ($result === false) {
                CLI::error('Migrations failed.');
                return;
            }
            CLI::write('Migrations complete.', 'green');
        }

        if ($seed) {
            CLI::write('Running DatabaseSeeder ...', 'yellow');
            $seeder = Services::seeder();
            $seeder->call('DatabaseSeeder');
            CLI::write('Seeding complete.', 'green');
        }

        CLI::write('Done.', 'green');
    }
}

