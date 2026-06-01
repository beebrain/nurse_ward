<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSourceApiWardNameToHourlyCensus extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('source_api_ward_name', 'hourly_patient_census')) {
            $this->forge->addColumn('hourly_patient_census', [
                'source_api_ward_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'default'    => '',
                    'after'      => 'record_time',
                ],
            ]);
        }

        $exists = $this->db->query(
            "SHOW INDEX FROM `hourly_patient_census` WHERE Key_name = 'hourly_census_ward_time_source'"
        )->getResultArray();

        if ($exists === []) {
            $this->forge->addUniqueKey(
                ['ward_id', 'record_time', 'source_api_ward_name'],
                'hourly_census_ward_time_source'
            );
        }
    }

    public function down()
    {
        $exists = $this->db->query(
            "SHOW INDEX FROM `hourly_patient_census` WHERE Key_name = 'hourly_census_ward_time_source'"
        )->getResultArray();
        if ($exists !== []) {
            $this->db->query('ALTER TABLE `hourly_patient_census` DROP INDEX `hourly_census_ward_time_source`');
        }

        if ($this->db->fieldExists('source_api_ward_name', 'hourly_patient_census')) {
            $this->forge->dropColumn('hourly_patient_census', 'source_api_ward_name');
        }
    }
}
