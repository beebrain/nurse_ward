<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWardApiAliasesTable extends Migration
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
            'api_ward_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'api_ward_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('ward_id');
        $this->forge->addUniqueKey('api_ward_name');
        $this->forge->addForeignKey('ward_id', 'wards', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ward_api_aliases');

        // ชื่อหลักบน wards ถือเป็น mapping แรก — ไม่ซ้ำใน aliases
        $db = \Config\Database::connect();
        if ($db->tableExists('wards')) {
            $wards = $db->table('wards')
                ->select('id, api_ward_code, api_ward_name')
                ->where('api_ward_name IS NOT NULL')
                ->where('api_ward_name !=', '')
                ->get()
                ->getResultArray();
            // ไม่ seed ลง aliases — เก็บเฉพาะชื่อเพิ่มเติม
        }
    }

    public function down()
    {
        $this->forge->dropTable('ward_api_aliases', true);
    }
}
