<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApiDiscrepancyReasons extends Migration
{
    public function up()
    {
        $this->forge->addColumn('daily_census', [
            'api_discrepancy_reasons' => [
                'type' => 'JSON',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('daily_census', 'api_discrepancy_reasons');
    }
}
