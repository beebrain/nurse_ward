<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CensusModel;
use App\Models\CensusQualityIndicatorModel;
use App\Models\WardModel;

class CensusController extends BaseController
{
    protected $censusModel;
    protected $qiModel;
    protected $wardModel;

    public function __construct()
    {
        $this->censusModel = new CensusModel();
        $this->qiModel     = new CensusQualityIndicatorModel();
        $this->wardModel   = new WardModel();
    }

    public function index()
    {
        return redirect()->to('census/new');
    }

    public function create()
    {
        $data = [
            'wards' => $this->wardModel->getActiveWithDepartment(),
            'title' => 'บันทึกยอดผู้ป่วยรายวัน',
        ];
        return view('census/create', $data);
    }

    public function store()
    {
        $censusData = $this->buildCensusData($this->request->getPost());
        if ($censusData === null) {
            return redirect()->back()->withInput()->with('error', 'กรุณากรอกข้อมูล Ward / วันที่ / Shift ให้ครบ');
        }

        $existing = $this->censusModel->findByShift(
            (int)$censusData['ward_id'],
            $censusData['record_date'],
            $censusData['shift']
        );

        $censusId = null;
        try {
            if ($existing) {
                $this->censusModel->update($existing['id'], $censusData);
                $censusId = $existing['id'];
            } else {
                $censusId = $this->censusModel->insert($censusData, true);
            }
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'บันทึกไม่สำเร็จ: ' . $e->getMessage());
        }

        // Save quality indicators
        $qi = $this->request->getPost('qi');
        if ($qi && is_array($qi)) {
            $this->qiModel->upsertForCensus((int)$censusId, $this->filterQiData($qi));
        }

        // Recalculate productivity when Afternoon shift is saved
        if ($censusData['shift'] === 'Afternoon') {
            $this->censusModel->recalculateProductivity(
                (int)$censusData['ward_id'],
                $censusData['record_date']
            );
        }

