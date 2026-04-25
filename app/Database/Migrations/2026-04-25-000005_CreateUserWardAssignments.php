<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserWardAssignments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'Shield users.id',
            ],
            'ward_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'ward_id']);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('ward_id', 'wards', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_ward_assignments');
    }

    public function down()
    {
        $this->forge->dropTable('user_ward_assignments', true);
    }
}
