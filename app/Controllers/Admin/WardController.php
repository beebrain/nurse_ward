<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DepartmentModel;
use App\Models\IpdApiFetchLogModel;
use App\Models\WardApiAliasModel;
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
        $wards          = $this->wardModel->getAllWithDepartment();
        $aliasesByWard  = (new WardApiAliasModel())->getAliasesGroupedByWardId();
        $mapping        = (new HosxpWardMappingService())->annotateAdminWards($wards, $aliasesByWard);

        $data = [
            'wards'           => $mapping['wards'],
            'mapping_summary' => $mapping['summary'],
            'title'           => 'จัดการ Ward',
        ];

        return view('admin/wards/index', $data);
    }

    public function create()
    {
        $data = array_merge(
            $this->apiMappingFormData(null),
            [
                'title'       => 'เพิ่ม Ward ใหม่',
                'departments' => $this->departmentModel->getActiveOrdered(),
                'ward'        => [],
            ]
        );

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

        $wardId = (int) $this->wardModel->getInsertID();
        $this->saveApiAliases(
            $wardId,
            $this->normalizeApiField('api_ward_code') ?? '',
            $this->normalizeApiField('api_ward_name') ?? ''
        );

        return redirect()->to('admin/wards')->with('message', 'Ward created successfully.');
    }

    public function edit($id = null)
    {
        $ward = $this->wardModel->find($id);

        if (! $ward) {
            return redirect()->to('admin/wards')->with('error', 'Ward not found.');
        }

        $data = array_merge(
            $this->apiMappingFormData((int) $id),
            [
                'ward'        => $ward,
                'title'       => 'แก้ไข Ward',
                'departments' => $this->departmentModel->getActiveOrdered(),
            ]
        );

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

        $this->saveApiAliases(
            (int) $id,
            $this->normalizeApiField('api_ward_code') ?? '',
            $this->normalizeApiField('api_ward_name') ?? ''
        );

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
     * @return array{
     *     api_ward_options: list<array{ward: string, ward_name: string}>,
     *     ward_aliases: list<string>,
     *     used_api_names: array<string, true>
     * }
     */
    private function apiMappingFormData(?int $wardId): array
    {
        $aliases = $wardId !== null
            ? (new WardApiAliasModel())->where('ward_id', $wardId)->findAll()
            : [];

        return [
            'api_ward_options' => $this->loadLatestApiWardOptions(),
            'ward_aliases'     => array_map(
                static fn ($a) => (string) ($a['api_ward_name'] ?? ''),
                $aliases
            ),
            'used_api_names'   => $this->getUsedApiNamesExcept($wardId),
        ];
    }

    /**
     * @return array<string, true>
     */
    private function getUsedApiNamesExcept(?int $excludeWardId): array
    {
        $used = [];
        $wards = $this->wardModel->select('id, api_ward_name')->where('is_active', 1)->findAll();
        foreach ($wards as $w) {
            if ($excludeWardId !== null && (int) $w['id'] === $excludeWardId) {
                continue;
            }
            $name = trim((string) ($w['api_ward_name'] ?? ''));
            if ($name !== '') {
                $used[$name] = true;
            }
        }
        foreach ((new WardApiAliasModel())->getNameToWardIdMap($excludeWardId) as $name => $_wid) {
            $used[$name] = true;
        }

        return $used;
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
        $aliases = $this->request->getPost('api_aliases');
        if (! is_array($aliases)) {
            $aliases = [];
        }

        $result = (new HosxpWardMappingService())->validateApiMappingFields(
            $this->request->getPost('api_ward_code'),
            $this->request->getPost('api_ward_name'),
            $excludeWardId,
            $aliases
        );

        return $result['valid'] ? null : $result['message'];
    }

    /**
     * @param list<string>|null $postedAliases
     */
    private function saveApiAliases(int $wardId, string $apiWardCode, string $primaryName): void
    {
        $posted = $this->request->getPost('api_aliases');
        if (! is_array($posted)) {
            $posted = [];
        }

        $names = [];
        foreach ($posted as $name) {
            $name = trim((string) $name);
            if ($name === '' || $name === $primaryName) {
                continue;
            }
            $names[] = $name;
        }

        (new WardApiAliasModel())->syncForWard($wardId, $apiWardCode, $names);
    }

    private function normalizeApiField(string $field): ?string
    {
        $value = trim((string) ($this->request->getPost($field) ?? ''));

        return $value === '' ? null : $value;
    }
}
