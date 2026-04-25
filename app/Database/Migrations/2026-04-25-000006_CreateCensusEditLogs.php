<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCensusEditLogs extends Migration
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
            'census_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'edited_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'action' => [
                'type'       => 'ENUM',
                'constraint' => ['create', 'update'],
                'default'    => 'create',
            ],
            // JSON snapshot of the census row BEFORE this edit (null for create actions)
            'snapshot' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('census_id');
        $this->forge->addForeignKey('census_id', 'daily_census', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('census_edit_logs');
    }

    public function down()
    {
        $this->forge->dropTable('census_edit_logs', true);
    }
}
