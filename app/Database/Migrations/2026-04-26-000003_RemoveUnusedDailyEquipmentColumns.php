<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveUnusedDailyEquipmentColumns extends Migration
{
    public function up()
    {
        foreach ([
            'equipment_infusion_pump',
            'equipment_syringe_pump',
            'equipment_patient_monitor',
            'equipment_oxygen_flowmeter',
        ] as $column) {
            $this->forge->dropColumn('daily_census', $column);
        }
    }

    public function down()
    {
        $fields = [];
        foreach ([
            'equipment_infusion_pump',
            'equipment_syringe_pump',
            'equipment_patient_monitor',
            'equipment_oxygen_flowmeter',
        ] as $column) {
            $fields[$column] = [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 0,
            ];
        }

        $this->forge->addColumn('daily_census', $fields);
    }
}
