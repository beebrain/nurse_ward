<?php

namespace App\Services;

use App\Models\CensusModel;

/**
 * Aggregates nursing productivity (required care hours / working hours) from shift census data.
 */
class ProductivityAggregateService
{
    protected CensusModel $censusModel;
    protected NursingProductivityService $productivityService;

    public function __construct(?CensusModel $censusModel = null, ?NursingProductivityService $productivityService = null)
    {
        $this->censusModel         = $censusModel ?? new CensusModel();
        $this->productivityService = $productivityService ?? new NursingProductivityService();
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
    public function getMonthlySummary(int $wardId, int $month, int $year, ?array $ward = null): array
    {
        $dateFrom = sprintf('%04d-%02d-01', $year, $month);
        $dateTo   = date('Y-m-t', strtotime($dateFrom));
        $rows     = $this->censusModel->getHistoryForList($wardId, $dateFrom, $dateTo, 120);
        $daily    = $this->buildDailyRows($rows, $ward);

        return $this->summarizeDailyRows(array_values($daily));
    }

    /**
     * Nursing productivity % for each month in a year (12 values).
     *
     * @return list<float|null>
     */
    public function getYearlyNursingTrend(int $wardId, int $year, ?array $ward = null): array
    {
        $values = [];
        for ($month = 1; $month <= 12; $month++) {
            $summary    = $this->getMonthlySummary($wardId, $month, $year, $ward);
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
                'productivity' => $this->getYearlyNursingTrend($wid, $year, $ward),
            ];
        }

        return $out;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<string, array<string, mixed>>
     */
    public function buildDailyRows(
        array $rows,
        ?array $ward = null,
        ?callable $dateLabel = null,
        ?callable $weekdayLabel = null
    ): array
    {
        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row['record_date']][$row['shift']] = $row;
        }

        $mode = NursingProductivityService::modeForWard($ward);
        $hosxp = new HosxpLevelDiffService();
        $wardId = $ward ? (int) ($ward['id'] ?? 0) : null;

        ksort($byDate);
        $daily = [];

        foreach ($byDate as $date => $shifts) {
            $metrics = $this->productivityService->buildDailyMetrics(
                $shifts,
                $mode,
                $wardId ?: null,
                $date,
                $hosxp
            );

            $admissions = 0;
            $discharges = 0;
            $transfersIn = 0;
            $transfersOut = 0;
            $deaths = 0;

            foreach ($shifts as $shiftRow) {
                $admissions += (int) $shiftRow['admissions'];
                $discharges += (int) $shiftRow['discharges'];
                $transfersIn += (int) $shiftRow['transfers_in'];
                $transfersOut += (int) $shiftRow['transfers_out'];
                $deaths += (int) $shiftRow['deaths'];
            }

            $daily[$date] = [
                'date'                => $date,
                'day_label'           => $dateLabel ? $dateLabel($date) : $date,
                'weekday_label'       => $weekdayLabel ? $weekdayLabel($date) : '',
                'patient_days'        => $metrics['patient_days'],
                'patient_day_shift'   => $metrics['patient_day_shift'],
                'care_shift'          => $metrics['care_shift'],
                'recorded_shifts'     => count($shifts),
                'required_care_hours' => $metrics['required_care_hours'],
                'working_hours'       => $metrics['working_hours'],
                'productivity'        => $metrics['productivity'],
                'turnover_cases'      => $metrics['turnover_cases'],
                'level_source'        => $metrics['level_source'],
                'productivity_mode'   => $mode,
                'admissions'          => $admissions,
                'discharges'          => $discharges,
                'transfers_in'        => $transfersIn,
                'transfers_out'       => $transfersOut,
                'deaths'              => $deaths,
            ];
        }

        return $daily;
    }

    /**
     * @param list<array<string, mixed>> $dailyRows
     */
    public function summarizeDailyRows(array $dailyRows): array
    {
        $summary = [
            'recorded_days'       => count($dailyRows),
            'recorded_shifts'     => 0,
            'patient_days'        => 0,
            'required_care_hours' => 0.0,
            'working_hours'       => 0.0,
            'productivity'        => null,
            'admissions'          => 0,
            'discharges'          => 0,
            'transfers_in'        => 0,
            'transfers_out'       => 0,
            'deaths'              => 0,
        ];

        foreach ($dailyRows as $row) {
            $summary['recorded_shifts']     += $row['recorded_shifts'];
            $summary['patient_days']        += $row['patient_days'];
            $summary['required_care_hours'] += $row['required_care_hours'];
            $summary['working_hours']       += $row['working_hours'];
            $summary['admissions']          += $row['admissions'] ?? 0;
            $summary['discharges']          += $row['discharges'] ?? 0;
            $summary['transfers_in']        += $row['transfers_in'] ?? 0;
            $summary['transfers_out']       += $row['transfers_out'] ?? 0;
            $summary['deaths']              += $row['deaths'] ?? 0;
        }

        $summary['required_care_hours'] = round($summary['required_care_hours'], 2);
        $summary['working_hours']       = round($summary['working_hours'], 2);
        $summary['productivity']        = $summary['working_hours'] > 0 && $summary['required_care_hours'] > 0
            ? round(($summary['required_care_hours'] * 100) / $summary['working_hours'], 2)
            : null;

        return $summary;
    }
}
