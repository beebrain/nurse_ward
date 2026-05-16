<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RestoreEquipmentHfnc extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('equipment_hfnc', 'daily_census')) {
            $this->forge->addColumn('daily_census', [
                'equipment_hfnc' => [
                    'type'       => 'TINYINT',
                    'constraint' => 3,
                    'unsigned'   => true,
                    'default'    => 0,
                    'after'      => 'equipment_ventilator',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('equipment_hfnc', 'daily_census')) {
            $this->forge->dropColumn('daily_census', 'equipment_hfnc');
        }
    }
}
