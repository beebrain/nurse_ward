<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DepartmentModel;
use App\Models\IpdApiFetchLogModel;
use App\Models\WardModel;
use App\Services\HosxpPayloadParser;
use App\Services\HosxpWardMappingService;

class WardController extends BaseController
{
    protected $wardModel;
    protected $departmentModel;

    public function __construct()
    {
        $this->wardModel       = new WardModel();
        $this->departmentModel = new DepartmentModel();
    }

    public function index()
    {
        $wards   = $this->wardModel->getAllWithDepartment();
        $mapping = (new HosxpWardMappingService())->annotateAdminWards($wards);

        $data = [
            'wards'           => $mapping['wards'],
            'mapping_summary' => $mapping['summary'],
            'title'           => 'จัดการ Ward',
        ];

        return view('admin/wards/index', $data);
    }

    public function create()
    {
        $data = [
            'title'          => 'เพิ่ม Ward ใหม่',
            'departments'    => $this->departmentModel->getActiveOrdered(),
            'api_ward_options' => $this->loadLatestApiWardOptions(),
        ];

        return view('admin/wards/create', $data);
    }

    public function store()
    {
        $rules = [
            'name'          => 'required|max_length[200]|min_length[2]',
            'code'          => 'permit_empty|max_length[30]',
            'department_id' => 'permit_empty|numeric',
            'total_beds'    => 'required|numeric|greater_than_equal_to[0]',
            'api_ward_code' => 'permit_empty|max_length[50]',
            'api_ward_name' => 'permit_empty|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $apiError = $this->validateApiMapping();
        if ($apiError !== null) {
            return redirect()->back()->withInput()->with('errors', ['api_ward_name' => $apiError]);
        }

        $this->wardModel->save([
            'name'          => $this->request->getPost('name'),
            'code'          => $this->request->getPost('code') ?: null,
            'department_id' => $this->request->getPost('department_id') ?: null,
            'total_beds'    => $this->request->getPost('total_beds'),
            'is_active'     => $this->request->getPost('is_active') ? true : false,
            'api_ward_code' => $this->normalizeApiField('api_ward_code'),
            'api_ward_name' => $this->normalizeApiField('api_ward_name'),
        ]);

        return redirect()->to('admin/wards')->with('message', 'Ward created successfully.');
    }

    public function edit($id = null)
    {
        $ward = $this->wardModel->find($id);

        if (! $ward) {
            return redirect()->to('admin/wards')->with('error', 'Ward not found.');
        }

        $data = [
            'ward'             => $ward,
            'title'            => 'แก้ไข Ward',
            'departments'      => $this->departmentModel->getActiveOrdered(),
            'api_ward_options' => $this->loadLatestApiWardOptions(),
        ];

        return view('admin/wards/edit', $data);
    }

    public function update($id = null)
    {
        $rules = [
            'name'          => 'required|max_length[200]|min_length[2]',
            'code'          => 'permit_empty|max_length[30]',
            'department_id' => 'permit_empty|numeric',
            'total_beds'    => 'required|numeric|greater_than_equal_to[0]',
            'api_ward_code' => 'permit_empty|max_length[50]',
            'api_ward_name' => 'permit_empty|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $apiError = $this->validateApiMapping((int) $id);
        if ($apiError !== null) {
            return redirect()->back()->withInput()->with('errors', ['api_ward_name' => $apiError]);
        }

        $this->wardModel->update($id, [
            'name'          => $this->request->getPost('name'),
            'code'          => $this->request->getPost('code') ?: null,
            'department_id' => $this->request->getPost('department_id') ?: null,
            'total_beds'    => $this->request->getPost('total_beds'),
            'is_active'     => $this->request->getPost('is_active') ? true : false,
            'api_ward_code' => $this->normalizeApiField('api_ward_code'),
            'api_ward_name' => $this->normalizeApiField('api_ward_name'),
        ]);

        return redirect()->to('admin/wards')->with('message', 'Ward updated successfully.');
    }

    public function delete($id = null)
    {
        if ($this->wardModel->delete($id)) {
            return redirect()->to('admin/wards')->with('message', 'Ward deleted successfully.');
        }

        return redirect()->to('admin/wards')->with('error', 'Failed to delete ward.');
    }

    /**
     * @return list<array{ward: string, ward_name: string}>
     */
    private function loadLatestApiWardOptions(): array
    {
        $log = (new IpdApiFetchLogModel())->getLatestSuccessfulWithPayload();
        if ($log === null) {
            return [];
        }

        $payload = json_decode($log['payload_json'] ?? '{}', true);
        if (! is_array($payload)) {
            return [];
        }

        $tables = (new HosxpPayloadParser())->parse($payload);

        return (new HosxpWardMappingService())->uniqueApiWardsFromMerged($tables['merged']);
    }

    private function validateApiMapping(?int $excludeWardId = null): ?string
    {
        $result = (new HosxpWardMappingService())->validateApiMappingFields(
            $this->request->getPost('api_ward_code'),
            $this->request->getPost('api_ward_name'),
            $excludeWardId
        );

        return $result['valid'] ? null : $result['message'];
    }

    private function normalizeApiField(string $field): ?string
    {
        $value = trim((string) ($this->request->getPost($field) ?? ''));

        return $value === '' ? null : $value;
    }
}
