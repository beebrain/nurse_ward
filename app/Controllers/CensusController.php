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
}
