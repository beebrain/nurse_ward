<?php

namespace App\Services;

/**
 * จับคู่แผนกในฐานข้อมูลกับ ward จาก HOSxP API (logic เดียวกับ scripts/fetch_ipd_hourly.py)
 */
class HosxpWardMappingService
{
    /**
     * สรุปสถานะ mapping สำหรับรายการแผนกใน Admin (ไม่ต้องมี payload API)
     *
     * @param list<array<string, mixed>> $wards
     *
     * @return array{
     *     wards: list<array<string, mixed>>,
     *     summary: array{total: int, configured: int, missing: int, duplicate: int}
     * }
     */
    public function annotateAdminWards(array $wards): array
    {
        $duplicateNames = $this->findDuplicateApiNames($wards);
        $annotated      = [];
        $counts         = ['total' => count($wards), 'configured' => 0, 'missing' => 0, 'duplicate' => 0];

        foreach ($wards as $ward) {
            $code = trim((string) ($ward['api_ward_code'] ?? ''));
            $name = trim((string) ($ward['api_ward_name'] ?? ''));

            if ($code === '' || $name === '') {
                $status = 'missing';
                $counts['missing']++;
            } elseif (isset($duplicateNames[$name])) {
                $status = 'duplicate';
                $counts['duplicate']++;
            } else {
                $status = 'ok';
                $counts['configured']++;
            }

            $ward['mapping_status']       = $status;
            $ward['mapping_status_label'] = $this->adminStatusLabel($status);
            $annotated[]                  = $ward;
        }

        return [
            'wards'   => $annotated,
            'summary' => $counts,
        ];
    }

