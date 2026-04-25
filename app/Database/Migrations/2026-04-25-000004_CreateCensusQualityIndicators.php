<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Quality Indicators recorded per shift per ward.
 * Linked to daily_census (one QI row per census shift record).
 *
 * HAI (Hospital-Acquired Infections): VAP, HAP, UTI, CAUTI, CLABSI, SSI, BSI, MDR
 * Special conditions: New Sepsis, End of Life, Palliative Care, Critical Care Support, High Flow O2
 */
class CreateCensusQualityIndicators extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'census_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            // ── Hospital-Acquired Infections (HAI) ─────────────────────────
            'hai_vap'    => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'comment' => 'Ventilator-Associated Pneumonia'],
            'hai_hap'    => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'comment' => 'Hospital-Acquired Pneumonia'],
            'hai_uti'    => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'comment' => 'Urinary Tract Infection'],
            'hai_cauti'  => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'comment' => 'Catheter-Associated UTI'],
            'hai_clabsi' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'comment' => 'Central Line-Associated BSI'],
            'hai_ssi'    => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'comment' => 'Surgical Site Infection'],
            'hai_bsi'    => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'comment' => 'Blood Stream Infection'],
            'hai_mdr'    => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'comment' => 'Multi-Drug Resistant organism'],

            // ── Special Conditions ──────────────────────────────────────────
            'new_sepsis'             => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
            'end_of_life'            => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
            'palliative_care'        => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
            'critical_care_support'  => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'comment' => 'รองรับผู้วิกฤต'],
            'high_flow_oxygen'       => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],

            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('census_id');
        $this->forge->addForeignKey('census_id', 'daily_census', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('census_quality_indicators');
    }

    public function down()
    {
        $this->forge->dropTable('census_quality_indicators', true);
    }
}
