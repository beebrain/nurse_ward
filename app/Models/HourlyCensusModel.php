<?php

namespace App\Models;

use CodeIgniter\Model;

class HourlyCensusModel extends Model
{
    protected $table            = 'hourly_patient_census';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ward_id',
        'record_time',
        'source_api_ward_name',
        'patient_count',
        'has_level_data',
        'patients_level_5',
        'patients_level_4',
        'patients_level_3',
        'patients_level_2',
        'patients_level_1',
        'admissions_today',
        'discharges_today',
        'moves_in_today',
        'moves_out_today',
        'deaths_today',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * รวมแถวหลาย source_api_ward_name (ชื่อหลัก + alias) ต่อ record_time สำหรับแสดงผล
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    public function aggregateTimelineByRecordTime(array $rows): array
    {
        $byTime = [];

        foreach ($rows as $row) {
            $t = (string) ($row['record_time'] ?? '');
            if ($t === '') {
                continue;
            }

            if (! isset($byTime[$t])) {
                $byTime[$t] = [
                    'record_time'       => $t,
                    'patient_count'     => 0,
                    'has_level_data'    => 0,
                    'patients_level_5'  => 0,
                    'patients_level_4'  => 0,
                    'patients_level_3'  => 0,
                    'patients_level_2'  => 0,
                    'patients_level_1'  => 0,
                    'admissions_today'  => 0,
                    'discharges_today'  => 0,
                    'moves_in_today'    => 0,
                    'moves_out_today'   => 0,
                    'deaths_today'      => 0,
                ];
            }

            $byTime[$t]['patient_count']    += (int) ($row['patient_count'] ?? 0);
            $byTime[$t]['has_level_data']    = max(
                (int) $byTime[$t]['has_level_data'],
                (int) ($row['has_level_data'] ?? 0)
            );
            foreach ([5, 4, 3, 2, 1] as $lv) {
                $byTime[$t]["patients_level_{$lv}"] += (int) ($row["patients_level_{$lv}"] ?? 0);
            }
            $byTime[$t]['admissions_today'] += (int) ($row['admissions_today'] ?? 0);
            $byTime[$t]['discharges_today'] += (int) ($row['discharges_today'] ?? 0);
            $byTime[$t]['moves_in_today']   += (int) ($row['moves_in_today'] ?? 0);
            $byTime[$t]['moves_out_today']  += (int) ($row['moves_out_today'] ?? 0);
            $byTime[$t]['deaths_today']     += (int) ($row['deaths_today'] ?? 0);
        }

        ksort($byTime);

        foreach ($byTime as &$slot) {
            $levelSum = 0;
            foreach ([1, 2, 3, 4, 5] as $lv) {
                $levelSum += (int) $slot["patients_level_{$lv}"];
            }
            if ($levelSum > 0) {
                $slot['has_level_data'] = 1;
            }
        }
        unset($slot);

        return array_values($byTime);
    }

    /**
     * Get shift timeline and compute net movement totals for a specific ward, date, and shift.
     * รวมยอดจากทุก source_api_ward_name ที่บันทึกแยกตาม config
     *
     * @return array{timeline: list<array<string, mixed>>, totals: array<string, int>}
     */
    public function getShiftTotals(int $wardId, string $date, string $shift): array
    {
        switch ($shift) {
            case 'Night':
                $startTime = "{$date} 00:00:00";
                $endTime   = "{$date} 08:00:00";
                $isNight   = true;
                break;
            case 'Morning':
                $startTime = "{$date} 08:00:00";
                $endTime   = "{$date} 16:00:00";
                $isNight   = false;
                break;
            case 'Afternoon':
                $startTime = "{$date} 16:00:00";
                $endTime   = "{$date} 23:59:59";
                $isNight   = false;
                break;
            default:
                return [
                    'timeline' => [],
                    'totals'   => $this->getEmptyTotals(),
                ];
        }

        $rawRows = $this->where('ward_id', $wardId)
            ->where('record_time >=', $startTime)
            ->where('record_time <=', $endTime)
            ->orderBy('record_time', 'ASC')
            ->findAll();

        $timeline = $this->aggregateTimelineByRecordTime($rawRows);

        if ($timeline === []) {
            return [
                'timeline' => [],
                'totals'   => $this->getEmptyTotals(),
            ];
        }

        $first = $timeline[0];
        $last  = $timeline[count($timeline) - 1];

        if ($isNight) {
            $startAdmissions = (strtotime($first['record_time']) - strtotime($startTime) < 3600) ? $first['admissions_today'] : 0;
            $startDischarges = (strtotime($first['record_time']) - strtotime($startTime) < 3600) ? $first['discharges_today'] : 0;
            $startMovesIn    = (strtotime($first['record_time']) - strtotime($startTime) < 3600) ? $first['moves_in_today'] : 0;
            $startMovesOut   = (strtotime($first['record_time']) - strtotime($startTime) < 3600) ? $first['moves_out_today'] : 0;
            $startDeaths     = (strtotime($first['record_time']) - strtotime($startTime) < 3600) ? $first['deaths_today'] : 0;
        } else {
            $startAdmissions = $first['admissions_today'];
            $startDischarges = $first['discharges_today'];
            $startMovesIn    = $first['moves_in_today'];
            $startMovesOut   = $first['moves_out_today'];
            $startDeaths     = $first['deaths_today'];
        }

        $totals = [
            'admissions'    => max(0, $last['admissions_today'] - $startAdmissions),
            'discharges'    => max(0, $last['discharges_today'] - $startDischarges),
            'transfers_in'  => max(0, $last['moves_in_today'] - $startMovesIn),
            'transfers_out' => max(0, $last['moves_out_today'] - $startMovesOut),
            'deaths'        => max(0, $last['deaths_today'] - $startDeaths),
            'patient_count' => $last['patient_count'],
        ];

        return [
            'timeline' => $timeline,
            'totals'   => $totals,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function getEmptyTotals(): array
    {
        return [
            'admissions'    => 0,
            'discharges'    => 0,
            'transfers_in'  => 0,
            'transfers_out' => 0,
            'deaths'        => 0,
            'patient_count' => 0,
        ];
    }
}