    /**
     * @param list<array<string, mixed>> $merged
     *
     * @return list<array{ward: string, ward_name: string}>
     */
    public function uniqueApiWardsFromMerged(array $merged): array
    {
        $seen = [];
        $out  = [];

        foreach ($merged as $row) {
            $code = trim((string) ($row['ward'] ?? ''));
            $name = trim((string) ($row['ward_name'] ?? ''));
            $key  = $code . '|' . $name;
            if ($key === '|' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[]      = ['ward' => $code, 'ward_name' => $name];
        }

        usort($out, static fn ($a, $b) => [$a['ward'], $a['ward_name']] <=> [$b['ward'], $b['ward_name']]);

        return $out;
    }

    /**
     * @param list<array<string, mixed>> $wards
     *
     * @return array<string, true> api_ward_name => true when duplicated
     */
    public function findDuplicateApiNames(array $wards): array
    {
        $counts = [];
        foreach ($wards as $ward) {
            if (! ($ward['is_active'] ?? true)) {
                continue;
            }
            $name = trim((string) ($ward['api_ward_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $counts[$name] = ($counts[$name] ?? 0) + 1;
        }

        $dupes = [];
        foreach ($counts as $name => $count) {
            if ($count > 1) {
                $dupes[$name] = true;
            }
        }

        return $dupes;
    }

    /**
     * @param list<array<string, mixed>> $apiRows แถวจาก HosxpPayloadParser::parse()['merged']
     * @param list<array<string, mixed>> $dbWards  แถวจาก wards (is_active)
     *
     * @return array{
     *     summary: array<string, int>,
     *     api_rows: list<array<string, mixed>>,
     *     db_issues: list<array<string, mixed>>
     * }
     */
    public function compare(array $apiRows, array $dbWards): array
    {
        $lookup = $this->buildLookup($dbWards);
        $apiResults = [];
        $matchedWardIds = [];

        foreach ($apiRows as $row) {
            $apiCode = trim((string) ($row['ward'] ?? ''));
            $apiName = trim((string) ($row['ward_name'] ?? ''));
            $match   = $this->findWard($apiCode, $apiName, $lookup);
            $status  = $this->resolveApiStatus($apiCode, $apiName, $match, $lookup);

            if ($match !== null && $status === 'matched') {
                $matchedWardIds[(int) $match['id']] = true;
            }

            $apiResults[] = [
                'ward'              => $apiCode,
                'ward_name'         => $apiName,
                'ward_name_ward'    => (string) ($row['ward_name_ward'] ?? ''),
                'patient_count'     => (int) ($row['patient_count'] ?? 0),
                'status'            => $status,
                'status_label'      => $this->apiStatusLabel($status),
                'ward_id'           => $match['id'] ?? null,
                'ward_name_db'      => $match['name'] ?? null,
                'api_ward_code_db'  => $match['api_ward_code'] ?? null,
                'api_ward_name_db'  => $match['api_ward_name'] ?? null,
                'note'              => $this->apiNote($status, $apiCode, $apiName, $match, $lookup),
            ];
        }

        $dbIssues = [];
        foreach ($dbWards as $ward) {
            $id = (int) $ward['id'];
            $code = trim((string) ($ward['api_ward_code'] ?? ''));
            $name = trim((string) ($ward['api_ward_name'] ?? ''));

            if ($code === '' || $name === '') {
                $dbIssues[] = $this->dbIssueRow($ward, 'missing_config', 'ยังไม่ตั้ง api_ward_code / api_ward_name');

                continue;
            }

            if (! isset($matchedWardIds[$id])) {
                $dbIssues[] = $this->dbIssueRow($ward, 'not_in_api', 'ไม่พบใน API รอบนี้ (หรือไม่มีผู้ป่วย/การเคลื่อนไหว)');
            }
        }

        $summary = $this->buildSummary($apiResults, $dbWards, $dbIssues);

        return [
            'summary'   => $summary,
            'api_rows'  => $apiResults,
            'db_issues' => $dbIssues,
        ];
    }

    /**
     * @param list<array<string, mixed>> $dbWards
     *
     * @return array{
     *     name_to_ward: array<string, array<string, mixed>>,
     *     db_name_to_ward: array<string, array<string, mixed>>,
     *     code_to_wards: array<string, list<array<string, mixed>>>
     * }
     */
    public function buildLookup(array $dbWards): array
    {
        $nameToWard = [];
        $dbNameToWard = [];
        $codeToWards = [];

        foreach ($dbWards as $ward) {
            $apiName = trim((string) ($ward['api_ward_name'] ?? ''));
            if ($apiName !== '') {
                $nameToWard[$apiName] = $ward;
            }
            $dbName = trim((string) ($ward['name'] ?? ''));
            if ($dbName !== '') {
                $dbNameToWard[$dbName] = $ward;
            }
            $code = trim((string) ($ward['api_ward_code'] ?? ''));
            if ($code !== '') {
                $codeToWards[$code][] = $ward;
            }
        }

        return [
            'name_to_ward'    => $nameToWard,
            'db_name_to_ward' => $dbNameToWard,
            'code_to_wards'   => $codeToWards,
        ];
    }

    /**
     * @param array{name_to_ward: array<string, array<string, mixed>>, db_name_to_ward: array<string, array<string, mixed>>, code_to_wards: array<string, list<array<string, mixed>>>} $lookup
     *
     * @return array<string, mixed>|null
     */
    public function findWard(string $apiCode, string $apiName, array $lookup): ?array
    {
        if ($apiName !== '') {
            if (isset($lookup['name_to_ward'][$apiName])) {
                return $lookup['name_to_ward'][$apiName];
            }
            if (isset($lookup['db_name_to_ward'][$apiName])) {
                return $lookup['db_name_to_ward'][$apiName];
            }
        }

        if ($apiCode === '') {
            return null;
        }

        $matches = $lookup['code_to_wards'][$apiCode] ?? [];
        if (count($matches) === 1) {
            return $matches[0];
        }

        if (count($matches) > 1 && $apiName !== '') {
            foreach ($matches as $ward) {
                if (trim((string) ($ward['api_ward_name'] ?? '')) === $apiName) {
                    return $ward;
                }
            }
        }

        return null;
    }

    /**
     * @param array{name_to_ward: array<string, array<string, mixed>>, db_name_to_ward: array<string, array<string, mixed>>, code_to_wards: array<string, list<array<string, mixed>>>} $lookup
     */
    private function resolveApiStatus(string $apiCode, string $apiName, ?array $match, array $lookup): string
    {
        if ($match === null) {
            if ($apiCode !== '' && count($lookup['code_to_wards'][$apiCode] ?? []) > 1) {
                return 'ambiguous';
            }

            return 'unmapped';
        }

        $dbApiName = trim((string) ($match['api_ward_name'] ?? ''));
        if ($apiName !== '' && $dbApiName !== '' && $apiName !== $dbApiName) {
            return 'name_mismatch';
        }

        return 'matched';
    }

    /**
     * @param array{name_to_ward: array<string, array<string, mixed>>, db_name_to_ward: array<string, array<string, mixed>>, code_to_wards: array<string, list<array<string, mixed>>>} $lookup
     */
    private function apiNote(string $status, string $apiCode, string $apiName, ?array $match, array $lookup): string
    {
        if ($status === 'ambiguous') {
            $names = array_map(
                static fn ($w) => (string) ($w['api_ward_name'] ?? $w['name'] ?? ''),
                $lookup['code_to_wards'][$apiCode] ?? []
            );

            return 'รหัส ' . $apiCode . ' มีหลายแผนกในระบบ: ' . implode(', ', $names);
        }

        if ($status === 'unmapped') {
            return 'เพิ่ม/แก้ api_ward_name ใน Admin → จัดการแผนก';
        }

        if ($status === 'name_mismatch' && $match !== null) {
            return 'จับคู่ได้แต่ชื่อ API ไม่ตรงกับที่ตั้งไว้ (' . ($match['api_ward_name'] ?? '') . ')';
        }

        return '';
    }

    private function apiStatusLabel(string $status): string
    {
        return match ($status) {
            'matched'        => 'จับคู่แล้ว',
            'name_mismatch'  => 'จับคู่แล้ว (ชื่อไม่ตรง)',
            'ambiguous'      => 'รหัสซ้ำ — ต้องระบุชื่อ',
            'unmapped'       => 'ยังไม่ map',
            default          => $status,
        };
    }

    /**
     * @param array<string, mixed> $ward
     *
     * @return array<string, mixed>
     */
    private function dbIssueRow(array $ward, string $issue, string $label): array
    {
        return [
            'ward_id'       => (int) $ward['id'],
            'name'          => (string) ($ward['name'] ?? ''),
            'code'          => (string) ($ward['code'] ?? ''),
            'api_ward_code' => (string) ($ward['api_ward_code'] ?? ''),
            'api_ward_name' => (string) ($ward['api_ward_name'] ?? ''),
            'issue'         => $issue,
            'issue_label'   => $label,
        ];
    }

    /**
     * @param list<array<string, mixed>> $apiResults
     * @param list<array<string, mixed>> $dbWards
     * @param list<array<string, mixed>> $dbIssues
     *
     * @return array<string, int>
     */
    private function buildSummary(array $apiResults, array $dbWards, array $dbIssues): array
    {
        $counts = [
            'api_total'         => count($apiResults),
            'matched'           => 0,
            'unmapped_api'      => 0,
            'ambiguous_api'     => 0,
            'name_mismatch_api' => 0,
            'db_active'         => count($dbWards),
            'db_missing_config' => 0,
            'db_not_in_api'     => 0,
        ];

        foreach ($apiResults as $row) {
            match ($row['status']) {
                'matched'       => $counts['matched']++,
                'unmapped'      => $counts['unmapped_api']++,
                'ambiguous'     => $counts['ambiguous_api']++,
                'name_mismatch' => $counts['name_mismatch_api']++,
                default         => null,
            };
        }

        foreach ($dbIssues as $issue) {
            if ($issue['issue'] === 'missing_config') {
                $counts['db_missing_config']++;
            } elseif ($issue['issue'] === 'not_in_api') {
                $counts['db_not_in_api']++;
            }
        }

        return $counts;
    }

    private function adminStatusLabel(string $status): string
    {
        return match ($status) {
            'ok'        => 'ตั้งค่าแล้ว',
            'missing'   => 'ยังไม่ตั้ง API',
            'duplicate' => 'ชื่อ API ซ้ำ',
            default     => $status,
        };
    }

    /**
     * @return array{valid: bool, message: string}
     */
    public function validateApiMappingFields(?string $apiCode, ?string $apiName, ?int $excludeWardId = null): array
    {
        $code = trim((string) $apiCode);
        $name = trim((string) $apiName);

        if ($code === '' && $name === '') {
            return ['valid' => true, 'message' => ''];
        }

        if ($code === '' || $name === '') {
            return [
                'valid'   => false,
                'message' => 'ต้องกรอกทั้ง API Ward Code และ API Ward Name หรือเว้นว่างทั้งคู่',
            ];
        }

        $model = model(\App\Models\WardModel::class);
        $builder = $model->where('api_ward_name', $name)->where('is_active', 1);
        if ($excludeWardId !== null) {
            $builder->where('id !=', $excludeWardId);
        }
        if ($builder->first() !== null) {
            return [
                'valid'   => false,
                'message' => 'API Ward Name "' . $name . '" ถูกใช้โดยแผนกอื่นแล้ว',
            ];
        }

        return ['valid' => true, 'message' => ''];
    }
}
