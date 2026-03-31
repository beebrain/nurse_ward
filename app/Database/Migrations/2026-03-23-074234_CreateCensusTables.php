<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCensusTables extends Migration
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
            'ward_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'record_date' => [
                'type' => 'DATE',
            ],
            'shift' => [
                'type'       => 'ENUM',
                'constraint' => ['Morning', 'Afternoon', 'Night'],
            ],
            'admissions' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
            ],
            'discharges' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
            ],
            'transfers_in' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
            ],
            'transfers_out' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
            ],
            'deaths' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
            ],
            'total_remaining' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('ward_id', 'wards', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['ward_id', 'record_date', 'shift']);
        $this->forge->createTable('daily_census');
    }

    public function down()
    {
        $this->forge->dropTable('daily_census');
    }
}
