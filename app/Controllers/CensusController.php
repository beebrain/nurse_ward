<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CensusModel;
use App\Models\WardModel;

class CensusController extends BaseController
{
    protected $censusModel;
    protected $wardModel;

    public function __construct()
    {
        $this->censusModel = new CensusModel();
        $this->wardModel = new WardModel();
    }

    public function index()
    {
        // Redirect to create for now, or show history
        return redirect()->to('census/new');
    }

    public function create()
    {
        $data = [
            'wards' => $this->wardModel->where('is_active', true)->findAll(),
            'title' => 'Daily Census Entry',
        ];

        return view('census/create', $data);
    }

    public function store()
    {
        $rules = $this->censusModel->getValidationRules();

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost();
        $data['created_by'] = auth()->id();

        try {
            $this->censusModel->insert($data);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to save record. Possible duplicate entry for this ward, date, and shift.');
        }

        return redirect()->to('census/new')->with('message', 'Census record saved successfully.');
    }

    public function autosave()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Invalid request type.']);
        }

        $rules = $this->censusModel->getValidationRules();
        // For autosave, we might only have partial data, but ward, date, and shift are required for identity
        $identityRules = [
            'ward_id'     => 'required|numeric',
            'record_date' => 'required|valid_date',
            'shift'       => 'required|in_list[Morning,Afternoon,Night]',
        ];

        if (!$this->validate($identityRules)) {
            return $this->response->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $data = $this->request->getPost();
        $data['created_by'] = auth()->id();

        // Check if record exists
        $existing = $this->censusModel->where([
            'ward_id'     => $data['ward_id'],
            'record_date' => $data['record_date'],
            'shift'       => $data['shift'],
        ])->first();

        try {
            if ($existing) {
                $this->censusModel->update($existing['id'], $data);
            } else {
                $this->censusModel->insert($data);
            }
            return $this->response->setJSON(['success' => true, 'message' => 'Auto-saved successfully.']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * JSON list of census history for nurses (ward, metrics, recorder, timestamps).
     */
    public function history()
    {
        if (! auth()->loggedIn()) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $wardId   = $this->request->getGet('ward_id');
        $dateFrom = (string) ($this->request->getGet('date_from') ?? date('Y-m-d', strtotime('-30 days')));
        $dateTo   = (string) ($this->request->getGet('date_to') ?? date('Y-m-d'));

        if (strtotime($dateFrom) === false || strtotime($dateTo) === false) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'วันที่ไม่ถูกต้อง']);
        }

        if ($dateFrom > $dateTo) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'ช่วงวันที่ไม่ถูกต้อง']);
        }

        $wardIdInt = null;
        if ($wardId !== null && $wardId !== '') {
            $wardIdInt = (int) $wardId;
            if ($wardIdInt < 1) {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'แผนกไม่ถูกต้อง']);
            }
        }

        $shiftLabels = [
            'Morning'   => 'เช้า',
            'Afternoon' => 'บ่าย',
            'Night'     => 'ดึก',
        ];

        $rows = $this->censusModel->getHistoryForList($wardIdInt, $dateFrom, $dateTo);

        $out = [];
        foreach ($rows as $row) {
            $shift = $row['shift'] ?? '';
            $out[] = [
                'id'                 => (int) $row['id'],
                'ward_id'            => (int) $row['ward_id'],
                'ward_name'          => $row['ward_name'] ?? '—',
                'record_date'        => $row['record_date'],
                'shift'              => $shift,
                'shift_label'        => $shiftLabels[$shift] ?? $shift,
                'admissions'         => (int) $row['admissions'],
                'discharges'         => (int) $row['discharges'],
                'transfers_in'       => (int) $row['transfers_in'],
                'transfers_out'      => (int) $row['transfers_out'],
                'deaths'             => (int) $row['deaths'],
                'total_remaining'    => (int) $row['total_remaining'],
                'recorder_username'  => $row['recorder_username'] ?? '—',
                'created_at'         => $row['created_at'],
                'updated_at'         => $row['updated_at'],
            ];
        }

        return $this->response->setJSON(['rows' => $out]);
    }
}
