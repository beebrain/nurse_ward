<?php

namespace App\Services;

use App\Models\CensusModel;
use App\Models\WardModel;

class ReportService
{
    protected $censusModel;
    protected $wardModel;

    public function __construct($censusModel = null, $wardModel = null)
    {
        $this->censusModel = $censusModel;
        $this->wardModel = $wardModel;
    }

    protected function getCensusModel()
    {
        if ($this->censusModel === null) {
            $this->censusModel = new CensusModel();
        }
        return $this->censusModel;
    }

    protected function getWardModel()
    {
        if ($this->wardModel === null) {
            $this->wardModel = new WardModel();
        }
        return $this->wardModel;
    }

    /**
     * Get aggregated monthly data for a ward.
     * 
     * @param int $wardId
     * @param int $month
     * @param int $year
     * @return array
     */
    public function getMonthlyData(int $wardId, int $month, int $year): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        // Get all census data for the month
        $builder = $this->getCensusModel()->builder();
        $builder->where('ward_id', $wardId);
        $builder->where('record_date >=', $startDate);
        $builder->where('record_date <=', $endDate);
        
        $censusData = $builder->get()->getResultArray();

        $totals = [
            'admissions' => 0,
            'discharges' => 0,
            'transfers_in' => 0,
            'transfers_out' => 0,
            'deaths' => 0,
            'patient_days' => 0,
        ];

        // Group by date to handle Patient Days (using Night shift or last available shift)
        $dailyData = [];
        foreach ($censusData as $row) {
            $date = $row['record_date'];
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [
                    'admissions' => 0,
                    'discharges' => 0,
                    'transfers_in' => 0,
                    'transfers_out' => 0,
                    'deaths' => 0,
                    'shifts' => []
                ];
            }

