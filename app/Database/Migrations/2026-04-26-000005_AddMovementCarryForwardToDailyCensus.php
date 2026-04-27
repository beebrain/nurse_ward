<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMovementCarryForwardToDailyCensus extends Migration
{
    public function up()
    {
        $this->forge->addColumn('daily_census', [
            'carried_forward_patients' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'total_patients',
            ],
            'movement_expected_patients' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'carried_forward_patients',
            ],
            'movement_variance' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'default'    => 0,
                'after'      => 'movement_expected_patients',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('daily_census', [
            'carried_forward_patients',
            'movement_expected_patients',
            'movement_variance',
        ]);
    }
}
