<?php

namespace App\Controllers;

use App\Models\WardModel;
use App\Services\ReportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends BaseController
{
    protected $reportService;
    protected $wardModel;

    public function __construct()
    {
        $this->reportService = new ReportService();
        $this->wardModel = new WardModel();
    }

    /**
     * Display the monthly summary report page.
     */
    public function monthly()
    {
        $data = [
            'title' => 'Monthly Summary Report',
            'wards' => $this->wardModel->where('is_active', true)->findAll(),
            'current_month' => date('n'),
            'current_year' => date('Y'),
        ];
        
        return view('reports/monthly_summary', $data);
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
        $data = [
            'title' => 'Interactive Dashboard',
            'wards' => $this->wardModel->where('is_active', true)->findAll(),
            'current_month' => date('n'),
            'current_year' => date('Y'),
        ];

        return view('reports/dashboard', $data);
    }

    /**
     * AJAX endpoint returning chart datasets.
     */
    public function dashboardData()
    {
        $wardId = (int) $this->request->getGet('ward_id');
        $month = (int) $this->request->getGet('month');
        $year = (int) $this->request->getGet('year');

        if ($wardId <= 0 || $month <= 0 || $month > 12 || $year <= 0) {
            return $this->response->setJSON(['error' => 'Invalid parameters'])->setStatusCode(400);
        }

        $ward = $this->wardModel->find($wardId);
        if (!$ward) {
            return $this->response->setJSON(['error' => 'Ward not found'])->setStatusCode(404);
        }

        $trend = $this->reportService->getYearlyTrend($wardId, $year);
        $comparison = $this->reportService->getWardComparison($month, $year);

        return $this->response->setJSON([
            'selected_ward' => $ward['name'],
            'year' => $year,
            'month' => $month,
            'trend' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                'patient_days' => $trend,
            ],
            'comparison' => $comparison,
        ]);
    }

    /**
     * Display daily table summary for selected ward/month/year.
     */
    public function dailySummary()
    {
        $wards = $this->wardModel->where('is_active', true)->findAll();
        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');

        $wardId = (int) ($this->request->getGet('ward_id') ?? 0);
        $month = (int) ($this->request->getGet('month') ?? $currentMonth);
        $year = (int) ($this->request->getGet('year') ?? $currentYear);

        $rows = [];
        $selectedWard = null;
        if ($wardId > 0 && $month >= 1 && $month <= 12 && $year > 0) {
            $selectedWard = $this->wardModel->find($wardId);
            if ($selectedWard) {
                $rows = $this->reportService->getDailySummaryTable($wardId, $month, $year);
            }
        }

        return view('reports/daily_summary', [
            'title' => 'Daily Summary Table',
            'wards' => $wards,
            'current_month' => $month,
            'current_year' => $year,
            'selected_ward_id' => $wardId,
            'selected_ward' => $selectedWard,
            'rows' => $rows,
        ]);
    }
}
