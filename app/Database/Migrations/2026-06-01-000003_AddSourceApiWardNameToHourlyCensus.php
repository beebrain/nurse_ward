<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSourceApiWardNameToHourlyCensus extends Migration
{
    public function up()
    {
        $this->forge->addColumn('hourly_patient_census', [
            'source_api_ward_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => '',
                'after'      => 'record_time',
            ],
        ]);

        // แยกบันทึกตามชื่อ API — รวมยอดตอนแสดงผล
        foreach (['ward_id', 'ward_id_record_time'] as $indexName) {
            $exists = $this->db->query(
                "SHOW INDEX FROM `hourly_patient_census` WHERE Key_name = " . $this->db->escape($indexName)
            )->getResultArray();
            if ($exists !== []) {
                $this->db->query("ALTER TABLE `hourly_patient_census` DROP INDEX `{$indexName}`");
            }
        }
        $this->forge->addUniqueKey(['ward_id', 'record_time', 'source_api_ward_name'], 'hourly_census_ward_time_source');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `hourly_patient_census` DROP INDEX `hourly_census_ward_time_source`');
        $this->forge->addKey(['ward_id', 'record_time']);
        $this->forge->dropColumn('hourly_patient_census', 'source_api_ward_name');
    }
}
