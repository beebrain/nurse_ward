<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CensusModel;
use App\Models\CensusQualityIndicatorModel;
use App\Models\CensusEditLogModel;
use App\Models\UserWardModel;
use App\Models\WardModel;
use App\Services\NursingProductivityService;
use App\Services\HosxpLevelDiffService;
use App\Services\ProductivityAggregateService;

class CensusController extends BaseController
{
    protected $censusModel;
    protected $qiModel;
    protected $editLogModel;
    protected $userWardModel;
    protected $wardModel;

    public function __construct()
    {
        $this->censusModel   = new CensusModel();
        $this->qiModel       = new CensusQualityIndicatorModel();
        $this->editLogModel  = new CensusEditLogModel();
        $this->userWardModel = new UserWardModel();
        $this->wardModel     = new WardModel();
    }

    public function index()
    {
        return redirect()->to('census/new');
    }

    public function create()
    {
        [$wards, $defaultWardId] = $this->getAvailableWardsForCurrentUser();

        return view('census/create', [
            'wards'    => $wards,
            'title'    => 'บันทึกยอดผู้ป่วยรายวัน',
            'isNurse'  => $this->isNurse(),
            'defaultWardId' => $defaultWardId,
        ]);
    }

    public function store()
    {
        $post = $this->request->getPost();
        $censusData = $this->buildCensusData($post);
        if ($censusData === null) {
            return redirect()->back()->withInput()->with('error', 'กรุณากรอกข้อมูล Ward / วันที่ / เวร ให้ครบ');
        }

        // Permission: nurse may only record for assigned wards
        if (! $this->canRecordForWard((int)$censusData['ward_id'])) {
            return redirect()->back()->withInput()->with('error', 'คุณไม่มีสิทธิ์บันทึกข้อมูลสำหรับ Ward นี้');
        }

        $existing = $this->censusModel->findByShift(
            (int)$censusData['ward_id'],
            $censusData['record_date'],
            $censusData['shift']
        );

        $censusId = null;
        try {
            if ($existing) {
                $this->editLogModel->logAction($existing['id'], auth()->id(), 'update', $existing);
                $this->censusModel->update($existing['id'], $censusData);
                $censusId = $existing['id'];
            } else {
                $censusId = $this->censusModel->insert($censusData, true);
                $this->editLogModel->logAction((int)$censusId, auth()->id(), 'create', null);
            }
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'บันทึกไม่สำเร็จ: ' . $e->getMessage());
        }

        $qi = $this->request->getPost('qi');
        if ($qi && is_array($qi)) {
            $this->qiModel->upsertForCensus((int)$censusId, $this->filterQiData($qi));
        }

        if ($this->censusModel->shouldRecalculateProductivityOnSave(
            $this->wardModel->find((int) $censusData['ward_id']),
            $censusData['shift']
        )) {
            $this->censusModel->recalculateProductivity(
                (int) $censusData['ward_id'],
                $censusData['record_date'],
                null,
                $censusData['shift']
            );
        }

        $msg = $existing ? 'อัปเดตข้อมูลเรียบร้อยแล้ว (มีการแก้ไขปรับปรุง)' : 'บันทึกข้อมูลเรียบร้อยแล้ว';

