<?php

namespace App\Controllers;

use App\Models\WardModel;
use App\Services\ReportService;

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
}
