<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CensusModel;
use App\Models\CensusQualityIndicatorModel;
use App\Models\CensusEditLogModel;
use App\Models\UserWardModel;
use App\Models\WardModel;

class CensusController extends BaseController
{
    protected $censusModel;
    protected $qiModel;
    protected $editLogModel;
    protected $userWardModel;
    protected $wardModel;

    // Shift start hours (24h) — used for the 12-hour recording window.
    private const SHIFT_START_HOUR = [
        'Night'     => 0,
        'Morning'   => 8,
        'Afternoon' => 16,
    ];

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

        // Time window: shift must have started; nurses have max 12 hours after shift start.
        if (! $this->isWithinRecordingWindow($censusData['record_date'], $censusData['shift'])) {
            return redirect()->back()->withInput()
                ->with('error', $this->recordingWindowError($censusData['record_date'], $censusData['shift']));
        }

        $existing = $this->censusModel->findByShift(
            (int)$censusData['ward_id'],
            $censusData['record_date'],
            $censusData['shift']
        );

        if ($this->censusModel->getNextShiftRecord((int)$censusData['ward_id'], $censusData['record_date'], $censusData['shift'])) {
            return redirect()->back()->withInput()->with('error', 'ไม่สามารถแก้ไข/เพิ่มเวรนี้ได้ เนื่องจากมีการบันทึกเวรถัดไปแล้ว');
        }

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

        if ($censusData['shift'] === 'Afternoon') {
            $this->censusModel->recalculateProductivity(
                (int)$censusData['ward_id'],
                $censusData['record_date']
            );
        }

        $msg = $existing ? 'อัปเดตข้อมูลเรียบร้อยแล้ว (มีการแก้ไขปรับปรุง)' : 'บันทึกข้อมูลเรียบร้อยแล้ว';

        return redirect()->to('census/new')->with('message', $msg);
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

