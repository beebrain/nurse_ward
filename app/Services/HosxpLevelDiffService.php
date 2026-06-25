<?php

namespace App\Services;

use App\Models\CensusModel;
use App\Models\HourlyCensusModel;

/**
 * Derives departed-patient levels from consecutive HosXP 30-minute snapshots.
 */
class HosxpLevelDiffService
{
    public function __construct(
        private ?HourlyCensusModel $hourlyModel = null,
    ) {
        $this->hourlyModel = $hourlyModel ?? new HourlyCensusModel();
    }

    /**
     * @return array{
     *   has_level_data: bool,
     *   level_source: string,
     *   departed_by_level: array<int, int>,
     *   departure_care_hours: float,
     *   remaining_care_hours: float,
     *   total_care_hours: float,
     *   intervals: int
     * }
     */
    public function analyzeShift(int $wardId, string $date, string $shift): array
    {
        $empty = $this->emptyAnalysis();

        $data = $this->hourlyModel->getShiftTotals($wardId, $date, $shift);
        $timeline = $data['timeline'] ?? [];
        if ($timeline === [] || ! $this->timelineHasLevelData($timeline)) {
            return $empty;
        }

        $departedByLevel = array_fill(1, 5, 0);

        for ($i = 1, $n = count($timeline); $i < $n; $i++) {
            $intervalDeparted = $this->diffInterval($timeline[$i - 1], $timeline[$i]);
            foreach ($intervalDeparted as $level => $count) {
                $departedByLevel[$level] += $count;
            }
        }

        $lastSlot            = $timeline[count($timeline) - 1];
        $remainingCareHours  = $this->careHoursFromSlot($lastSlot);
        $departureCareHours  = $this->careHoursFromDeparted($departedByLevel);

        return [
            'has_level_data'       => true,
            'level_source'         => 'hosxp_diff',
            'departed_by_level'    => $departedByLevel,
            'departure_care_hours' => round($departureCareHours, 2),
            'remaining_care_hours' => round($remainingCareHours, 2),
            'total_care_hours'     => round($remainingCareHours + $departureCareHours, 2),
            'intervals'          => max(0, count($timeline) - 1),
        ];
    }

    /**
     * @param list<array<string, mixed>> $timeline
     */
    public function timelineHasLevelData(array $timeline): bool
    {
        foreach ($timeline as $slot) {
            if (! empty($slot['has_level_data'])) {
                return true;
            }
            foreach ([1, 2, 3, 4, 5] as $lv) {
                if ((int) ($slot["patients_level_{$lv}"] ?? 0) > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $prev
     * @param array<string, mixed> $curr
     * @return array<int, int> ward level => count departed this interval
     */
    public function diffInterval(array $prev, array $curr): array
    {
        $departed = array_fill(1, 5, 0);
        $decrease = [];

        foreach ([5, 4, 3, 2, 1] as $level) {
            $delta = (int) ($curr["patients_level_{$level}"] ?? 0) - (int) ($prev["patients_level_{$level}"] ?? 0);
            if ($delta < 0) {
                $decrease[$level] = -$delta;
            }
        }

        $outEvents = max(0, (int) ($curr['discharges_today'] ?? 0) - (int) ($prev['discharges_today'] ?? 0))
            + max(0, (int) ($curr['moves_out_today'] ?? 0) - (int) ($prev['moves_out_today'] ?? 0))
            + max(0, (int) ($curr['deaths_today'] ?? 0) - (int) ($prev['deaths_today'] ?? 0));

        if ($outEvents === 0 || $decrease === []) {
            return $departed;
        }

        $totalDecrease = array_sum($decrease);
        $toAssign      = min($outEvents, $totalDecrease);

        if ($toAssign <= 0) {
            return $departed;
        }

        if ($toAssign >= $totalDecrease) {
            foreach ($decrease as $level => $count) {
                $departed[$level] = $count;
            }

            return $departed;
        }

        // Proportional split when out_events < total level drops
        $assigned = 0;
        foreach ([5, 4, 3, 2, 1] as $level) {
            if (! isset($decrease[$level])) {
                continue;
            }
            $share = (int) floor($toAssign * $decrease[$level] / $totalDecrease);
            $departed[$level] = $share;
            $assigned += $share;
        }

        $remainder = $toAssign - $assigned;
        foreach ([5, 4, 3, 2, 1] as $level) {
            if ($remainder <= 0) {
                break;
            }
            if (($decrease[$level] ?? 0) > $departed[$level]) {
                $departed[$level]++;
                $remainder--;
            }
        }

        return $departed;
    }

    /**
     * @param array<string, mixed> $slot
     */
    public function careHoursFromSlot(array $slot): float
    {
        $hours = 0.0;
        foreach (CensusModel::LEVEL_HOURS as $level => $hourPerPatient) {
            $hours += (int) ($slot["patients_level_{$level}"] ?? 0) * $hourPerPatient;
        }

        return $hours;
    }

    /**
     * @param array<int, int> $departedByLevel
     */
    public function careHoursFromDeparted(array $departedByLevel): float
    {
        $hours = 0.0;
        foreach (CensusModel::LEVEL_HOURS as $level => $hourPerPatient) {
            $hours += (int) ($departedByLevel[$level] ?? 0) * $hourPerPatient;
        }

        return $hours;
    }

    /**
     * @return array{
     *   has_level_data: bool,
     *   level_source: string,
     *   departed_by_level: array<int, int>,
     *   departure_care_hours: float,
     *   remaining_care_hours: float,
     *   total_care_hours: float,
     *   intervals: int
     * }
     */
    private function emptyAnalysis(): array
    {
        return [
            'has_level_data'       => false,
            'level_source'         => 'none',
            'departed_by_level'    => array_fill(1, 5, 0),
            'departure_care_hours' => 0.0,
            'remaining_care_hours' => 0.0,
            'total_care_hours'     => 0.0,
            'intervals'            => 0,
        ];
    }
}
