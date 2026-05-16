<?php

namespace App\Controllers;

use App\Models\UserWardModel;
use App\Models\WardModel;
use App\Services\ProductivityAggregateService;
use App\Services\ReportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
     * Legacy route — monthly single-ward report merged into dashboard.
     */
    public function monthly()
    {
        return redirect()->to(base_url('reports/dashboard'))
            ->with('message', 'สรุปรายเดือนถูกรวมเข้าแดชบอร์ดแล้ว — เปรียบเทียบทุกแผนกได้ที่แท็บภาพรวมรายเดือน');
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
     * AJAX endpoint to fetch report data.
     */
    public function getData()
    {
        $wardId = $this->request->getGet('ward_id');
        $month = $this->request->getGet('month');
        $year = $this->request->getGet('year');

        if (!$wardId || !$month || !$year) {
            return $this->response->setJSON(['error' => 'Missing parameters'])->setStatusCode(400);
        }

        $reportData = $this->reportService->getMonthlyReport((int)$wardId, (int)$month, (int)$year);
        
        return $this->response->setJSON($reportData);
    }

    /**
     * Export monthly report to Excel.
     */
    public function export()
    {
        $wardId = $this->request->getGet('ward_id');
        $month = (int)$this->request->getGet('month');
        $year = (int)$this->request->getGet('year');

        if (!$wardId || !$month || !$year) {
            return redirect()->back()->with('error', 'Missing parameters for export');
        }

        $ward = $this->wardModel->find($wardId);
        if (!$ward) {
            return redirect()->back()->with('error', 'Ward not found');
        }

        $reportData = $this->reportService->getMonthlyReport((int)$wardId, $month, $year);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title
        $sheet->setCellValue('A1', 'Monthly Summary Report');
        $sheet->setCellValue('A2', 'Ward: ' . $ward['name']);
        $sheet->setCellValue('A3', 'Month: ' . date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year);
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Styling
        $headerStyle = [
            'font' => ['bold' => true],
            'borders' => [
                'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EEEEEE'],
            ],
        ];

        // Core Metrics Table
        $sheet->setCellValue('A5', 'Metric');
        $sheet->setCellValue('B5', 'Value');
        $sheet->getStyle('A5:B5')->applyFromArray($headerStyle);

        $sheet->setCellValue('A6', 'Total Patient Days');
        $sheet->setCellValue('B6', $reportData['patient_days']);
        $sheet->setCellValue('A7', 'Ward Beds');
        $sheet->setCellValue('B7', $reportData['ward_beds']);
        $sheet->setCellValue('A8', 'Days in Month');
        $sheet->setCellValue('B8', $reportData['days_in_month']);
        $sheet->setCellValue('A9', 'Productivity (%)');
        $sheet->setCellValue('B9', round($reportData['productivity'], 2) . '%');

        // Breakdown Table
        $sheet->setCellValue('A11', 'Category');
        $sheet->setCellValue('B11', 'Total');
        $sheet->getStyle('A11:B11')->applyFromArray($headerStyle);

        $sheet->setCellValue('A12', 'Admissions');
        $sheet->setCellValue('B12', $reportData['admissions']);
        $sheet->setCellValue('A13', 'Discharges');
        $sheet->setCellValue('B13', $reportData['discharges']);
        $sheet->setCellValue('A14', 'Transfers In');
        $sheet->setCellValue('B14', $reportData['transfers_in']);
        $sheet->setCellValue('A15', 'Transfers Out');
        $sheet->setCellValue('B15', $reportData['transfers_out']);
        $sheet->setCellValue('A16', 'Deaths');
        $sheet->setCellValue('B16', $reportData['deaths']);

        // Auto-size columns
        foreach (range('A', 'B') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Monthly_Report_' . str_replace(' ', '_', $ward['name']) . '_' . $year . '_' . $month . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
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

    private function thaiWeekdayLabel(string $date): string
    {
        $labels = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];

        return $labels[(int) date('w', strtotime($date))] ?? '';
    }

    private function thaiDateShort(string $date): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }

        $months = [
            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
            5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
            9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
        ];

        $day           = (int) date('j', $timestamp);
        $month         = $months[(int) date('n', $timestamp)] ?? date('m', $timestamp);
        $thaiYearShort = ((int) date('Y', $timestamp) + 543) % 100;

        return sprintf('%d %s %02d', $day, $month, $thaiYearShort);
    }
}
