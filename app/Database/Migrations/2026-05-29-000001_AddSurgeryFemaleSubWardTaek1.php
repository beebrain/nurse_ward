<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * เพิ่มแผนกย่อย ศญ_แทรก1 (API ward 08) หากยังไม่มี — สำหรับที่ migrate แยก 3 แผนกไปแล้ว
 */
class AddSurgeryFemaleSubWardTaek1 extends Migration
{
    private const API_NAME = 'ศญ_แทรก1';

    public function up()
    {
        $db = \Config\Database::connect();

        $existing = $db->table('wards')
            ->where('api_ward_name', self::API_NAME)
            ->get()
            ->getRowArray();

        if ($existing) {
            return;
        }

        $db->table('wards')->insert([
            'name'          => 'หอผู้ป่วยศัลยกรรมหญิง (แทรก 1)',
            'code'          => 'ศญแทรก1',
            'department_id' => 3,
            'total_beds'    => 0,
            'is_active'     => 1,
            'api_ward_code' => '08',
            'api_ward_name' => self::API_NAME,
        ]);

        $newId = (int) $db->insertID();
        $this->copyUserWardFromWard16($db, $newId);
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $ward = $db->table('wards')->where('api_ward_name', self::API_NAME)->get()->getRowArray();
        if (! $ward) {
            return;
        }
        if ($db->tableExists('user_wards')) {
            $db->table('user_wards')->where('ward_id', $ward['id'])->delete();
        }
        $db->table('hourly_patient_census')->where('ward_id', $ward['id'])->delete();
        $db->table('wards')->where('id', $ward['id'])->delete();
    }

    private function copyUserWardFromWard16($db, int $newWardId): void
    {
        if (! $db->tableExists('user_wards')) {
            return;
        }

        $assignments = $db->table('user_wards')->where('ward_id', 16)->get()->getResultArray();
        foreach ($assignments as $assignment) {
            $exists = $db->table('user_wards')
                ->where('user_id', $assignment['user_id'])
                ->where('ward_id', $newWardId)
                ->countAllResults();
            if ($exists > 0) {
                continue;
            }
            $db->table('user_wards')->insert([
                'user_id' => $assignment['user_id'],
                'ward_id' => $newWardId,
            ]);
        }
    }
}
