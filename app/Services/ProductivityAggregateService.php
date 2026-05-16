<?php

namespace App\Services;

use App\Models\CensusModel;

/**
 * Aggregates nursing productivity (required care hours / working hours) from shift census data.
 */
class ProductivityAggregateService
{
    protected CensusModel $censusModel;

    public function __construct(?CensusModel $censusModel = null)
    {
        $this->censusModel = $censusModel ?? new CensusModel();
    }

    /**
     * Monthly nursing productivity summary for one ward.
     *
     * @return array{
     *   recorded_days: int,
     *   recorded_shifts: int,
     *   patient_days: int,
     *   required_care_hours: float,
     *   working_hours: float,
     *   productivity: float|null
     * }
     */
    public function getMonthlySummary(int $wardId, int $month, int $year): array
    {
        $dateFrom = sprintf('%04d-%02d-01', $year, $month);
        $dateTo   = date('Y-m-t', strtotime($dateFrom));
        $rows     = $this->censusModel->getHistoryForList($wardId, $dateFrom, $dateTo, 120);
        $daily    = $this->buildDailyRows($rows);

        return $this->summarizeDailyRows(array_values($daily));
    }

    /**
     * Nursing productivity % for each month in a year (12 values).
     *
     * @return list<float|null>
     */
    public function getYearlyNursingTrend(int $wardId, int $year): array
    {
        $values = [];
        for ($month = 1; $month <= 12; $month++) {
            $summary    = $this->getMonthlySummary($wardId, $month, $year);
            $values[]   = $summary['productivity'];
        }

        return $values;
    }

    /**
     * @return list<array{ward_id: int, ward_name: string, productivity: list<float|null>}>
     */
    public function getYearlyNursingTrendAllWards(int $year, array $wards): array
    {
        $out = [];
        foreach ($wards as $ward) {
            $wid = (int) $ward['id'];
            $out[] = [
                'ward_id'      => $wid,
                'ward_name'    => (string) $ward['name'],
                'productivity' => $this->getYearlyNursingTrend($wid, $year),
            ];
        }

        return $out;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<string, array<string, mixed>>
     */
    private function buildDailyRows(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row['record_date']][$row['shift']] = $row;
        }

        ksort($byDate);
        $daily = [];

        foreach ($byDate as $date => $shifts) {
            $patientDayRow = $this->pickShiftRow($shifts, ['Night', 'Afternoon', 'Morning']);
            $careRow       = $this->pickShiftRow($shifts, ['Afternoon', 'Night', 'Morning']);
            $requiredCareHours = $careRow ? $this->requiredCareHoursFromRow($careRow) : 0.0;
            $workingHours      = 0.0;

            foreach ($shifts as $shiftRow) {
                $workingHours += (float) ($shiftRow['working_hours'] ?? 0);
            }

            $daily[$date] = [
                'patient_days'          => (int) ($patientDayRow['total_patients'] ?? 0),
                'recorded_shifts'       => count($shifts),
                'required_care_hours'   => round($requiredCareHours, 2),
                'working_hours'         => round($workingHours, 2),
                'productivity'          => $workingHours > 0 && $requiredCareHours > 0
                    ? round(($requiredCareHours * 100) / $workingHours, 2)
                    : null,
            ];
        }

        return $daily;
    }

    /**
     * @param list<array<string, mixed>> $dailyRows
     */
    private function summarizeDailyRows(array $dailyRows): array
    {
        $summary = [
            'recorded_days'       => count($dailyRows),
            'recorded_shifts'     => 0,
            'patient_days'        => 0,
            'required_care_hours' => 0.0,
            'working_hours'       => 0.0,
            'productivity'        => null,
        ];

        foreach ($dailyRows as $row) {
            $summary['recorded_shifts']     += $row['recorded_shifts'];
            $summary['patient_days']        += $row['patient_days'];
            $summary['required_care_hours'] += $row['required_care_hours'];
            $summary['working_hours']       += $row['working_hours'];
        }

        $summary['required_care_hours'] = round($summary['required_care_hours'], 2);
        $summary['working_hours']       = round($summary['working_hours'], 2);
        $summary['productivity']        = $summary['working_hours'] > 0 && $summary['required_care_hours'] > 0
            ? round(($summary['required_care_hours'] * 100) / $summary['working_hours'], 2)
            : null;

        return $summary;
    }

    private function pickShiftRow(array $shifts, array $priority): ?array
    {
        foreach ($priority as $shift) {
            if (isset($shifts[$shift])) {
                return $shifts[$shift];
            }
        }

        return null;
    }

    private function requiredCareHoursFromRow(array $row): float
    {
        if ((float) ($row['required_care_hours'] ?? 0) > 0) {
            return (float) $row['required_care_hours'];
        }

        $hours = 0.0;
        foreach (CensusModel::LEVEL_HOURS as $level => $hourPerPatient) {
            $hours += (int) ($row["patients_level_{$level}"] ?? 0) * $hourPerPatient;
        }

        return $hours;
    }
}