        return redirect()->to('census/new')->with('message', $msg);
    }

    public function confirmSave()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Invalid request']);
        }

        $post = $this->request->getPost();

        if (empty($post['ward_id']) || empty($post['record_date']) || empty($post['shift'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'ข้อมูลไม่ครบ']);
        }

        if (! $this->canRecordForWard((int)$post['ward_id'])) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ไม่มีสิทธิ์บันทึก Ward นี้']);
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

        $censusId  = null;
        $isUpdate  = false;
        try {
            if ($existing) {
                $this->editLogModel->logAction($existing['id'], auth()->id(), 'update', $existing);
                $this->censusModel->update($existing['id'], $censusData);
                $censusId = $existing['id'];
                $isUpdate = true;
            } else {
                $censusId = $this->censusModel->insert($censusData, true);
                $this->editLogModel->logAction((int)$censusId, auth()->id(), 'create', null);
            }

            $qi = $post['qi'] ?? [];
            if ($censusId && is_array($qi) && array_filter($qi)) {
                $this->qiModel->upsertForCensus((int)$censusId, $this->filterQiData($qi));
            }

            if ($this->censusModel->shouldRecalculateProductivityOnSave(
                $this->wardModel->find((int) $censusData['ward_id']),
                $censusData['shift']
            )) {
                $this->censusModel->recalculateProductivity(
                    (int) $censusData['ward_id'],
                    $censusData['record_date'],
                    null,
                    $censusData['shift']
                );
            }

            return $this->response->setJSON([
                'success'   => true,
                'census_id' => $censusId,
                'updated'   => $isUpdate,
                'message'   => $isUpdate ? 'ยืนยันการแก้ไขข้อมูลเรียบร้อยแล้ว' : 'ยืนยันข้อมูลเรียบร้อยแล้ว',
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function movementContext()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Invalid request']);
        }

        $wardId = (int)($this->request->getGet('ward_id') ?? 0);
        $date   = (string)($this->request->getGet('record_date') ?? '');
        $shift  = (string)($this->request->getGet('shift') ?? '');

        if ($wardId <= 0 || $date === '' || $shift === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'ข้อมูลไม่ครบ']);
        }

        if (! $this->canRecordForWard($wardId)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ไม่มีสิทธิ์ดู Ward นี้']);
        }

        [$previousDate, $previousShift] = $this->censusModel->previousShiftKey($date, $shift);
        $current = $this->censusModel->findByShift($wardId, $date, $shift);
        $previous = $this->censusModel->getPreviousShiftRecord($wardId, $date, $shift);
        $qi = $current ? $this->qiModel->findByCensusId((int)$current['id']) : null;

        return $this->response->setJSON([
            'success'        => true,
            'previous_date'  => $previousDate,
            'previous_shift' => $previousShift,
            'has_previous'   => $previous !== null,
            'has_current'    => $current !== null,
            'carried_forward_patients' => (int)($previous['total_patients'] ?? 0),
            'current_record' => $current ? $this->formatCensusSnapshot($current, $qi) : null,
            'previous_snapshot' => [
                'patients_general_level_5' => (int)($previous['patients_general_level_5'] ?? 0),
                'patients_general_level_4' => (int)($previous['patients_general_level_4'] ?? 0),
                'patients_general_level_3' => (int)($previous['patients_general_level_3'] ?? 0),
                'patients_general_level_2' => (int)($previous['patients_general_level_2'] ?? 0),
                'patients_general_level_1' => (int)($previous['patients_general_level_1'] ?? 0),
                'patients_special_level_5' => (int)($previous['patients_special_level_5'] ?? 0),
                'patients_special_level_4' => (int)($previous['patients_special_level_4'] ?? 0),
                'patients_special_level_3' => (int)($previous['patients_special_level_3'] ?? 0),
                'patients_special_level_2' => (int)($previous['patients_special_level_2'] ?? 0),
                'patients_special_level_1' => (int)($previous['patients_special_level_1'] ?? 0),
                'equipment_ventilator'     => (int)($previous['equipment_ventilator'] ?? 0),
                'equipment_hfnc'           => (int)($previous['equipment_hfnc'] ?? 0),
            ],
        ]);
    }

    public function hourlyGuidelines()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Invalid request']);
        }

        $wardId = (int)($this->request->getGet('ward_id') ?? 0);
        $date   = (string)($this->request->getGet('record_date') ?? '');
        $shift  = (string)($this->request->getGet('shift') ?? '');

        if ($wardId <= 0 || $date === '' || $shift === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'ข้อมูลไม่ครบ']);
        }

        if (! $this->canRecordForWard($wardId)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ไม่มีสิทธิ์ดู Ward นี้']);
        }

        $hourlyModel = new \App\Models\HourlyCensusModel();
        $data = $hourlyModel->getShiftTotals($wardId, $date, $shift);

        return $this->response->setJSON([
            'success'  => true,
            'timeline' => $data['timeline'],
            'totals'   => $data['totals']
        ]);
    }

    public function productivityPreview()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Invalid request']);
        }

        $post = $this->request->getPost();
        $draft = $this->buildCensusData($post);
        if ($draft === null) {
            return $this->response->setJSON(['success' => false, 'message' => 'ข้อมูลไม่ครบ']);
        }

        $wardId = (int) $draft['ward_id'];
        $date   = $draft['record_date'];
        $shift  = $draft['shift'];

        if (! $this->canRecordForWard($wardId)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ไม่มีสิทธิ์ดู Ward นี้']);
        }

        $ward    = $this->wardModel->find($wardId);
        $mode    = NursingProductivityService::modeForWard($ward);
        $service = new NursingProductivityService();
        $hosxp   = new HosxpLevelDiffService();

        $shiftPreview = $service->previewShift($draft, $mode);

        $shiftsByKey = [];
        foreach ($this->censusModel->where('ward_id', $wardId)->where('record_date', $date)->findAll() as $row) {
            $shiftsByKey[$row['shift']] = $row;
        }
        $shiftsByKey[$shift] = $draft;

        $daily = $service->buildDailyMetrics($shiftsByKey, $mode, $wardId, $date, $hosxp);
        $shiftLabels = $this->shiftLabelsTh();

        $recordedShifts = $this->orderedShiftKeys(array_keys($shiftsByKey));
        $recordedLabels = array_map(fn ($s) => $shiftLabels[$s] ?? $s, $recordedShifts);

        $careFromShifts = $mode === NursingProductivityService::MODE_TURNOVER
            ? $recordedLabels
            : [($daily['care_shift'] ? ($shiftLabels[$daily['care_shift']] ?? $daily['care_shift']) : null)];

        $careFromShifts = array_values(array_filter($careFromShifts));

        return $this->response->setJSON([
            'success' => true,
            'productivity_mode' => $mode,
            'level_source'      => $daily['level_source'],
            'shift' => [
                'key'                 => $shift,
                'label'               => $shiftLabels[$shift] ?? $shift,
                'required_care_hours' => $shiftPreview['required_care_hours'],
                'working_hours'       => $shiftPreview['working_hours'],
                'productivity'        => $shiftPreview['productivity'],
            ],
            'daily' => [
                'required_care_hours' => $daily['required_care_hours'],
                'working_hours'       => $daily['working_hours'],
                'productivity'        => $daily['productivity'],
                'recorded_shifts'     => $recordedLabels,
                'care_from_shifts'    => $careFromShifts,
                'working_from_shifts' => $recordedLabels,
                'care_shift_key'      => $daily['care_shift'],
            ],
        ]);
    }

    public function history()
    {
        [$wards, $defaultWardId] = $this->getAvailableWardsForCurrentUser();

        return view('census/history', [
            'title' => 'ประวัติการบันทึกย้อนหลัง',
            'wards' => $wards,
            'defaultWardId' => $defaultWardId,
            'currentMonth' => (int) date('n'),
            'currentYear' => (int) date('Y'),
            'isNurse' => $this->isNurse(),
        ]);
    }

    public function historyData()
    {
        if (! auth()->loggedIn()) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $year = (int)($this->request->getGet('year') ?? date('Y'));
        $month = (int)($this->request->getGet('month') ?? date('n'));

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'ตัวกรองไม่ถูกต้อง']);
        }

        $wardId = $this->resolveHistoryWardId($this->request->getGet('ward_id'));
        if ($wardId === null) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'ไม่มี Ward ที่สามารถดูข้อมูลได้']);
        }

        return $this->response->setJSON($this->buildMonthHistoryPayload($wardId, $month, $year));
    }

    public function productivity()
    {
        if ($redirect = $this->ensureProductivityAccess()) {
            return $redirect;
        }

        [$wards, $defaultWardId] = $this->getAvailableWardsForCurrentUser();

        return view('census/productivity', [
            'title'         => 'แดชบอร์ด Productivity',
            'wards'         => $wards,
            'defaultWardId' => $defaultWardId,
            'currentMonth' => (int) date('n'),
            'currentYear'  => (int) date('Y'),
            'isNurse'      => $this->isNurse(),
            'canRecord'    => auth()->user()->can('census.record'),
            'wardSubtitle' => $this->buildProductivityWardSubtitle($wards),
            'levelHours'   => CensusModel::LEVEL_HOURS,
            'shiftHours'   => CensusModel::SHIFT_HOURS,
        ]);
    }

    public function productivityData()
    {
        if ($redirect = $this->ensureProductivityAccess(true)) {
            return $redirect;
        }

        $mode  = (string) ($this->request->getGet('mode') ?? 'month');
        $year  = (int) ($this->request->getGet('year') ?? date('Y'));
        $month = (int) ($this->request->getGet('month') ?? date('n'));

        if (! in_array($mode, ['month', 'year'], true) || $year < 2000 || $year > 2100) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'ตัวกรองไม่ถูกต้อง']);
        }
        if ($mode === 'month' && ($month < 1 || $month > 12)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'เดือนไม่ถูกต้อง']);
        }

        [$wards] = $this->getAvailableWardsForCurrentUser();
        if ($wards === []) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'ไม่มี Ward ที่สามารถดูข้อมูลได้']);
        }

        if ($mode === 'year') {
            return $this->response->setJSON($this->buildAllWardsYearProductivityPayload($wards, $year));
        }

        return $this->response->setJSON($this->buildAllWardsMonthProductivityPayload($wards, $month, $year));
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function getAvailableWardsForCurrentUser(): array
    {
        $wards = $this->wardModel->getActiveWithDepartment();
        $defaultWardId = null;

        if ($this->isNurse()) {
            $assignedIds = array_map('intval', $this->userWardModel->getWardIdsForUser((int)auth()->id()));
            $wards = array_values(array_filter($wards, fn($w) => in_array((int)$w['id'], $assignedIds, true)));
            if (count($assignedIds) === 1) {
                $defaultWardId = (int)$assignedIds[0];
            }
        }

        if ($defaultWardId === null && ! empty($wards)) {
            $defaultWardId = (int)$wards[0]['id'];
        }

        return [$wards, $defaultWardId];
    }

    private function resolveHistoryWardId($wardIdRaw): ?int
    {
        [$wards, $defaultWardId] = $this->getAvailableWardsForCurrentUser();
        $allowedIds = array_map(static fn($ward) => (int)$ward['id'], $wards);
        $wardId = ($wardIdRaw !== null && $wardIdRaw !== '') ? (int)$wardIdRaw : $defaultWardId;

        if ($wardId === null || $wardId <= 0 || ! in_array($wardId, $allowedIds, true)) {
            return null;
        }

        return $wardId;
    }

    private function buildMonthHistoryPayload(int $wardId, int $month, int $year): array
    {
        $dateFrom = sprintf('%04d-%02d-01', $year, $month);
        $dateTo = date('Y-m-t', strtotime($dateFrom));
        $rows = $this->censusModel->getHistoryForList($wardId, $dateFrom, $dateTo, 120);

        $days = [];
        $lastDay = (int)date('t', strtotime($dateFrom));
        for ($day = 1; $day <= $lastDay; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $days[$date] = [
                'date' => $date,
                'day_label' => $this->thaiDateShort($date),
                'weekday_label' => $this->thaiWeekdayLabel($date),
                'shifts' => [
                    'Night' => null,
                    'Morning' => null,
                    'Afternoon' => null,
                ],
            ];
        }

        foreach ($rows as $row) {
            $date = $row['record_date'];
            $shift = $row['shift'];
            if (isset($days[$date]) && array_key_exists($shift, $days[$date]['shifts'])) {
                $days[$date]['shifts'][$shift] = $this->formatHistoryShift($row);
            }
        }

        return [
            'mode' => 'month',
            'ward_id' => $wardId,
            'month' => $month,
            'year' => $year,
            'days' => array_values($days),
            'summary' => $this->summarizeRows($rows),
        ];
    }

    private function buildMonthProductivityPayload(int $wardId, int $month, int $year): array
    {
        $dateFrom = sprintf('%04d-%02d-01', $year, $month);
        $dateTo = date('Y-m-t', strtotime($dateFrom));
        $ward = $this->wardModel->find($wardId);
        $rows = $this->censusModel->getHistoryForList($wardId, $dateFrom, $dateTo, 120);
        $daily = $this->buildDailyProductivityRows($rows, $ward);

        return [
            'mode' => 'month',
            'ward_id' => $wardId,
            'month' => $month,
            'year' => $year,
            'days' => array_values($daily),
            'summary' => $this->summarizeProductivityRows(array_values($daily)),
        ];
    }

    /**
     * @param list<array<string, mixed>> $wards
     */
    private function buildAllWardsMonthProductivityPayload(array $wards, int $month, int $year): array
    {
        $dateFrom = sprintf('%04d-%02d-01', $year, $month);
        $lastDay  = (int) date('t', strtotime($dateFrom));

        $wardMeta = [];
        foreach ($wards as $ward) {
            $wardMeta[] = [
                'id'    => (int) $ward['id'],
                'code'  => (string) ($ward['code'] ?? ''),
                'name'  => (string) $ward['name'],
                'label' => trim((string) ($ward['code'] ?? '') . ' ' . $ward['name']),
            ];
        }

        $days = [];
        for ($day = 1; $day <= $lastDay; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $days[$date] = [
                'date'          => $date,
                'day_label'     => $this->thaiDateShort($date),
                'weekday_label' => $this->thaiWeekdayLabel($date),
                'by_ward'       => [],
            ];
        }

        $allDailyRows = [];
        foreach ($wards as $ward) {
            $wardId = (int) $ward['id'];
            $rows   = $this->censusModel->getHistoryForList($wardId, $dateFrom, date('Y-m-t', strtotime($dateFrom)), 120);
            $daily  = $this->buildDailyProductivityRows($rows, $ward);

            foreach ($daily as $date => $dayRow) {
                if (! isset($days[$date])) {
                    continue;
                }
                $days[$date]['by_ward'][(string) $wardId] = [
                    'productivity'          => $dayRow['productivity'],
                    'patient_days'          => $dayRow['patient_days'],
                    'required_care_hours'   => $dayRow['required_care_hours'],
                    'working_hours'         => $dayRow['working_hours'],
                    'recorded_shifts'       => $dayRow['recorded_shifts'],
                ];
                $allDailyRows[] = $dayRow;
            }
        }

        return [
            'mode'   => 'month',
            'month'  => $month,
            'year'   => $year,
            'wards'  => $wardMeta,
            'days'   => array_values($days),
            'summary' => $this->summarizeProductivityRows($allDailyRows),
        ];
    }

    /**
     * @param list<array<string, mixed>> $wards
     */
    private function buildAllWardsYearProductivityPayload(array $wards, int $year): array
    {
        $wardMeta = [];
        foreach ($wards as $ward) {
            $wardMeta[] = [
                'id'    => (int) $ward['id'],
                'code'  => (string) ($ward['code'] ?? ''),
                'name'  => (string) $ward['name'],
                'label' => trim((string) ($ward['code'] ?? '') . ' ' . $ward['name']),
            ];
        }

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = [
                'month'       => $m,
                'month_label' => $this->thaiMonthLabel($m),
                'by_ward'     => [],
            ];
        }

        $allDailyRows = [];
        foreach ($wards as $ward) {
            $wardId = (int) $ward['id'];
            $rows   = $this->censusModel->getHistoryForList($wardId, "{$year}-01-01", "{$year}-12-31", 1200);
            $daily  = $this->buildDailyProductivityRows($rows, $ward);

            foreach ($daily as $dayRow) {
                $allDailyRows[] = $dayRow;
            }

            $monthly = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthly[$m] = [
                    'recorded_days'       => 0,
                    'required_care_hours' => 0.0,
                    'working_hours'       => 0.0,
                    'productivity'        => null,
                ];
            }

            foreach ($daily as $dayRow) {
                $m = (int) date('n', strtotime($dayRow['date']));
                $monthly[$m]['recorded_days']++;
                $monthly[$m]['required_care_hours'] += $dayRow['required_care_hours'];
                $monthly[$m]['working_hours'] += $dayRow['working_hours'];
            }

            foreach ($monthly as $m => &$row) {
                $row['required_care_hours'] = round($row['required_care_hours'], 2);
                $row['working_hours']       = round($row['working_hours'], 2);
                $row['productivity']        = $row['working_hours'] > 0 && $row['required_care_hours'] > 0
                    ? round(($row['required_care_hours'] * 100) / $row['working_hours'], 2)
                    : null;
                $months[$m]['by_ward'][(string) $wardId] = [
                    'productivity'        => $row['productivity'],
                    'recorded_days'     => $row['recorded_days'],
                    'required_care_hours' => $row['required_care_hours'],
                    'working_hours'     => $row['working_hours'],
                ];
            }
            unset($row);
        }

        return [
            'mode'    => 'year',
            'year'    => $year,
            'wards'   => $wardMeta,
            'months'  => array_values($months),
            'summary' => $this->summarizeProductivityRows($allDailyRows),
        ];
    }

    private function buildYearProductivityPayload(int $wardId, int $year): array
    {
        $ward = $this->wardModel->find($wardId);
        $rows = $this->censusModel->getHistoryForList($wardId, "{$year}-01-01", "{$year}-12-31", 1200);
        $daily = $this->buildDailyProductivityRows($rows, $ward);
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = [
                'month' => $month,
                'month_label' => $this->thaiMonthLabel($month),
                'recorded_days' => 0,
                'recorded_shifts' => 0,
                'patient_days' => 0,
                'required_care_hours' => 0.0,
                'working_hours' => 0.0,
                'productivity' => null,
                'admissions' => 0,
                'discharges' => 0,
                'transfers_in' => 0,
                'transfers_out' => 0,
                'deaths' => 0,
            ];
        }

        foreach ($daily as $day) {
            $month = (int)date('n', strtotime($day['date']));
            $months[$month]['recorded_days']++;
            $months[$month]['recorded_shifts'] += $day['recorded_shifts'];
            $months[$month]['patient_days'] += $day['patient_days'];
            $months[$month]['required_care_hours'] += $day['required_care_hours'];
            $months[$month]['working_hours'] += $day['working_hours'];
            $months[$month]['admissions'] += $day['admissions'];
            $months[$month]['discharges'] += $day['discharges'];
            $months[$month]['transfers_in'] += $day['transfers_in'];
            $months[$month]['transfers_out'] += $day['transfers_out'];
            $months[$month]['deaths'] += $day['deaths'];
        }

        foreach ($months as &$monthRow) {
            $monthRow['required_care_hours'] = round($monthRow['required_care_hours'], 2);
            $monthRow['working_hours'] = round($monthRow['working_hours'], 2);
            $monthRow['productivity'] = $monthRow['working_hours'] > 0 && $monthRow['required_care_hours'] > 0
                ? round(($monthRow['required_care_hours'] * 100) / $monthRow['working_hours'], 2)
                : null;
        }
        unset($monthRow);

        return [
            'mode' => 'year',
            'ward_id' => $wardId,
            'year' => $year,
            'months' => array_values($months),
            'summary' => $this->summarizeProductivityRows(array_values($daily)),
        ];
    }

    private function buildDailyProductivityRows(array $rows, ?array $ward = null): array
    {
        return (new ProductivityAggregateService($this->censusModel))->buildDailyRows(
            $rows,
            $ward,
            fn(string $date): string => $this->thaiDateShort($date),
            fn(string $date): string => $this->thaiWeekdayLabel($date),
        );
    }

    private function summarizeProductivityRows(array $rows): array
    {
        return (new ProductivityAggregateService($this->censusModel))->summarizeDailyRows($rows);
    }

    private function formatHistoryShift(array $row): array
    {
        $shiftLabels = ['Night' => 'เวรดึก', 'Morning' => 'เวรเช้า', 'Afternoon' => 'เวรบ่าย'];
        $shift = $row['shift'] ?? '';

        return [
            'id' => (int)$row['id'],
            'shift' => $shift,
            'shift_label' => $shiftLabels[$shift] ?? $shift,
            'record_date' => $row['record_date'],
            'record_date_label' => $this->thaiDateShort((string)$row['record_date']),
            'total_patients' => (int)$row['total_patients'],
            'patients_general_level_5' => (int)($row['patients_general_level_5'] ?? 0),
            'patients_general_level_4' => (int)($row['patients_general_level_4'] ?? 0),
            'patients_general_level_3' => (int)($row['patients_general_level_3'] ?? 0),
            'patients_general_level_2' => (int)($row['patients_general_level_2'] ?? 0),
            'patients_general_level_1' => (int)($row['patients_general_level_1'] ?? 0),
            'patients_special_level_5' => (int)($row['patients_special_level_5'] ?? 0),
            'patients_special_level_4' => (int)($row['patients_special_level_4'] ?? 0),
            'patients_special_level_3' => (int)($row['patients_special_level_3'] ?? 0),
            'patients_special_level_2' => (int)($row['patients_special_level_2'] ?? 0),
            'patients_special_level_1' => (int)($row['patients_special_level_1'] ?? 0),
            'patients_level_5' => (int)($row['patients_level_5'] ?? 0),
            'patients_level_4' => (int)($row['patients_level_4'] ?? 0),
            'patients_level_3' => (int)($row['patients_level_3'] ?? 0),
            'patients_level_2' => (int)($row['patients_level_2'] ?? 0),
            'patients_level_1' => (int)($row['patients_level_1'] ?? 0),
            'carried_forward_patients' => (int)($row['carried_forward_patients'] ?? 0),
            'movement_expected_patients' => (int)($row['movement_expected_patients'] ?? 0),
            'movement_variance' => (int)($row['movement_variance'] ?? 0),
            'admissions' => (int)$row['admissions'],
            'discharges' => (int)$row['discharges'],
            'transfers_in' => (int)$row['transfers_in'],
            'transfers_out' => (int)$row['transfers_out'],
            'deaths' => (int)$row['deaths'],
            'nurses_hw' => (int)($row['nurses_hw'] ?? 0),
            'nurses_rn' => (int)$row['nurses_rn'],
            'nurses_tn' => (int)$row['nurses_tn'],
            'nurses_pn' => (int)$row['nurses_pn'],
            'nurses_aide' => (int)($row['nurses_aide'] ?? 0),
            'nurses_ward' => (int)($row['nurses_ward'] ?? 0),
            'equipment_ventilator' => (int)($row['equipment_ventilator'] ?? 0),
            'equipment_hfnc' => (int)($row['equipment_hfnc'] ?? 0),
            'working_hours' => (float)($row['working_hours'] ?? 0),
            'required_care_hours' => (float)($row['required_care_hours'] ?? 0),
            'productivity' => $row['productivity'] !== null ? round((float)$row['productivity'], 2) : null,
            'recorder_username' => $row['recorder_username'] ?? '—',
            'updated_at' => $row['updated_at'],
        ];
    }

    private function summarizeRows(array $rows): array
    {
        $summary = [
            'recorded_shifts' => count($rows),
            'total_patients_sum' => 0,
            'admissions' => 0,
            'discharges' => 0,
            'transfers_in' => 0,
            'transfers_out' => 0,
            'deaths' => 0,
        ];

        foreach ($rows as $row) {
            $summary['total_patients_sum'] += (int)$row['total_patients'];
            $summary['admissions'] += (int)$row['admissions'];
            $summary['discharges'] += (int)$row['discharges'];
            $summary['transfers_in'] += (int)$row['transfers_in'];
            $summary['transfers_out'] += (int)$row['transfers_out'];
            $summary['deaths'] += (int)$row['deaths'];
        }

        return $summary;
    }

    private function thaiWeekdayLabel(string $date): string
    {
        return thai_weekday_label($date);
    }

    private function thaiDateShort(string $date): string
    {
        return thai_date_short($date);
    }

    private function thaiMonthLabel(int $month): string
    {
        return thai_month_label($month);
    }

    private function isNurse(): bool
    {
        $user = auth()->user();
        return $user && $user->inGroup('nurse') && ! $user->inGroup('superadmin') && ! $user->inGroup('manager');
    }

    private function ensureProductivityAccess(bool $json = false)
    {
        if (! auth()->loggedIn()) {
            if ($json) {
                return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
            }

            return redirect()->to(base_url('login'));
        }

        $user = auth()->user();
        if (! $user->can('census.record') && ! $user->can('reports.view')) {
            if ($json) {
                return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
            }

            return redirect()->to(base_url('auth/pending'));
        }

        return null;
    }

    private function buildProductivityWardSubtitle(array $wards): string
    {
        if ($wards === []) {
            return 'ยังไม่มีแผนกที่คุณสามารถดูข้อมูลได้';
        }

        if (count($wards) === 1) {
            $ward = $wards[0];
            $name = trim((string) ($ward['name'] ?? ''));
            $dept = trim((string) ($ward['department_name'] ?? ''));

            return $dept !== '' && $name !== ''
                ? "แผนก {$name} ({$dept})"
                : ($name !== '' ? "แผนก {$name}" : 'แผนกที่รับผิดชอบ');
        }

        if ($this->isNurse()) {
            return 'ประสิทธิภาพการพยาบาล — ' . count($wards) . ' แผนกที่รับผิดชอบ';
        }

        return 'ประสิทธิภาพการพยาบาล — ทุกแผนก (' . count($wards) . ')';
    }

    /**
     * Returns true if the current user may record data for the given ward.
     * Superadmin and Manager have unrestricted access.
     */
    private function canRecordForWard(int $wardId): bool
    {
        if (! $this->isNurse()) {
            return true;
        }
        return $this->userWardModel->userCanAccessWard((int)auth()->id(), $wardId);
    }

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
            'patients_general_level_5' => (int)($post['patients_general_level_5'] ?? 0),
            'patients_general_level_4' => (int)($post['patients_general_level_4'] ?? 0),
            'patients_general_level_3' => (int)($post['patients_general_level_3'] ?? 0),
            'patients_general_level_2' => (int)($post['patients_general_level_2'] ?? 0),
            'patients_general_level_1' => (int)($post['patients_general_level_1'] ?? 0),
            'patients_special_level_5' => (int)($post['patients_special_level_5'] ?? 0),
            'patients_special_level_4' => (int)($post['patients_special_level_4'] ?? 0),
            'patients_special_level_3' => (int)($post['patients_special_level_3'] ?? 0),
            'patients_special_level_2' => (int)($post['patients_special_level_2'] ?? 0),
            'patients_special_level_1' => (int)($post['patients_special_level_1'] ?? 0),
            'patients_level_5' => (int)($post['patients_level_5'] ?? 0),
            'patients_level_4' => (int)($post['patients_level_4'] ?? 0),
            'patients_level_3' => (int)($post['patients_level_3'] ?? 0),
            'patients_level_2' => (int)($post['patients_level_2'] ?? 0),
            'patients_level_1' => (int)($post['patients_level_1'] ?? 0),
            'carried_forward_patients' => $this->getCarriedForwardTotal((int)$wardId, $date, $shift),
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
            'equipment_ventilator'       => (int)($post['equipment_ventilator'] ?? 0),
            'equipment_hfnc'             => (int)($post['equipment_hfnc'] ?? 0),
            'notes'            => $post['notes'] ?? null,
            'api_discrepancy_reasons' => isset($post['api_discrepancy_reasons']) ? json_encode($post['api_discrepancy_reasons'], JSON_UNESCAPED_UNICODE) : null,
            'created_by'       => auth()->id(),
        ];

        $this->censusModel->computeDerived($data);
        return $data;
    }

    private function getCarriedForwardTotal(int $wardId, string $date, string $shift): int
    {
        $previous = $this->censusModel->getPreviousShiftRecord($wardId, $date, $shift);

        return (int)($previous['total_patients'] ?? 0);
    }

    private function formatCensusSnapshot(array $record, ?array $qi = null): array
    {
        $fields = [
            'patients_general_level_5', 'patients_general_level_4', 'patients_general_level_3',
            'patients_general_level_2', 'patients_general_level_1',
            'patients_special_level_5', 'patients_special_level_4', 'patients_special_level_3',
            'patients_special_level_2', 'patients_special_level_1',
            'admissions', 'discharges', 'transfers_in', 'transfers_out', 'deaths',
            'nurses_hw', 'nurses_rn', 'nurses_tn', 'nurses_pn', 'nurses_aide', 'nurses_ward',
            'equipment_ventilator', 'equipment_hfnc',
        ];

        $snapshot = [];
        foreach ($fields as $field) {
            $snapshot[$field] = (int)($record[$field] ?? 0);
        }

        $snapshot['notes'] = (string)($record['notes'] ?? '');
        $snapshot['api_discrepancy_reasons'] = isset($record['api_discrepancy_reasons']) && is_string($record['api_discrepancy_reasons']) 
            ? json_decode($record['api_discrepancy_reasons'], true) 
            : [];
        $snapshot['qi'] = [];
        foreach ($this->filterQiData($qi ?? []) as $field => $value) {
            $snapshot['qi'][$field] = $value;
        }

        return $snapshot;
    }

    private function filterQiData(array $qi): array
    {
        $allowed = [
            'hai_vap', 'hai_hap', 'hai_uti', 'hai_cauti',
            'hai_clabsi', 'hai_ssi', 'hai_bsi', 'hai_mdr',
            'new_sepsis', 'end_of_life', 'palliative_care',
            'critical_care_support',
        ];
        $out = [];
        foreach ($allowed as $key) {
            $out[$key] = (int)($qi[$key] ?? 0);
        }
        return $out;
    }

    /**
     * @return array<string, string>
     */
    private function shiftLabelsTh(): array
    {
        return ['Night' => 'เวรดึก', 'Morning' => 'เวรเช้า', 'Afternoon' => 'เวรบ่าย'];
    }

    /**
     * @param list<string> $shifts
     * @return list<string>
     */
    private function orderedShiftKeys(array $shifts): array
    {
        $order = ['Night', 'Morning', 'Afternoon'];

        return array_values(array_filter($order, static fn ($s) => in_array($s, $shifts, true)));
    }
}
