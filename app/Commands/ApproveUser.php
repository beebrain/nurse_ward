<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ApproveUser extends BaseCommand
{
    protected $group       = 'Auth';
    protected $name        = 'auth:approve';
    protected $description = 'Approves a user by ID';
    protected $usage       = 'auth:approve [user_id]';
    protected $arguments   = ['user_id' => 'The user ID to approve'];

    public function run(array $params)
    {
        $userId = $params[0] ?? CLI::prompt('User ID');
        $db = \Config\Database::connect();
        $db->table('users')->where('id', $userId)->update(['approval_status' => 'approved']);
        CLI::write("User {$userId} approved.", 'green');
    }
}