        if (! $this->canRecordForWard((int)$post['ward_id'])) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ไม่มีสิทธิ์บันทึก Ward นี้']);
        }

        if (! $this->isWithinRecordingWindow($post['record_date'], $post['shift'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $this->recordingWindowError($post['record_date'], $post['shift']),
            ]);
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

        if ($this->censusModel->getNextShiftRecord((int)$censusData['ward_id'], $censusData['record_date'], $censusData['shift'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ไม่สามารถแก้ไข/เพิ่มเวรนี้ได้ เนื่องจากมีการบันทึกเวรถัดไปแล้ว',
            ]);
        }

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

            if ($censusData['shift'] === 'Afternoon') {
                $this->censusModel->recalculateProductivity(
                    (int)$censusData['ward_id'],
                    $censusData['record_date']
                );
            }

            return $this->response->setJSON([
                'success'   => true,
                'census_id' => $censusId,
                'updated'   => $isUpdate,
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
        [$nextDate, $nextShift] = $this->censusModel->nextShiftKey($date, $shift);
        $current = $this->censusModel->findByShift($wardId, $date, $shift);
        $previous = $this->censusModel->getPreviousShiftRecord($wardId, $date, $shift);
        $next = $this->censusModel->getNextShiftRecord($wardId, $date, $shift);
        $qi = $current ? $this->qiModel->findByCensusId((int)$current['id']) : null;

        return $this->response->setJSON([
            'success'        => true,
            'previous_date'  => $previousDate,
            'previous_shift' => $previousShift,
            'next_date'      => $nextDate,
            'next_shift'     => $nextShift,
            'has_previous'   => $previous !== null,
            'has_current'    => $current !== null,
            'is_locked'      => $next !== null,
            'locked_by'      => $next ? [
                'record_date' => $next['record_date'],
                'shift'       => $next['shift'],
            ] : null,
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
        [$wards, $defaultWardId] = $this->getAvailableWardsForCurrentUser();

        return view('census/productivity', [
            'title' => 'Productivity',
            'wards' => $wards,
            'defaultWardId' => $defaultWardId,
            'currentMonth' => (int) date('n'),
            'currentYear' => (int) date('Y'),
            'isNurse' => $this->isNurse(),
        ]);
    }

    public function productivityData()
    {
        if (! auth()->loggedIn()) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $mode = (string)($this->request->getGet('mode') ?? 'month');
        $year = (int)($this->request->getGet('year') ?? date('Y'));
        $month = (int)($this->request->getGet('month') ?? date('n'));

        if (! in_array($mode, ['month', 'year'], true) || $year < 2000 || $year > 2100) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'ตัวกรองไม่ถูกต้อง']);
        }
        if ($mode === 'month' && ($month < 1 || $month > 12)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'เดือนไม่ถูกต้อง']);
        }

        $wardId = $this->resolveHistoryWardId($this->request->getGet('ward_id'));
        if ($wardId === null) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'ไม่มี Ward ที่สามารถดูข้อมูลได้']);
        }

        if ($mode === 'year') {
            return $this->response->setJSON($this->buildYearProductivityPayload($wardId, $year));
        }

        return $this->response->setJSON($this->buildMonthProductivityPayload($wardId, $month, $year));
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
        $rows = $this->censusModel->getHistoryForList($wardId, $dateFrom, $dateTo, 120);
        $daily = $this->buildDailyProductivityRows($rows);

        return [
            'mode' => 'month',
            'ward_id' => $wardId,
            'month' => $month,
            'year' => $year,
            'days' => array_values($daily),
            'summary' => $this->summarizeProductivityRows(array_values($daily)),
        ];
    }

    private function buildYearProductivityPayload(int $wardId, int $year): array
    {
        $rows = $this->censusModel->getHistoryForList($wardId, "{$year}-01-01", "{$year}-12-31", 1200);
        $daily = $this->buildDailyProductivityRows($rows);
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

    private function buildDailyProductivityRows(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row['record_date']][$row['shift']] = $row;
        }

        ksort($byDate);
        $daily = [];
        foreach ($byDate as $date => $shifts) {
            $patientDayRow = $this->pickShiftRow($shifts, ['Night', 'Afternoon', 'Morning']);
            $careRow = $this->pickShiftRow($shifts, ['Afternoon', 'Night', 'Morning']);
            $requiredCareHours = $careRow ? $this->requiredCareHoursFromRow($careRow) : 0.0;
            $workingHours = 0.0;
            $admissions = 0;
            $discharges = 0;
            $transfersIn = 0;
            $transfersOut = 0;
            $deaths = 0;

            foreach ($shifts as $shiftRow) {
                $workingHours += (float)($shiftRow['working_hours'] ?? 0);
                $admissions += (int)$shiftRow['admissions'];
                $discharges += (int)$shiftRow['discharges'];
                $transfersIn += (int)$shiftRow['transfers_in'];
                $transfersOut += (int)$shiftRow['transfers_out'];
                $deaths += (int)$shiftRow['deaths'];
            }

            $daily[$date] = [
                'date' => $date,
                'day_label' => $this->thaiDateShort($date),
                'weekday_label' => $this->thaiWeekdayLabel($date),
                'patient_days' => (int)($patientDayRow['total_patients'] ?? 0),
                'patient_day_shift' => $patientDayRow['shift'] ?? null,
                'care_shift' => $careRow['shift'] ?? null,
                'recorded_shifts' => count($shifts),
                'required_care_hours' => round($requiredCareHours, 2),
                'working_hours' => round($workingHours, 2),
                'productivity' => $workingHours > 0 && $requiredCareHours > 0
                    ? round(($requiredCareHours * 100) / $workingHours, 2)
                    : null,
                'admissions' => $admissions,
                'discharges' => $discharges,
                'transfers_in' => $transfersIn,
                'transfers_out' => $transfersOut,
                'deaths' => $deaths,
            ];
        }

        return $daily;
    }

    private function pickShiftRow(array $shifts, array $priority): ?array
    {
        foreach ($priority as $shift) {
            if (isset($shifts[$shift])) {
                return $shifts[$shift];
            }
        }

        return null;
    }

    private function requiredCareHoursFromRow(array $row): float
    {
        if ((float)($row['required_care_hours'] ?? 0) > 0) {
            return (float)$row['required_care_hours'];
        }

        $hours = 0.0;
        foreach (CensusModel::LEVEL_HOURS as $level => $hourPerPatient) {
            $hours += (int)($row["patients_level_{$level}"] ?? 0) * $hourPerPatient;
        }

        return $hours;
    }

    private function summarizeProductivityRows(array $rows): array
    {
        $summary = [
            'recorded_days' => count($rows),
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

        foreach ($rows as $row) {
            $summary['recorded_shifts'] += $row['recorded_shifts'];
            $summary['patient_days'] += $row['patient_days'];
            $summary['required_care_hours'] += $row['required_care_hours'];
            $summary['working_hours'] += $row['working_hours'];
            $summary['admissions'] += $row['admissions'];
            $summary['discharges'] += $row['discharges'];
            $summary['transfers_in'] += $row['transfers_in'];
            $summary['transfers_out'] += $row['transfers_out'];
            $summary['deaths'] += $row['deaths'];
        }

        $summary['required_care_hours'] = round($summary['required_care_hours'], 2);
        $summary['working_hours'] = round($summary['working_hours'], 2);
        $summary['productivity'] = $summary['working_hours'] > 0 && $summary['required_care_hours'] > 0
            ? round(($summary['required_care_hours'] * 100) / $summary['working_hours'], 2)
            : null;

        return $summary;
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
        $labels = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
        return $labels[(int)date('w', strtotime($date))] ?? '';
    }

    private function thaiDateShort(string $date): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }

        $months = [
            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
            5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
            9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
        ];

        $day = (int)date('j', $timestamp);
        $month = $months[(int)date('n', $timestamp)] ?? date('m', $timestamp);
        $thaiYearShort = ((int)date('Y', $timestamp) + 543) % 100;

        return sprintf('%d %s %02d', $day, $month, $thaiYearShort);
    }

    private function thaiMonthLabel(int $month): string
    {
        $labels = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
        ];

        return $labels[$month] ?? (string)$month;
    }

    private function isNurse(): bool
    {
        $user = auth()->user();
        return $user && $user->inGroup('nurse') && ! $user->inGroup('superadmin') && ! $user->inGroup('manager');
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

    /**
     * Recording window:
     * - Never allow "future shifts" (before shift start).
     * - Late-entry is allowed (no 12-hour cutoff).
     *
     * Shift starts: Night=00:00, Morning=08:00, Afternoon=16:00
     */
    private function isWithinRecordingWindow(string $date, string $shift): bool
    {
        $startHour = self::SHIFT_START_HOUR[$shift] ?? 0;
        $shiftStart = strtotime($date . ' ' . sprintf('%02d:00:00', $startHour));

        if (time() < $shiftStart) {
            return false;
        }

        return true;
    }

    private function recordingWindowError(string $date, string $shift): string
    {
        $startHour = self::SHIFT_START_HOUR[$shift] ?? 0;
        $shiftStart = strtotime($date . ' ' . sprintf('%02d:00:00', $startHour));

        if (time() < $shiftStart) {
            return 'ยังไม่ถึงเวลาเริ่มเวร ' . $shift . ' วันที่ ' . $date . ' จึงยังไม่สามารถบันทึกได้';
        }

        return 'ไม่สามารถบันทึกได้สำหรับเวรนี้';
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
            'critical_care_support', 'high_flow_oxygen',
        ];
        $out = [];
        foreach ($allowed as $key) {
            $out[$key] = (int)($qi[$key] ?? 0);
        }
        return $out;
    }
}
