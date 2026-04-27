<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveDefaultEquipmentCountsFromWards extends Migration
{
    public function up()
    {
        foreach (['default_ventilator_count', 'default_hfnc_count'] as $column) {
            $this->forge->dropColumn('wards', $column);
        }
    }

    public function down()
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
}
