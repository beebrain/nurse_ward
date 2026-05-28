<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHourlyPatientCensusTable extends Migration
{
    public function up()
    {
        // 1. Add api_ward_code and api_ward_name columns to wards table
        $this->forge->addColumn('wards', [
            'api_ward_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'code',
            ],
            'api_ward_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'api_ward_code',
            ],
        ]);

        // 2. Insert missing "อินทนิล" ward if it doesn't exist
        $now = date('Y-m-d H:i:s');
        $db = \Config\Database::connect();
        
        $inthanin = $db->table('wards')->where('name', 'หอผู้ป่วยพิเศษอินทนิล')->orWhere('name', 'อินทนิล')->get()->getRow();
        if (!$inthanin) {
            $db->table('wards')->insert([
                'name'          => 'หอผู้ป่วยพิเศษอินทนิล',
                'code'          => 'อินทนิล',
                'department_id' => 7, // กลุ่มงานอายุรกรรม
                'total_beds'    => 0,
                'is_active'     => 1,
                'created_at'    => $now,
            ]);
        }

        // 3. Update ward API mappings
        $mappings = [
            // id => [api_ward_code, api_ward_name]
            1  => ['09', 'ศัลยกรรมชาย'],
            2  => ['19', 'หออภิบาลผู้ป่วยวิกฤตอายุรกรรม'],
            3  => ['18', 'หออภิบาลผู้ป่วยวิกฤตศัลยกรรม'],
            4  => ['31', 'หออภิบาลผู้ป่วยวิกฤตโรคหัวใจ'],
            5  => ['75', 'ICU ระบบทางเดินหายใจ'],
            6  => ['40', 'หออภิบาลผู้ป่วยวิกฤตศัลยกรรมประสาท'],
            7  => ['38', 'หออภิบาลผู้ป่วยวิกฤตกุมารเวชกรรม'],
            8  => ['78', 'ICU ศัลยกรรมอุบัติเหตุ'],
            9  => ['53', 'หอผู้ป่วยโรคหลอดเลือดสมอง (Stroke unit)'],
            10 => ['80', 'หอผู้ป่วยระบบทางเดินหายใจ'],
            11 => ['21', 'ตา หู คอ จมูก'],
            13 => ['06', 'ศัลยกรรมกระดูกชาย'],
            14 => ['07', 'ศัลยกรรมกระดูกหญิง'],
            15 => ['77', 'พูนพิพัฒน์ 3'],
            16 => ['08', 'ศัลยกรรมหญิง'],
            17 => ['05', 'ศัลยกรรมอุบัติเหตุ'],
            18 => ['25', 'ศัลยกรรมประสาท'],
            19 => ['57', 'พูนพิพัฒน์ 4'],
            20 => ['10', 'กุมารเวชกรรม 2'],
            21 => ['11', 'กุมารเวชกรรม 3'],
            22 => ['12', 'สูติกรรมหลังคลอด'],
            23 => ['13', 'นรีเวชกรรม'],
            24 => ['59', 'พูนพิพัฒน์ 5'],
            25 => ['74', 'พูนพิพัฒน์ 2'],
            26 => ['81', 'หอแยกโรคผู้ป่วยติดเชื้อ'],
            27 => ['79', 'หอผู้ป่วยหัตถการโรคหัวใจ'],
            28 => ['03', 'อายุรกรรมหญิง 1'],
            29 => ['01', 'อายุรกรรมชาย 1'],
            31 => ['26', 'ห้องคลอด'],
            34 => ['02', 'อายุรกรรมชาย 2'],
            35 => ['04', 'อายุรกรรมหญิง 2'],
        ];

        foreach ($mappings as $id => [$code, $name]) {
            $db->table('wards')->where('id', $id)->update([
                'api_ward_code' => $code,
                'api_ward_name' => $name,
            ]);
        }

        // Also update the newly inserted "อินทนิล" ward's mapping
        $db->table('wards')->where('code', 'อินทนิล')->update([
            'api_ward_code' => '76',
            'api_ward_name' => 'อินทนิล',
        ]);

        // 4. Create hourly_patient_census table
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
            'record_time' => [
                'type' => 'DATETIME',
            ],
            'patient_count' => [
                'type'       => 'INT',
                'constraint' => 6,
                'default'    => 0,
            ],
            'admissions_today' => [
                'type'       => 'INT',
                'constraint' => 6,
                'default'    => 0,
            ],
            'discharges_today' => [
                'type'       => 'INT',
                'constraint' => 6,
                'default'    => 0,
            ],
            'moves_in_today' => [
                'type'       => 'INT',
                'constraint' => 6,
                'default'    => 0,
            ],
            'moves_out_today' => [
                'type'       => 'INT',
                'constraint' => 6,
                'default'    => 0,
            ],
            'deaths_today' => [
                'type'       => 'INT',
                'constraint' => 6,
                'default'    => 0,
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
        $this->forge->addKey(['ward_id', 'record_time']); // composite index
        $this->forge->createTable('hourly_patient_census');

        // Add foreign key constraint
        $db->query('ALTER TABLE `hourly_patient_census` ADD CONSTRAINT `hourly_census_ward_id_foreign`
            FOREIGN KEY (`ward_id`) REFERENCES `wards`(`id`) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down()
    {
        $db = \Config\Database::connect();
        
        // Drop constraint and table
        $db->query('ALTER TABLE `hourly_patient_census` DROP FOREIGN KEY `hourly_census_ward_id_foreign`');
        $this->forge->dropTable('hourly_patient_census');

        // Remove mapping columns
        $this->forge->dropColumn('wards', ['api_ward_code', 'api_ward_name']);

        // Delete "อินทนิล"
        $db->table('wards')->where('code', 'อินทนิล')->delete();
    }
}
