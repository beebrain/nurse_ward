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
            $dailyData[$date]['shifts'][$row['shift']] = $this->censusTotalPatients($row);
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
     * Yearly patient-days trend for every active ward (for multi-series charts).
     *
     * @return list<array{ward_id: int, ward_name: string, patient_days: list<int>}>
     */
    public function getYearlyTrendAllWards(int $year): array
    {
        $wards = $this->getWardModel()
            ->where('is_active', true)
            ->orderBy('name', 'ASC')
            ->findAll();

        $out = [];
        foreach ($wards as $ward) {
            $wid = (int) $ward['id'];
            $out[] = [
                'ward_id'      => $wid,
                'ward_name'    => (string) $ward['name'],
                'patient_days' => $this->getYearlyTrend($wid, $year),
            ];
        }

        return $out;
    }

    /**
     * Yearly productivity % trend for every active ward.
     *
     * @return list<array{ward_id: int, ward_name: string, productivity: list<float>}>
     */
    public function getYearlyProductivityTrendAllWards(int $year): array
    {
        $wards = $this->getWardModel()
            ->where('is_active', true)
            ->orderBy('name', 'ASC')
            ->findAll();

        $out = [];
        foreach ($wards as $ward) {
            $wid = (int) $ward['id'];
            $out[] = [
                'ward_id'     => $wid,
                'ward_name'   => (string) $ward['name'],
                'productivity' => $this->getYearlyProductivityTrend($wid, $year),
            ];
        }

        return $out;
    }

    /**
     * Colors for Chart.js datasets (line charts, multiple wards).
     *
     * @return list<array{border: string, fill: string}>
     */
    public function getChartColorPalette(int $count): array
    {
        $rgb = [
            [0, 93, 172],
            [25, 135, 84],
            [220, 53, 69],
            [102, 16, 242],
            [253, 126, 20],
            [32, 201, 151],
            [214, 51, 132],
            [111, 66, 193],
            [13, 202, 240],
            [108, 117, 125],
        ];

        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $c   = $rgb[$i % count($rgb)];
            $out[] = [
                'border' => sprintf('rgb(%d,%d,%d)', $c[0], $c[1], $c[2]),
                'fill'   => sprintf('rgba(%d,%d,%d,0.06)', $c[0], $c[1], $c[2]),
            ];
        }

        return $out;
    }

    /**
     * Build Chart.js-style datasets for patient-day trends.
     *
     * @param list<array{ward_name: string, patient_days: list<int>}> $series
     * @return list<array{label: string, data: list<int>, borderColor: string, backgroundColor: string, fill: bool, tension: float, pointRadius: int}>
     */
    public function buildPatientDayTrendDatasets(array $series): array
    {
        $palette = $this->getChartColorPalette(count($series));
        $datasets = [];

        foreach ($series as $i => $row) {
            $p = $palette[$i];
            $datasets[] = [
                'label'            => $row['ward_name'],
                'data'             => $row['patient_days'],
                'borderColor'      => $p['border'],
                'backgroundColor'  => $p['fill'],
                'fill'             => count($series) === 1,
                'tension'          => 0.25,
                'pointRadius'      => count($series) > 5 ? 2 : 3,
                'pointHoverRadius' => 5,
                'borderWidth'      => 2,
            ];
        }

        return $datasets;
    }

    /**
     * @param list<array{ward_name: string, productivity: list<float>}> $series
     * @return list<array<string, mixed>>
     */
    public function buildProductivityTrendDatasets(array $series): array
    {
        $palette = $this->getChartColorPalette(count($series));
        $datasets  = [];

        foreach ($series as $i => $row) {
            $p = $palette[$i];
            $datasets[] = [
                'label'            => $row['ward_name'],
                'data'             => $row['productivity'],
                'borderColor'      => $p['border'],
                'backgroundColor'  => $p['fill'],
                'fill'             => count($series) === 1,
                'tension'          => 0.25,
                'pointRadius'      => count($series) > 5 ? 2 : 3,
                'pointHoverRadius' => 5,
                'borderWidth'      => 2,
            ];
        }

        return $datasets;
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
            $daily[$date]['shifts'][$row['shift']] = $this->censusTotalPatients($row);
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
     * Daily census matrix for all wards (rows = days, columns = wards).
     *
     * @param list<array<string, mixed>> $wards
     * @return array{wards: list<array<string, int|string>>, days: list<array<string, mixed>>, summary: array<string, int>}
     */
    public function getAllWardsDailyMatrix(array $wards, int $month, int $year): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $lastDay   = (int) date('t', strtotime($startDate));

        $days = [];
        for ($day = 1; $day <= $lastDay; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $days[$date] = [
                'date'    => $date,
                'by_ward' => [],
            ];
        }

        $wardMeta = [];
        $summary  = [
            'patient_days'  => 0,
            'admissions'    => 0,
            'discharges'    => 0,
            'transfers_in'  => 0,
            'transfers_out' => 0,
            'deaths'        => 0,
        ];

        foreach ($wards as $ward) {
            $wardId = (int) $ward['id'];
            $wardMeta[] = [
                'id'    => $wardId,
                'code'  => (string) ($ward['code'] ?? ''),
                'name'  => (string) $ward['name'],
                'label' => trim((string) ($ward['code'] ?? '') . ' ' . $ward['name']),
            ];

            foreach ($this->getDailySummaryTable($wardId, $month, $year) as $row) {
                $date = $row['record_date'];
                if (! isset($days[$date])) {
                    continue;
                }
                $days[$date]['by_ward'][(string) $wardId] = [
                    'patient_days'  => (int) $row['patient_days'],
                    'admissions'    => (int) $row['admissions'],
                    'discharges'    => (int) $row['discharges'],
                    'transfers_in'  => (int) $row['transfers_in'],
                    'transfers_out' => (int) $row['transfers_out'],
                    'deaths'        => (int) $row['deaths'],
                ];
                $summary['patient_days']  += (int) $row['patient_days'];
                $summary['admissions']    += (int) $row['admissions'];
                $summary['discharges']    += (int) $row['discharges'];
                $summary['transfers_in']  += (int) $row['transfers_in'];
                $summary['transfers_out'] += (int) $row['transfers_out'];
                $summary['deaths']        += (int) $row['deaths'];
            }
        }

        return [
            'wards'   => $wardMeta,
            'days'    => array_values($days),
            'summary' => $summary,
        ];
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
            $shifts[$r['shift']] = $this->censusTotalPatients($r);
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

    /**
     * Aggregate productivity by department (กลุ่มงาน) for a given month.
     *
     * @return array{labels: list<string>, productivity: list<float>, patient_days: list<int>}
     */
    public function getDepartmentProductivity(int $month, int $year): array
    {
        $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));

        $db   = \Config\Database::connect();
        $rows = $db->table('wards w')
            ->select('w.id, w.total_beds, d.id AS dept_id, d.short_name AS dept_name, d.sort_order')
            ->join('departments d', 'd.id = w.department_id', 'inner')
            ->where('w.is_active', 1)
            ->orderBy('d.sort_order', 'ASC')
            ->get()->getResultArray();

        $depts = [];
        foreach ($rows as $row) {
            $deptId = (int) $row['dept_id'];
            if (! isset($depts[$deptId])) {
                $depts[$deptId] = [
                    'name'       => $row['dept_name'],
                    'total_beds' => 0,
                    'ward_ids'   => [],
                ];
            }
            $depts[$deptId]['total_beds'] += (int) $row['total_beds'];
            $depts[$deptId]['ward_ids'][]  = (int) $row['id'];
        }

        $labels      = [];
        $productivity = [];
        $patientDays = [];

        foreach ($depts as $dept) {
            $days = 0;
            foreach ($dept['ward_ids'] as $wid) {
                $days += (int) $this->getMonthlyReport($wid, $month, $year)['patient_days'];
            }
            $labels[]      = $dept['name'];
            $patientDays[] = $days;
            $productivity[] = round($this->calculateProductivity($days, $dept['total_beds'], $daysInMonth), 1);
        }

        return [
            'labels'       => $labels,
            'productivity' => $productivity,
            'patient_days' => $patientDays,
        ];
    }

    /**
     * Cross-ward comparison with occupancy and nursing productivity metrics.
     *
     * @return array{
     *   wards: list<array<string, int|float|string|null>>,
     *   summary: array<string, int|float|string|null>
     * }
     */
    public function getFullWardComparison(int $month, int $year): array
    {
        $wards = $this->getWardModel()
            ->where('is_active', true)
            ->orderBy('name', 'ASC')
            ->findAll();

        $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        $aggregate   = new ProductivityAggregateService($this->getCensusModel());

        $wardRows           = [];
        $totalPatientDays   = 0;
        $totalBeds          = 0;
        $totalRequiredHours = 0.0;
        $totalWorkingHours  = 0.0;

        foreach ($wards as $ward) {
            $wardId  = (int) $ward['id'];
            $report  = $this->getMonthlyReport($wardId, $month, $year);
            $nursing = $aggregate->getMonthlySummary($wardId, $month, $year);
            $beds    = (int) $ward['total_beds'];

            $wardRows[] = [
                'ward_id'                => $wardId,
                'ward_name'              => (string) $ward['name'],
                'ward_code'              => (string) ($ward['code'] ?? ''),
                'beds'                   => $beds,
                'patient_days'         => (int) $report['patient_days'],
                'occupancy_productivity' => round((float) $report['productivity'], 2),
                'nursing_productivity'   => $nursing['productivity'],
                'recorded_days'          => $nursing['recorded_days'],
                'working_hours'          => $nursing['working_hours'],
                'required_care_hours'    => $nursing['required_care_hours'],
            ];

            $totalPatientDays   += (int) $report['patient_days'];
            $totalBeds          += $beds;
            $totalRequiredHours += $nursing['required_care_hours'];
            $totalWorkingHours  += $nursing['working_hours'];
        }

        $capacity       = $totalBeds * $daysInMonth;
        $avgOccupancy   = $capacity > 0 ? round(($totalPatientDays / $capacity) * 100, 2) : 0.0;
        $avgNursing     = $totalWorkingHours > 0
            ? round(($totalRequiredHours * 100) / $totalWorkingHours, 2)
            : null;

        return [
            'wards'   => $wardRows,
            'summary' => [
                'total_patient_days'      => $totalPatientDays,
                'total_beds'              => $totalBeds,
                'avg_occupancy_productivity' => $avgOccupancy,
                'avg_nursing_productivity'   => $avgNursing,
                'best_occupancy'          => $this->extremumWard($wardRows, 'occupancy_productivity', true),
                'worst_occupancy'         => $this->extremumWard($wardRows, 'occupancy_productivity', false),
                'best_nursing'            => $this->extremumWard($wardRows, 'nursing_productivity', true),
                'worst_nursing'           => $this->extremumWard($wardRows, 'nursing_productivity', false),
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $wardRows
     * @return array{ward_name: string, value: float}|null
     */
    private function extremumWard(array $wardRows, string $field, bool $max): ?array
    {
        $best     = null;
        $bestName = null;

        foreach ($wardRows as $row) {
            $value = $row[$field] ?? null;
            if ($value === null) {
                continue;
            }
            $float = (float) $value;
            if ($best === null || ($max && $float > $best) || (! $max && $float < $best)) {
                $best     = $float;
                $bestName = (string) $row['ward_name'];
            }
        }

        if ($best === null || $bestName === null) {
            return null;
        }

        return ['ward_name' => $bestName, 'value' => round($best, 2)];
    }

    private function censusTotalPatients(array $row): int
    {
        return (int)($row['total_patients'] ?? $row['total_remaining'] ?? 0);
    }
}
