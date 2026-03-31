<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CreateWard extends BaseCommand
{
    protected $group       = 'Admin';
    protected $name        = 'admin:create-ward';
    protected $description = 'Creates a ward for testing';
    protected $usage       = 'admin:create-ward [name] [beds]';

    public function run(array $params)
    {
        $name = $params[0] ?? CLI::prompt('Ward Name');
        $beds = $params[1] ?? CLI::prompt('Total Beds');
        $db = \Config\Database::connect();
        $db->table('wards')->insert([
            'name'       => $name,
            'total_beds' => $beds,
            'is_active'  => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        CLI::write("Ward '{$name}' created.", 'green');
    }
}
