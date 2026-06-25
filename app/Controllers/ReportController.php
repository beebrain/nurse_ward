<?php

namespace App\Controllers;

use App\Models\UserWardModel;
use App\Models\WardModel;
use App\Services\ProductivityAggregateService;
use App\Services\ReportService;

class ReportController extends BaseController
{
    protected $reportService;
    protected $wardModel;
    protected $userWardModel;

    public function __construct()
    {
        $this->reportService = new ReportService();
        $this->wardModel = new WardModel();
        $this->userWardModel = new UserWardModel();
    }

    /**
     * Display read-only nurse ward assignment report.
     */
    public function userWards()
    {
        return view('reports/user_wards', [
            'title' => 'รายงานผู้รับผิดชอบแผนก',
            'assignments' => $this->userWardModel->getNurseAssignmentReport(),
        ]);
    }

    /**
     * Display dashboard page for trend visualizations.
     */
    public function dashboard()
    {
        $user = auth()->user();

        return view('reports/dashboard', [
            'title'         => 'แดชบอร์ดประสิทธิภาพ',
            'isSuperAdmin'  => $user && $user->inGroup('superadmin'),
            'current_month' => (int) date('n'),
            'current_year'  => (int) date('Y'),
            'snapshot'      => $this->reportService->getExecutiveSnapshot(),
        ]);
    }

    /**
     * AJAX: monthly comparison across all wards (superadmin only).
     */
    public function dashboardData()
    {
        $user = auth()->user();
        if (! $user || ! $user->inGroup('superadmin')) {
            return $this->response->setJSON(['error' => 'เฉพาะ Super Admin เท่านั้น'])->setStatusCode(403);
        }

        $month = (int) $this->request->getGet('month');
        $year  = (int) $this->request->getGet('year');

        if ($month <= 0 || $month > 12 || $year <= 0) {
            return $this->response->setJSON(['error' => 'Invalid parameters'])->setStatusCode(400);
        }

        $monthLabels = $this->reportService->getThaiMonthShortLabels();
        $full        = $this->reportService->getFullWardComparison($month, $year);
        $wards       = $this->wardModel->where('is_active', true)->orderBy('name', 'ASC')->findAll();

        $trendSeries   = $this->reportService->getYearlyTrendAllWards($year);
        $occTrend      = $this->reportService->getYearlyProductivityTrendAllWards($year);
        $nursingAgg    = new ProductivityAggregateService();
        $nurseTrend    = $nursingAgg->getYearlyNursingTrendAllWards($year, $wards);

        $wardLabels = array_column($full['wards'], 'ward_name');
        $occupancyBar = array_column($full['wards'], 'occupancy_productivity');
        $nursingBar   = array_map(
            static fn ($w) => $w['nursing_productivity'] ?? 0,
            $full['wards']
        );

        return $this->response->setJSON([
            'year'    => $year,
            'month'   => $month,
            'summary' => $full['summary'],
            'wards'   => $full['wards'],
            'trend'   => [
                'labels'   => $monthLabels,
                'datasets' => $this->reportService->buildPatientDayTrendDatasets($trendSeries),
            ],
            'occupancy_trend' => [
                'labels'   => $monthLabels,
                'datasets' => $this->reportService->buildProductivityTrendDatasets($occTrend),
            ],
            'nursing_trend' => [
                'labels'   => $monthLabels,
                'datasets' => $this->reportService->buildProductivityTrendDatasets($nurseTrend),
            ],
            'occupancy_comparison' => [
                'labels' => $wardLabels,
                'values' => $occupancyBar,
            ],
            'nursing_comparison' => [
                'labels' => $wardLabels,
                'values' => $nursingBar,
            ],
            'department_comparison' => $this->reportService->getDepartmentProductivity($month, $year),
        ]);
    }

    /**
     * Daily patient movement matrix — all wards, no ward picker.
     */
    public function dailySummary()
    {
        return view('reports/daily_summary', [
            'title'         => 'สรุปรายวัน (ทุกแผนก)',
            'wards'         => $this->wardModel->where('is_active', true)->orderBy('name', 'ASC')->findAll(),
            'current_month' => (int) date('n'),
            'current_year'  => (int) date('Y'),
        ]);
    }

