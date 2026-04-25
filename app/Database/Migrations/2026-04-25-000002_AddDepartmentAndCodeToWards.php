<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDepartmentAndCodeToWards extends Migration
{
    public function up()
    {
        // Extend name field to 200 chars to hold full Thai ward names
        $this->forge->modifyColumn('wards', [
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'name'       => 'name',
            ],
        ]);

        // Add code (short abbreviation: ER, MICU, ศช., etc.)
        $this->forge->addColumn('wards', [
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'name',
            ],
        ]);

        // Add department_id FK (nullable so existing rows don't break)
        $this->forge->addColumn('wards', [
            'department_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'code',
            ],
        ]);

        $this->db->query('ALTER TABLE `wards` ADD CONSTRAINT `wards_department_id_foreign`
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL ON UPDATE CASCADE');

        // Map existing wards to departments and update codes + names
        // Source: ward รพอุตรดิตถ์.xls
        $mappings = [
            // [current_id, new_name, code, department_id]
            [1,  'หอผู้ป่วยศัลยกรรมชาย',                        'ศช.',       3],
            [2,  'หออภิบาลผู้ป่วยวิกฤตอายุรกรรม',               'MICU',      6],
            [3,  'หออภิบาลผู้ป่วยวิกฤตศัลยกรรม',                'ICU Surg.', 6],
            [4,  'หออภิบาลผู้ป่วยวิกฤตโรคหัวใจ',                'CCU',       6],
            [5,  'หออภิบาลผู้ป่วยวิกฤตระบบทางเดินหายใจ',        'RICU',      6],
            [6,  'หออภิบาลผู้ป่วยวิกฤตศัลยกรรมประสาท',          'ICU Neuro', 6],
            [7,  'หออภิบาลผู้ป่วยวิกฤตกุมารเวชกรรม',            'NICU',      6],
            [8,  'หออภิบาลผู้ป่วยวิกฤตศัลยกรรมอุบัติเหตุ',      'ICU Trauma',6],
            [9,  'หอผู้ป่วยโรคหลอดเลือดสมอง',                   'Stroke Unit',7],
            [10, 'หอผู้ป่วยระบบทางเดินหายใจ',                   'RCW',       7],
            [11, 'หอผู้ป่วยตา หู คอ จมูก',                      'EENT',      9],
        ];

        foreach ($mappings as [$id, $name, $code, $deptId]) {
            $this->db->table('wards')->where('id', $id)->update([
                'name'          => $name,
                'code'          => $code,
                'department_id' => $deptId,
            ]);
        }

        // Insert remaining wards from XLS that don't exist yet
        $now  = date('Y-m-d H:i:s');
        $newWards = [
            // [name, code, department_id, total_beds]
            ['งานอุบัติเหตุและฉุกเฉิน',                        'ER',          1, 0],
            ['หอผู้ป่วยศัลยกรรมกระดูกชาย',                    'ศกช.',        2, 0],
            ['หอผู้ป่วยศัลยกรรมกระดูกหญิง',                   'ศกญ.',        2, 0],
            ['หอผู้ป่วยพิเศษ 1 ชั้น 3 (ออร์โธปิดิกส์)',       'พิเศษ 1/3',   2, 0],
            ['หอผู้ป่วยศัลยกรรมหญิง',                          'ศญ.',         3, 0],
            ['หอผู้ป่วยศัลยกรรมอุบัติเหตุ',                   'ศอ.',         3, 0],
            ['หอผู้ป่วยศัลยกรรมประสาท',                        'ศป.',         3, 0],
            ['หอผู้ป่วยพิเศษ Premium 1 ชั้น 4 (ศัลยกรรม)',    'พิเศษP1/4',   3, 0],
            ['หอผู้ป่วยกุมารเวชกรรม 2',                        'เด็ก 2',      4, 0],
            ['หอผู้ป่วยกุมารเวชกรรม 3',                        'เด็ก 3',      4, 0],
            ['หอผู้ป่วยสูติกรรมหลังคลอด',                      'PP',          5, 0],
            ['หอผู้ป่วยนรีเวชกรรม',                            'Gyne',        5, 0],
            ['หอผู้ป่วยพิเศษ Premium 1 ชั้น 5 (สูติ-นรีเวช)', 'พิเศษP1/5',   5, 0],
            ['หอผู้ป่วยพิเศษ 1 ชั้น 2 (อายุรกรรม)',           'พิเศษ 1/2',   7, 0],
            ['หอแยกโรคผู้ป่วยติดเชื้อ',                        'แยกโรค',      7, 0],
            ['พิเศษอายุรกรรมชั้น 4 และหัตถการหัวใจ',           'พิเศษอาย/4',  7, 0],
            ['หอผู้ป่วยอายุรกรรมหญิง',                         'อญ.',         7, 0],
            ['หอผู้ป่วยอายุรกรรมชาย',                          'อช.',         7, 0],
            ['หอผู้ป่วยจิตเวช',                                 'จิตเวช',      8, 0],
            ['งานห้องคลอด',                                     'LR',          10, 0],
        ];

        foreach ($newWards as [$name, $code, $deptId, $beds]) {
            $this->db->table('wards')->insert([
                'name'          => $name,
                'code'          => $code,
                'department_id' => $deptId,
                'total_beds'    => $beds,
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => null,
                'deleted_at'    => null,
            ]);
        }
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `wards` DROP FOREIGN KEY `wards_department_id_foreign`');
        $this->forge->dropColumn('wards', ['department_id', 'code']);
        $this->forge->modifyColumn('wards', [
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'name'       => 'name',
            ],
        ]);
    }
}
