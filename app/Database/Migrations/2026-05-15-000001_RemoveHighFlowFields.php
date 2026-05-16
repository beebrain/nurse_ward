<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveHighFlowFields extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('equipment_hfnc', 'daily_census')) {
            $this->forge->dropColumn('daily_census', 'equipment_hfnc');
        }

        if ($this->db->fieldExists('high_flow_oxygen', 'census_quality_indicators')) {
            $this->forge->dropColumn('census_quality_indicators', 'high_flow_oxygen');
        }
    }

    public function down()
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

        if (! $this->db->fieldExists('high_flow_oxygen', 'census_quality_indicators')) {
            $this->forge->addColumn('census_quality_indicators', [
                'high_flow_oxygen' => [
                    'type'       => 'TINYINT',
                    'constraint' => 3,
                    'unsigned'   => true,
                    'default'    => 0,
                    'after'      => 'critical_care_support',
                ],
            ]);
        }
    }
}
