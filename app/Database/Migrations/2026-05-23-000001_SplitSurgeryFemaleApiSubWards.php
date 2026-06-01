<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * ศัลยกรรมหญิง (API ward 08) แยกย่อยตาม ward_name จาก API:
 * ศญ1_สามัญ, ศญ2_สามัญ, ศญ_พิเศษ, ศญ_แทรก1
 */
class SplitSurgeryFemaleApiSubWards extends Migration
{
    private const API_CODE = '08';

    private const SUB_WARDS = [
        [
            'id'             => 16,
            'name'           => 'หอผู้ป่วยศัลยกรรมหญิง 1 (สามัญ)',
            'code'           => 'ศญ1',
            'api_ward_name'  => 'ศญ1_สามัญ',
        ],
        [
            'name'          => 'หอผู้ป่วยศัลยกรรมหญิง 2 (สามัญ)',
            'code'          => 'ศญ2',
            'api_ward_name' => 'ศญ2_สามัญ',
        ],
        [
            'name'          => 'หอผู้ป่วยศัลยกรรมหญิง (พิเศษ)',
            'code'          => 'ศญพิเศษ',
            'api_ward_name' => 'ศญ_พิเศษ',
        ],
        [
            'name'          => 'หอผู้ป่วยศัลยกรรมหญิง (แทรก 1)',
            'code'          => 'ศญแทรก1',
            'api_ward_name' => 'ศญ_แทรก1',
        ],
    ];

    public function up()
    {
        $db = \Config\Database::connect();

        $createdIds = [];

        foreach (self::SUB_WARDS as $spec) {
            $row = [
                'name'           => $spec['name'],
                'code'           => $spec['code'],
                'department_id'  => 3,
                'total_beds'     => 0,
                'is_active'      => 1,
                'api_ward_code'  => self::API_CODE,
                'api_ward_name'  => $spec['api_ward_name'],
            ];

            if (! empty($spec['id'])) {
                $db->table('wards')->where('id', (int) $spec['id'])->update($row);
                $createdIds[] = (int) $spec['id'];
                continue;
            }

            $existing = $db->table('wards')
                ->where('api_ward_name', $spec['api_ward_name'])
                ->get()
                ->getRowArray();

            if ($existing) {
                $db->table('wards')->where('id', $existing['id'])->update($row);
                $createdIds[] = (int) $existing['id'];
                continue;
            }

            $db->table('wards')->insert($row);
            $createdIds[] = (int) $db->insertID();
        }

        $this->copyUserWardAssignments($db, $createdIds);
    }

    public function down()
    {
        $db = \Config\Database::connect();

        foreach (['ศญ2_สามัญ', 'ศญ_พิเศษ', 'ศญ_แทรก1'] as $apiName) {
            $ward = $db->table('wards')->where('api_ward_name', $apiName)->get()->getRowArray();
            if (! $ward) {
                continue;
            }
            if ($db->tableExists('user_wards')) {
                $db->table('user_wards')->where('ward_id', $ward['id'])->delete();
            }
            $db->table('hourly_patient_census')->where('ward_id', $ward['id'])->delete();
            $db->table('wards')->where('id', $ward['id'])->delete();
        }

        $db->table('wards')->where('id', 16)->update([
            'name'          => 'หอผู้ป่วยศัลยกรรมหญิง',
            'code'          => 'ศญ.',
            'api_ward_code' => self::API_CODE,
            'api_ward_name' => 'ศัลยกรรมหญิง',
        ]);
    }

    /**
     * @param list<int> $wardIds
     */
    private function copyUserWardAssignments($db, array $wardIds): void
    {
        if ($wardIds === [] || ! $db->tableExists('user_wards')) {
            return;
        }

        $sourceId = 16;
        $assignments = $db->table('user_wards')
            ->where('ward_id', $sourceId)
            ->get()
            ->getResultArray();

        foreach ($assignments as $assignment) {
            foreach ($wardIds as $wardId) {
                if ($wardId === $sourceId) {
                    continue;
                }
                $exists = $db->table('user_wards')
                    ->where('user_id', $assignment['user_id'])
                    ->where('ward_id', $wardId)
                    ->countAllResults();
                if ($exists > 0) {
                    continue;
                }
                $db->table('user_wards')->insert([
                    'user_id'  => $assignment['user_id'],
                    'ward_id'  => $wardId,
                ]);
            }
        }
    }
}
