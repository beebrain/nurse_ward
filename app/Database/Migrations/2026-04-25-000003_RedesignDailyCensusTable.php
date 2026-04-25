<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Redesigns daily_census to capture:
 *  - 5-level patient classification (Level 5–1 combined ปกติ+พิเศษ)
 *  - 6 nurse types per shift (HW, RN, TN, PN, Aide, Ward)
 *  - Working hours, required care hours, productivity (auto-calculated)
 *  - Patient movements
 *
 * Productivity formula (from ward รพอุตรดิตถ์ Excel):
 *   required_care_hrs = (12×L5)+(7.5×L4)+(5.5×L3)+(3.5×L2)+(1.5×L1)  ← Afternoon shift
 *   working_hrs       = (nurses_rn + nurses_tn + nurses_pn across 3 shifts) × 7
 *   productivity      = (required_care_hrs × 100) / working_hrs
 */
class RedesignDailyCensusTable extends Migration
{
    public function up()
    {
        // Drop existing table (test data only — FK cascade handles daily_census refs)
        $this->forge->dropTable('daily_census', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ward_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'record_date' => ['type' => 'DATE'],
            'shift' => [
                'type'       => 'ENUM',
                'constraint' => ['Night', 'Morning', 'Afternoon'],
                'comment'    => 'ดึก / เช้า / บ่าย',
            ],

            // ── Patient levels (ปกติ + พิเศษ รวมกัน) ──────────────────────
            'patients_level_5' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'comment' => 'วิกฤต — 12h/patient',
            ],
            'patients_level_4' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'comment' => 'หนัก — 7.5h/patient',
            ],
            'patients_level_3' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'comment' => 'ปานกลาง — 5.5h/patient',
            ],
            'patients_level_2' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'comment' => 'น้อย — 3.5h/patient',
            ],
            'patients_level_1' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0,
                'comment' => 'ช่วยตัวเองได้ — 1.5h/patient',
            ],
            // computed = L5+L4+L3+L2+L1 (stored for query performance)
            'total_patients' => [
                'type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0,
            ],

            // ── Patient movements ───────────────────────────────────────────
            'admissions'    => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
            'discharges'    => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
            'transfers_in'  => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
            'transfers_out' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
            'deaths'        => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],

            // ── Nursing staff by type (per shift) ──────────────────────────
            'nurses_hw'   => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'comment' => 'หัวหน้าเวร'],
            'nurses_rn'   => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'comment' => 'พยาบาลวิชาชีพ'],
            'nurses_tn'   => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'comment' => 'พยาบาลเทคนิค'],
            'nurses_pn'   => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'comment' => 'เจ้าหน้าที่ช่วยพยาบาล'],
            'nurses_aide' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'comment' => 'ผู้ช่วยเหลือคนไข้'],
            'nurses_ward' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'comment' => 'พนักงานผู้ป่วย'],

            // ── Productivity (calculated & stored for Afternoon shift only) ─
            // working_hrs  = (RN+TN+PN all 3 shifts) × 7
            // care_hrs     = (12×L5)+(7.5×L4)+(5.5×L3)+(3.5×L2)+(1.5×L1) of THIS afternoon
            // productivity = (care_hrs × 100) / working_hrs
            'working_hours'       => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
            'required_care_hours' => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
            'productivity'        => ['type' => 'DECIMAL', 'constraint' => '8,4', 'null' => true],

            'notes' => ['type' => 'TEXT', 'null' => true],

            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['ward_id', 'record_date', 'shift']);
        $this->forge->addKey(['ward_id', 'record_date']);
        $this->forge->addForeignKey('ward_id', 'wards', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('daily_census');
    }

    public function down()
    {
        $this->forge->dropTable('daily_census', true);
    }
}
