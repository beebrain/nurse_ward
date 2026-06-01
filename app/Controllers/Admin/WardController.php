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
        ]);

        $this->saveApiMappingFromRequest((int) $this->wardModel->getInsertID());

        return redirect()->to('admin/wards')->with('message', 'Ward created successfully.');
    }

    public function edit($id = null)
    {
        $ward = $this->wardModel->find($id);

        if (! $ward) {
            return redirect()->to('admin/wards')->with('error', 'Ward not found.');
        }

        $data = array_merge(
            $this->apiMappingFormData((int) $id, $ward),
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
        ]);

        $this->saveApiMappingFromRequest((int) $id);

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
     * @param array<string, mixed>|null $ward
     *
     * @return array{
     *     api_ward_options: list<array<string, mixed>>,
     *     ward_selected_names: list<string>,
     *     used_api_names: array<string, true>
     * }
     */
    private function apiMappingFormData(?int $wardId, ?array $ward = null): array
    {
        $selected = [];
        if ($ward !== null) {
            $primary = trim((string) ($ward['api_ward_name'] ?? ''));
            if ($primary !== '') {
                $selected[] = $primary;
            }
            $aliases = (new WardApiAliasModel())->where('ward_id', $wardId)->findAll();
            foreach ($aliases as $a) {
                $n = trim((string) ($a['api_ward_name'] ?? ''));
                if ($n !== '') {
                    $selected[] = $n;
                }
            }
        }
        $selected = array_values(array_unique($selected));

        if ($posted = old('api_ward_names')) {
            $selected = is_array($posted) ? array_values(array_unique(array_filter(array_map('trim', $posted)))) : $selected;
        } elseif (($text = old('api_ward_names_text')) !== null && trim((string) $text) !== '') {
            $parts    = preg_split('/\r\n|\r|\n/', (string) $text) ?: [];
            $selected = array_values(array_unique(array_filter(array_map('trim', $parts))));
        }

        return [
            'api_ward_options'    => $this->loadLatestApiWardOptions(),
            'ward_selected_names' => $selected,
            'used_api_names'      => $this->getUsedApiNamesExcept($wardId),
        ];
    }

    /**
     * @return list<string>
     */
    private function getPostedApiWardNames(): array
    {
        $out = [];

        $posted = $this->request->getPost('api_ward_names');
        if (is_array($posted)) {
            foreach ($posted as $name) {
                $name = trim((string) $name);
                if ($name !== '') {
                    $out[] = $name;
                }
            }
        }

        if ($out === []) {
            $legacyPrimary = trim((string) ($this->request->getPost('api_ward_name') ?? ''));
            if ($legacyPrimary !== '') {
                $out[] = $legacyPrimary;
            }
            $legacyAliases = $this->request->getPost('api_aliases');
            if (is_array($legacyAliases)) {
                foreach ($legacyAliases as $name) {
                    $name = trim((string) $name);
                    if ($name !== '') {
                        $out[] = $name;
                    }
                }
            }
        }

        if ($out !== []) {
            return array_values(array_unique($out));
        }

        $text = trim((string) ($this->request->getPost('api_ward_names_text') ?? ''));
        if ($text === '') {
            return [];
        }

        $parts = preg_split('/\r\n|\r|\n/', $text) ?: [];

        return array_values(array_unique(array_filter(array_map('trim', $parts))));
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
        $code  = trim((string) ($this->request->getPost('api_ward_code') ?? ''));
        $names = $this->getPostedApiWardNames();

        if ($code === '' && $names === []) {
            return null;
        }

        if ($code === '') {
            return 'ต้องเลือกรหัส ward จาก HOSxP';
        }

        if ($names === []) {
            return 'ติ๊กเลือกชื่อ API อย่างน้อย 1 ชื่อที่ต้องการรวม';
        }

        $result = (new HosxpWardMappingService())->validateApiMappingFields(
            $code,
            $names[0],
            $excludeWardId,
            array_slice($names, 1)
        );

        return $result['valid'] ? null : $result['message'];
    }

    private function saveApiMappingFromRequest(int $wardId): void
    {
        $code  = $this->normalizeApiField('api_ward_code') ?? '';
        $names = $this->getPostedApiWardNames();

        if ($code === '' && $names === []) {
            $this->wardModel->update($wardId, [
                'api_ward_code' => null,
                'api_ward_name' => null,
            ]);
            (new WardApiAliasModel())->syncForWard($wardId, '', []);

            return;
        }

        $primary = $names[0];
        $extras  = array_slice($names, 1);

        $this->wardModel->update($wardId, [
            'api_ward_code' => $code,
            'api_ward_name' => $primary,
        ]);

        (new WardApiAliasModel())->syncForWard($wardId, $code, $extras);
    }

    private function normalizeApiField(string $field): ?string
    {
        $value = trim((string) ($this->request->getPost($field) ?? ''));

        return $value === '' ? null : $value;
    }
}