        return redirect()->to('census/new')
                         ->with('message', 'บันทึกข้อมูลเรียบร้อยแล้ว');
    }

    public function autosave()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Invalid request']);
        }

        $post = $this->request->getPost();

        if (empty($post['ward_id']) || empty($post['record_date']) || empty($post['shift'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'ข้อมูลไม่ครบ']);
        }

        $censusData = $this->buildCensusData($post);
        if ($censusData === null) {
            return $this->response->setJSON(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
        }

        $existing = $this->censusModel->findByShift(
            (int)$censusData['ward_id'],
            $censusData['record_date'],
            $censusData['shift']
        );

        $censusId = null;
        try {
            if ($existing) {
                $this->censusModel->update($existing['id'], $censusData);
                $censusId = $existing['id'];
            } else {
                $censusId = $this->censusModel->insert($censusData, true);
            }

            // Save QI
            $qi = $post['qi'] ?? [];
            if ($censusId && is_array($qi) && array_filter($qi)) {
                $this->qiModel->upsertForCensus((int)$censusId, $this->filterQiData($qi));
            }

            // Productivity (Afternoon only)
            if ($censusData['shift'] === 'Afternoon') {
                $this->censusModel->recalculateProductivity(
                    (int)$censusData['ward_id'],
                    $censusData['record_date']
                );
            }

            return $this->response->setJSON(['success' => true, 'census_id' => $censusId]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function history()
    {
        if (! auth()->loggedIn()) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $wardId   = $this->request->getGet('ward_id');
        $dateFrom = (string)($this->request->getGet('date_from') ?? date('Y-m-d', strtotime('-30 days')));
        $dateTo   = (string)($this->request->getGet('date_to')   ?? date('Y-m-d'));

        if ($dateFrom > $dateTo) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'ช่วงวันที่ไม่ถูกต้อง']);
        }

        $wardIdInt = ($wardId !== null && $wardId !== '') ? max(1, (int)$wardId) : null;

        $shiftLabels = [
            'Night'     => 'ดึก',
            'Morning'   => 'เช้า',
            'Afternoon' => 'บ่าย',
        ];

        $rows = $this->censusModel->getHistoryForList($wardIdInt, $dateFrom, $dateTo);

        $out = [];
        foreach ($rows as $row) {
            $shift = $row['shift'] ?? '';
            $out[] = [
                'id'                   => (int)$row['id'],
                'ward_id'              => (int)$row['ward_id'],
                'ward_name'            => ($row['ward_code'] ? $row['ward_code'] . ' ' : '') . ($row['ward_name'] ?? '—'),
                'record_date'          => $row['record_date'],
                'shift'                => $shift,
                'shift_label'          => $shiftLabels[$shift] ?? $shift,
                'patients_level_5'     => (int)$row['patients_level_5'],
                'patients_level_4'     => (int)$row['patients_level_4'],
                'patients_level_3'     => (int)$row['patients_level_3'],
                'patients_level_2'     => (int)$row['patients_level_2'],
                'patients_level_1'     => (int)$row['patients_level_1'],
                'total_patients'       => (int)$row['total_patients'],
                'admissions'           => (int)$row['admissions'],
                'discharges'           => (int)$row['discharges'],
                'transfers_in'         => (int)$row['transfers_in'],
                'transfers_out'        => (int)$row['transfers_out'],
                'deaths'               => (int)$row['deaths'],
                'nurses_rn'            => (int)$row['nurses_rn'],
                'nurses_tn'            => (int)$row['nurses_tn'],
                'nurses_pn'            => (int)$row['nurses_pn'],
                'working_hours'        => $row['working_hours'],
                'required_care_hours'  => $row['required_care_hours'],
                'productivity'         => $row['productivity'] !== null ? round((float)$row['productivity'], 2) : null,
                'recorder_username'    => $row['recorder_username'] ?? '—',
                'updated_at'           => $row['updated_at'],
            ];
        }

        return $this->response->setJSON(['rows' => $out]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function buildCensusData(array $post): ?array
    {
        $wardId = $post['ward_id']     ?? '';
        $date   = $post['record_date'] ?? '';
        $shift  = $post['shift']       ?? '';

        if (! $wardId || ! $date || ! $shift) {
            return null;
        }

        $data = [
            'ward_id'          => (int)$wardId,
            'record_date'      => $date,
            'shift'            => $shift,
            'patients_level_5' => (int)($post['patients_level_5'] ?? 0),
            'patients_level_4' => (int)($post['patients_level_4'] ?? 0),
            'patients_level_3' => (int)($post['patients_level_3'] ?? 0),
            'patients_level_2' => (int)($post['patients_level_2'] ?? 0),
            'patients_level_1' => (int)($post['patients_level_1'] ?? 0),
            'admissions'       => (int)($post['admissions']   ?? 0),
            'discharges'       => (int)($post['discharges']   ?? 0),
            'transfers_in'     => (int)($post['transfers_in'] ?? 0),
            'transfers_out'    => (int)($post['transfers_out'] ?? 0),
            'deaths'           => (int)($post['deaths']       ?? 0),
            'nurses_hw'        => (int)($post['nurses_hw']   ?? 0),
            'nurses_rn'        => (int)($post['nurses_rn']   ?? 0),
            'nurses_tn'        => (int)($post['nurses_tn']   ?? 0),
            'nurses_pn'        => (int)($post['nurses_pn']   ?? 0),
            'nurses_aide'      => (int)($post['nurses_aide'] ?? 0),
            'nurses_ward'      => (int)($post['nurses_ward'] ?? 0),
            'notes'            => $post['notes'] ?? null,
            'created_by'       => auth()->id(),
        ];

        $this->censusModel->computeDerived($data);
        return $data;
    }

    private function filterQiData(array $qi): array
    {
        $allowed = [
            'hai_vap', 'hai_hap', 'hai_uti', 'hai_cauti',
            'hai_clabsi', 'hai_ssi', 'hai_bsi', 'hai_mdr',
            'new_sepsis', 'end_of_life', 'palliative_care',
            'critical_care_support', 'high_flow_oxygen',
        ];
        $out = [];
        foreach ($allowed as $key) {
            $out[$key] = (int)($qi[$key] ?? 0);
        }
        return $out;
    }
}
