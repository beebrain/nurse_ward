<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\IpdApiFetchLogModel;
use CodeIgniter\HTTP\ResponseInterface;

class HosxpLogController extends BaseController
{
    public function index()
    {
        return view('admin/hosxp_logs/index', [
            'title' => 'บันทึกดิบ HOSxP (API)',
        ]);
    }

    public function data(): ResponseInterface
    {
        $limit    = min(200, max(1, (int) ($this->request->getGet('limit') ?? 50)));
        $dateFrom = trim((string) ($this->request->getGet('date_from') ?? ''));
        $dateTo   = trim((string) ($this->request->getGet('date_to') ?? ''));

        $model = new IpdApiFetchLogModel();
        $rows  = $model->getRecentLogs($limit, $dateFrom ?: null, $dateTo ?: null);

        foreach ($rows as &$row) {
            $row['fetched_at_fmt']  = $this->formatThaiDatetime($row['fetched_at'] ?? '');
            $row['record_time_fmt'] = $this->formatThaiDatetime($row['record_time'] ?? '');
            $row['success_label']   = ! empty($row['success']) ? 'สำเร็จ' : 'ล้มเหลว';
        }
        unset($row);

        return $this->response->setJSON([
            'success' => true,
            'rows'    => $rows,
        ]);
    }

    public function detail(int $id): ResponseInterface
    {
        $model = new IpdApiFetchLogModel();
        $log   = $model->getLogWithPayload($id);

        if (! $log) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'ไม่พบรายการ',
            ]);
        }

        $payload = json_decode($log['payload_json'] ?? '{}', true);
        if (! is_array($payload)) {
            $payload = ['raw' => $log['payload_json']];
        }

        return $this->response->setJSON([
            'success' => true,
            'log'     => [
                'id'             => $log['id'],
                'fetched_at'     => $this->formatThaiDatetime($log['fetched_at'] ?? ''),
                'record_time'    => $this->formatThaiDatetime($log['record_time'] ?? ''),
                'success'        => (bool) $log['success'],
                'wards_saved'    => (int) $log['wards_saved'],
                'patient_total'  => (int) $log['patient_total'],
                'error_message'  => $log['error_message'],
            ],
            'payload' => $payload,
        ]);
    }

    private function formatThaiDatetime(string $value): string
    {
        if ($value === '') {
            return '-';
        }

        $ts = strtotime($value);

        return $ts ? date('d/m/Y H:i', $ts) : $value;
    }
}
