<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDepartmentsTable extends Migration
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'short_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 3,
                'default'    => 0,
            ],
            'is_active' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('departments');

        // Seed departments from ward รพอุตรดิตถ์.xls
        $now = date('Y-m-d H:i:s');
        $departments = [
            [1, 'กลุ่มงานการพยาบาลผู้ป่วยอุบัติเหตุและฉุกเฉิน', 'อุบัติเหตุและฉุกเฉิน', 1, 1, $now, $now],
            [2, 'กลุ่มงานการพยาบาลผู้ป่วยออร์โธปิดิกส์', 'ออร์โธปิดิกส์', 2, 1, $now, $now],
            [3, 'กลุ่มงานการพยาบาลผู้ป่วยศัลยกรรม', 'ศัลยกรรม', 3, 1, $now, $now],
            [4, 'กลุ่มงานการพยาบาลผู้ป่วยกุมารเวชกรรม', 'กุมารเวชกรรม', 4, 1, $now, $now],
            [5, 'กลุ่มงานการพยาบาลผู้ป่วยสูติ-นรีเวช', 'สูติ-นรีเวช', 5, 1, $now, $now],
            [6, 'กลุ่มงานการพยาบาลผู้ป่วยหนัก', 'ผู้ป่วยหนัก', 6, 1, $now, $now],
            [7, 'กลุ่มงานการพยาบาลผู้ป่วยอายุรกรรม', 'อายุรกรรม', 7, 1, $now, $now],
            [8, 'กลุ่มงานการพยาบาลจิตเวช', 'จิตเวช', 8, 1, $now, $now],
            [9, 'กลุ่มงานการพยาบาลผู้ป่วยโสต ศอ นาสิก จักษุ', 'โสต ศอ นาสิก จักษุ', 9, 1, $now, $now],
            [10, 'กลุ่มงานการพยาบาลผู้คลอด', 'ผู้คลอด', 10, 1, $now, $now],
        ];

        $this->db->table('departments')->insertBatch(
            array_map(fn($r) => [
                'id'         => $r[0],
                'name'       => $r[1],
                'short_name' => $r[2],
                'sort_order' => $r[3],
                'is_active'  => $r[4],
                'created_at' => $r[5],
                'updated_at' => $r[6],
            ], $departments)
        );
    }

    public function down()
    {
        $this->forge->dropTable('departments');
    }
}