    /**
     * AJAX: daily matrix for all wards.
     */
    public function dailySummaryData()
    {
        $month = (int) $this->request->getGet('month');
        $year  = (int) $this->request->getGet('year');

        if ($month < 1 || $month > 12 || $year <= 0) {
            return $this->response->setJSON(['error' => 'ตัวกรองไม่ถูกต้อง'])->setStatusCode(400);
        }

        $wards = $this->wardModel->where('is_active', true)->orderBy('name', 'ASC')->findAll();
        if ($wards === []) {
            return $this->response->setJSON(['error' => 'ไม่มีแผนกที่เปิดใช้งาน'])->setStatusCode(403);
        }

        $matrix = $this->reportService->getAllWardsDailyMatrix($wards, $month, $year);

        $days = [];
        foreach ($matrix['days'] as $day) {
            $date = (string) $day['date'];
            $days[] = array_merge($day, [
                'day_label'     => $this->thaiDateShort($date),
                'weekday_label' => $this->thaiWeekdayLabel($date),
            ]);
        }

        return $this->response->setJSON([
            'month'   => $month,
            'year'    => $year,
            'wards'   => $matrix['wards'],
            'days'    => $days,
            'summary' => $matrix['summary'],
        ]);
    }

    /**
     * Ward-specific patient behavior dashboard.
     */
    public function behaviorDashboard()
    {
        $user = auth()->user();
        
        $assignedWards = [];
        if ($user && $user->inGroup('nurse') && ! $user->inGroup('superadmin') && ! $user->inGroup('manager')) {
            $assignedIds = array_map('intval', $this->userWardModel->getWardIdsForUser((int)$user->id));
            $assignedWards = array_values(array_filter($this->wardModel->getActiveWithDepartment(), fn($w) => in_array((int)$w['id'], $assignedIds, true)));
        } else {
            $assignedWards = $this->wardModel->getActiveWithDepartment();
        }

        return view('reports/behavior_dashboard', [
            'title' => 'แดชบอร์ดพฤติกรรมคนไข้',
            'wards' => $assignedWards,
            'current_month' => (int) date('n'),
            'current_year' => (int) date('Y'),
        ]);
    }

    /**
     * AJAX: behavior dashboard data.
     */
    public function behaviorDashboardData()
    {
        $wardId = (int) $this->request->getGet('ward_id');
        $month = (int) $this->request->getGet('month');
        $year = (int) $this->request->getGet('year');

        if ($wardId <= 0 || $month <= 0 || $month > 12 || $year <= 0) {
            return $this->response->setJSON(['error' => 'Invalid parameters'])->setStatusCode(400);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('hourly_patient_census');
        $builder->where('ward_id', $wardId);
        $builder->where('YEAR(record_time)', $year);
        $builder->where('MONTH(record_time)', $month);
        $builder->orderBy('record_time', 'ASC');
        $results    = $builder->get()->getResultArray();
        $hourlyModel = new \App\Models\HourlyCensusModel();
        $aggregated  = $hourlyModel->aggregateTimelineByRecordTime($results);

        $daily = [];
        foreach ($aggregated as $row) {
            $date = date('Y-m-d', strtotime($row['record_time']));
            if (! isset($daily[$date])) {
                $daily[$date] = [
                    'date'           => $date,
                    'patient_count'  => [],
                    'admissions'     => 0,
                    'discharges'     => 0,
                    'transfers_in'   => 0,
                    'transfers_out'  => 0,
                    'deaths'         => 0,
                ];
            }
            $daily[$date]['patient_count'][] = (int) $row['patient_count'];
            $daily[$date]['admissions']    = max($daily[$date]['admissions'], (int) $row['admissions_today']);
            $daily[$date]['discharges']    = max($daily[$date]['discharges'], (int) $row['discharges_today']);
            $daily[$date]['transfers_in']  = max($daily[$date]['transfers_in'], (int) $row['moves_in_today']);
            $daily[$date]['transfers_out'] = max($daily[$date]['transfers_out'], (int) $row['moves_out_today']);
            $daily[$date]['deaths']        = max($daily[$date]['deaths'], (int) $row['deaths_today']);
        }

        return $this->response->setJSON([
            'year' => $year,
            'month' => $month,
            'ward_id' => $wardId,
            'raw_data' => $results,
            'daily_summary' => array_values($daily)
        ]);
    }

    private function thaiWeekdayLabel(string $date): string
    {
        return thai_weekday_label($date);
    }

    private function thaiDateShort(string $date): string
    {
        return thai_date_short($date);
    }
}