            $dailyData[$date]['admissions'] += (int)$row['admissions'];
            $dailyData[$date]['discharges'] += (int)$row['discharges'];
            $dailyData[$date]['transfers_in'] += (int)$row['transfers_in'];
            $dailyData[$date]['transfers_out'] += (int)$row['transfers_out'];
            $dailyData[$date]['deaths'] += (int)$row['deaths'];
            $dailyData[$date]['shifts'][$row['shift']] = (int)$row['total_remaining'];
        }

        foreach ($dailyData as $date => $data) {
            $totals['admissions'] += $data['admissions'];
            $totals['discharges'] += $data['discharges'];
            $totals['transfers_in'] += $data['transfers_in'];
            $totals['transfers_out'] += $data['transfers_out'];
            $totals['deaths'] += $data['deaths'];

            // Patient Days: Prefer Night shift, then Afternoon, then Morning
            if (isset($data['shifts']['Night'])) {
                $totals['patient_days'] += $data['shifts']['Night'];
            } elseif (isset($data['shifts']['Afternoon'])) {
                $totals['patient_days'] += $data['shifts']['Afternoon'];
            } elseif (isset($data['shifts']['Morning'])) {
                $totals['patient_days'] += $data['shifts']['Morning'];
            }
        }

        return $totals;
    }

    /**
     * Get ward bed count.
     * 
     * @param int $wardId
     * @return int
     */
    public function getWardBeds(int $wardId): int
    {
        $ward = $this->getWardModel()->find($wardId);
        return $ward ? (int)$ward['total_beds'] : 0;
    }

    /**
     * Get full monthly report including metrics.
     * 
     * @param int $wardId
     * @param int $month
     * @param int $year
     * @return array
     */
    public function getMonthlyReport(int $wardId, int $month, int $year): array
    {
        $data = $this->getMonthlyData($wardId, $month, $year);
        $beds = $this->getWardBeds($wardId);
        // Use date() so the optional PHP "calendar" extension is not required (often missing in minimal Docker images).
        $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));

        $data['ward_beds'] = $beds;
        $data['days_in_month'] = $daysInMonth;
        $data['productivity'] = $this->calculateProductivity($data['patient_days'], $beds, $daysInMonth);

        return $data;
    }

    /**
     * Calculate productivity percentage.
     * 
     * Formula: (Total Patient Days / (Ward Beds * Days in Month)) * 100
     * 
     * @param int $patientDays
     * @param int $wardBeds
     * @param int $daysInMonth
     * @return float
     */
    public function calculateProductivity(int $patientDays, int $wardBeds, int $daysInMonth): float
    {
        if ($wardBeds <= 0 || $daysInMonth <= 0) {
            return 0.0;
        }

        $capacity = $wardBeds * $daysInMonth;
        return ($patientDays / $capacity) * 100;
    }

    /**
     * Get monthly patient-day trend for all months in a year.
     *
     * @param int $wardId
     * @param int $year
     * @return array<int, int>
     */
    public function getYearlyTrend(int $wardId, int $year): array
    {
        $trend = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthly = $this->getMonthlyReport($wardId, $month, $year);
            $trend[] = (int) $monthly['patient_days'];
        }

        return $trend;
    }

    /**
     * Get cross-ward productivity comparison for a given month.
     *
     * @param int $month
     * @param int $year
     * @return array{labels: array<int, string>, productivity: array<int, float>, patient_days: array<int, int>}
     */
    public function getWardComparison(int $month, int $year): array
    {
        $wards = $this->getWardModel()
            ->where('is_active', true)
            ->findAll();

        $labels = [];
        $productivity = [];
        $patientDays = [];

        foreach ($wards as $ward) {
            $report = $this->getMonthlyReport((int) $ward['id'], $month, $year);
            $labels[] = (string) $ward['name'];
            $productivity[] = round((float) $report['productivity'], 2);
            $patientDays[] = (int) $report['patient_days'];
        }

        return [
            'labels' => $labels,
            'productivity' => $productivity,
            'patient_days' => $patientDays,
        ];
    }

    /**
     * Get daily summary rows for a ward in selected month/year.
     *
     * @param int $wardId
     * @param int $month
     * @param int $year
     * @return array<int, array<string, int|string>>
     */
    public function getDailySummaryTable(int $wardId, int $month, int $year): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $rows = $this->getCensusModel()
            ->where('ward_id', $wardId)
            ->where('record_date >=', $startDate)
            ->where('record_date <=', $endDate)
            ->orderBy('record_date', 'ASC')
            ->findAll();

        $daily = [];
        foreach ($rows as $row) {
            $date = $row['record_date'];
            if (! isset($daily[$date])) {
                $daily[$date] = [
                    'record_date' => $date,
                    'admissions' => 0,
                    'discharges' => 0,
                    'transfers_in' => 0,
                    'transfers_out' => 0,
                    'deaths' => 0,
                    'patient_days' => 0,
                    'shifts' => [],
                ];
            }

            $daily[$date]['admissions'] += (int) $row['admissions'];
            $daily[$date]['discharges'] += (int) $row['discharges'];
            $daily[$date]['transfers_in'] += (int) $row['transfers_in'];
            $daily[$date]['transfers_out'] += (int) $row['transfers_out'];
            $daily[$date]['deaths'] += (int) $row['deaths'];
            $daily[$date]['shifts'][$row['shift']] = (int) $row['total_remaining'];
        }

        foreach ($daily as &$day) {
            if (isset($day['shifts']['Night'])) {
                $day['patient_days'] = $day['shifts']['Night'];
            } elseif (isset($day['shifts']['Afternoon'])) {
                $day['patient_days'] = $day['shifts']['Afternoon'];
            } elseif (isset($day['shifts']['Morning'])) {
                $day['patient_days'] = $day['shifts']['Morning'];
            }
            unset($day['shifts']);
        }
        unset($day);

        return array_values($daily);
    }

    /**
     * @return list<string> Thai short month labels (12).
     */
    public function getThaiMonthShortLabels(): array
    {
        return ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    }

    /**
     * Monthly productivity % for one ward across a calendar year.
     *
     * @return list<float>
     */
    public function getYearlyProductivityTrend(int $wardId, int $year): array
    {
        $values = [];
        for ($m = 1; $m <= 12; $m++) {
            $report = $this->getMonthlyReport($wardId, $m, $year);
            $values[] = round((float) $report['productivity'], 2);
        }

        return $values;
    }

    /**
     * Latest recorded census day for a ward: occupancy (คงพยาบาล) using Night → Afternoon → Morning.
     *
     * @return array{occupancy: int, record_date: string|null, shift_used: string|null, shift_label: string|null}
     */
    public function getLatestCensusDaySnapshot(int $wardId): array
    {
        $builder = $this->getCensusModel()->builder();
        $builder->selectMax('record_date', 'max_date');
        $builder->where('ward_id', $wardId);
        $maxRow = $builder->get()->getRowArray();

        $date = $maxRow['max_date'] ?? null;
        if ($date === null || $date === '') {
            return [
                'occupancy'    => 0,
                'record_date'  => null,
                'shift_used'   => null,
                'shift_label'  => null,
            ];
        }

        $rows = $this->getCensusModel()
            ->where('ward_id', $wardId)
            ->where('record_date', $date)
            ->findAll();

        $shifts = [];
        foreach ($rows as $r) {
            $shifts[$r['shift']] = (int) $r['total_remaining'];
        }

        $occupancy  = 0;
        $shiftUsed  = null;
        $shiftLabel = null;
        $map        = ['Morning' => 'เช้า', 'Afternoon' => 'บ่าย', 'Night' => 'ดึก'];

        foreach (['Night', 'Afternoon', 'Morning'] as $s) {
            if (isset($shifts[$s])) {
                $occupancy  = $shifts[$s];
                $shiftUsed  = $s;
                $shiftLabel = $map[$s] ?? $s;
                break;
            }
        }

        return [
            'occupancy'    => $occupancy,
            'record_date'  => $date,
            'shift_used'   => $shiftUsed,
            'shift_label'  => $shiftLabel,
        ];
    }

    /**
     * Per-ward snapshot for executive dashboard (latest census vs bed capacity).
     *
     * @return list<array<string, int|float|string|null>>
     */
    public function getExecutiveSnapshot(): array
    {
        $wards = $this->getWardModel()
            ->where('is_active', true)
            ->orderBy('name', 'ASC')
            ->findAll();

        $out = [];
        foreach ($wards as $ward) {
            $wid  = (int) $ward['id'];
            $beds = (int) $ward['total_beds'];
            $snap = $this->getLatestCensusDaySnapshot($wid);

            $util = null;
            if ($beds > 0) {
                $util = round(($snap['occupancy'] / $beds) * 100, 1);
            }

            $out[] = [
                'ward_id'       => $wid,
                'ward_name'     => (string) $ward['name'],
                'total_beds'    => $beds,
                'occupancy'     => $snap['occupancy'],
                'record_date'   => $snap['record_date'],
                'shift_label'   => $snap['shift_label'],
                'utilization_pct' => $util,
            ];
        }

        return $out;
    }
}
