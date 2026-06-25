<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHosxpLevelsToHourlyCensus extends Migration
{
    public function up()
    {
        $fields = [
            'has_level_data' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'patient_count',
            ],
            'patients_level_5' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'has_level_data',
            ],
            'patients_level_4' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'patients_level_5',
            ],
            'patients_level_3' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'patients_level_4',
            ],
            'patients_level_2' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'patients_level_3',
            ],
            'patients_level_1' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'patients_level_2',
            ],
        ];

        $this->forge->addColumn('hourly_patient_census', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('hourly_patient_census', [
            'has_level_data',
            'patients_level_5',
            'patients_level_4',
            'patients_level_3',
            'patients_level_2',
            'patients_level_1',
        ]);
    }
}
