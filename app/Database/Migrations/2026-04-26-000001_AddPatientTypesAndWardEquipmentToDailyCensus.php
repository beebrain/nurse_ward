<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPatientTypesAndWardEquipmentToDailyCensus extends Migration
{
    public function up()
    {
        $this->forge->addColumn('daily_census', [
            'patients_general_level_5' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'shift',
            ],
            'patients_general_level_4' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'patients_general_level_5',
            ],
            'patients_general_level_3' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'patients_general_level_4',
            ],
            'patients_general_level_2' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'patients_general_level_3',
            ],
            'patients_general_level_1' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'patients_general_level_2',
            ],
            'patients_special_level_5' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'patients_general_level_1',
            ],
            'patients_special_level_4' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'patients_special_level_5',
            ],
            'patients_special_level_3' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'patients_special_level_4',
            ],
            'patients_special_level_2' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'patients_special_level_3',
            ],
            'patients_special_level_1' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'patients_special_level_2',
            ],
            'equipment_ventilator' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'nurses_ward',
            ],
            'equipment_hfnc' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'equipment_ventilator',
            ],
            'equipment_infusion_pump' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'equipment_hfnc',
            ],
            'equipment_syringe_pump' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'equipment_infusion_pump',
            ],
            'equipment_patient_monitor' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'equipment_syringe_pump',
            ],
            'equipment_oxygen_flowmeter' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'after' => 'equipment_patient_monitor',
            ],
        ]);

        $this->db->query('
            UPDATE daily_census
            SET
                patients_general_level_5 = patients_level_5,
                patients_general_level_4 = patients_level_4,
                patients_general_level_3 = patients_level_3,
                patients_general_level_2 = patients_level_2,
                patients_general_level_1 = patients_level_1
        ');
    }

    public function down()
    {
        $this->forge->dropColumn('daily_census', [
            'patients_general_level_5',
            'patients_general_level_4',
            'patients_general_level_3',
            'patients_general_level_2',
            'patients_general_level_1',
            'patients_special_level_5',
            'patients_special_level_4',
            'patients_special_level_3',
            'patients_special_level_2',
            'patients_special_level_1',
            'equipment_ventilator',
            'equipment_hfnc',
            'equipment_infusion_pump',
            'equipment_syringe_pump',
            'equipment_patient_monitor',
            'equipment_oxygen_flowmeter',
        ]);
    }
}
