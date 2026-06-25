<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHosxpNursingLevelMapTable extends Migration
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
            'hosxp_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'hosxp_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'ward_level' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
            ],
            'sort_order' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('hosxp_code');
        $this->forge->createTable('hosxp_nursing_level_map');

        $now = date('Y-m-d H:i:s');
        $rows = [];
        foreach ([5, 4, 3, 2, 1] as $level) {
            $rows[] = [
                'hosxp_code' => (string) $level,
                'hosxp_name' => 'Level ' . $level,
                'ward_level' => $level,
                'sort_order' => 6 - $level,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $rows[] = [
                'hosxp_code' => 'L' . $level,
                'hosxp_name' => 'L' . $level,
                'ward_level' => $level,
                'sort_order' => 6 - $level,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->db->table('hosxp_nursing_level_map')->insertBatch($rows);
    }

    public function down()
    {
        $this->forge->dropTable('hosxp_nursing_level_map');
    }
}
