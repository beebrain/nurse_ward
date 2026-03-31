<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusToUsers extends Migration
{
    public function up()
    {
        $fields = [
            'approval_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'deactivated'],
                'default'    => 'pending',
                'after'      => 'username',
            ],
        ];

        $this->forge->addColumn('users', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'approval_status');
    }
}
