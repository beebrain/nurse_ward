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
        'patient_count',
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
     * Get shift timeline and compute net movement totals for a specific ward, date, and shift.
     *
     * @param int    $wardId
     * @param string $date  Format: YYYY-MM-DD
     * @param string $shift 'Night', 'Morning', or 'Afternoon'
     * @return array
     */
    public function getShiftTotals(int $wardId, string $date, string $shift): array
    {
        // Define times for shifts
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
                    'totals'   => $this->getEmptyTotals()
                ];
        }

        // Fetch all hourly census records within the shift
        $timeline = $this->where('ward_id', $wardId)
                         ->where('record_time >=', $startTime)
                         ->where('record_time <=', $endTime)
                         ->orderBy('record_time', 'ASC')
                         ->findAll();

        if (empty($timeline)) {
            return [
                'timeline' => [],
                'totals'   => $this->getEmptyTotals()
            ];
        }

        $first = $timeline[0];
        $last  = $timeline[count($timeline) - 1];

        // Shift calculation
        if ($isNight) {
            // For Night shift (00:00 - 08:00), cumulative stats start from 00:00.
            // If the first snapshot is close to 00:00, we subtract it.
            // If there's only one record (e.g. at 08:00), we treat the start as 0.
            $startAdmissions = (strtotime($first['record_time']) - strtotime($startTime) < 3600) ? $first['admissions_today'] : 0;
            $startDischarges = (strtotime($first['record_time']) - strtotime($startTime) < 3600) ? $first['discharges_today'] : 0;
            $startMovesIn    = (strtotime($first['record_time']) - strtotime($startTime) < 3600) ? $first['moves_in_today'] : 0;
            $startMovesOut   = (strtotime($first['record_time']) - strtotime($startTime) < 3600) ? $first['moves_out_today'] : 0;
            $startDeaths     = (strtotime($first['record_time']) - strtotime($startTime) < 3600) ? $first['deaths_today'] : 0;
        } else {
            // For Morning and Afternoon, we subtract the start hour snapshot (e.g., 08:00 or 16:00)
            // if it exists, or default to 0 if it doesn't.
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
            'patient_count' => $last['patient_count'], // current/end count
        ];

        return [
            'timeline' => $timeline,
            'totals'   => $totals
        ];
    }

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
