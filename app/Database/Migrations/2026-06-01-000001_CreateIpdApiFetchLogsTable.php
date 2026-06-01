<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIpdApiFetchLogsTable extends Migration
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
            'fetched_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'record_time' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'success' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'wards_saved' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'patient_total' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'payload_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('record_time');
        $this->forge->addKey('fetched_at');
        $this->forge->createTable('ipd_api_fetch_logs');
    }

    public function down()
    {
        $this->forge->dropTable('ipd_api_fetch_logs');
    }
}
