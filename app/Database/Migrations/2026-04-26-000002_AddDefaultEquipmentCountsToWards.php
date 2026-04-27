<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDefaultEquipmentCountsToWards extends Migration
{
    public function up()
    {
        $this->forge->addColumn('wards', [
            'default_ventilator_count' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'total_beds',
            ],
            'default_hfnc_count' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'default_ventilator_count',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('wards', [
            'default_ventilator_count',
            'default_hfnc_count',
        ]);
    }
}
