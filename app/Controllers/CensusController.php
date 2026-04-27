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
        $wards = $this->wardModel->getActiveWithDepartment();

        // Nurses see only their assigned wards
        if ($this->isNurse()) {
            $assignedIds = $this->userWardModel->getWardIdsForUser((int)auth()->id());
            $wards = array_values(array_filter($wards, fn($w) => in_array((int)$w['id'], $assignedIds)));
        }

        return view('census/create', [
            'wards'    => $wards,
            'title'    => 'บันทึกยอดผู้ป่วยรายวัน',
            'isNurse'  => $this->isNurse(),
        ]);
    }

    public function store()
    {
        $post = $this->request->getPost();
        $censusData = $this->buildCensusData($post);
        if ($censusData === null) {
            return redirect()->back()->withInput()->with('error', 'กรุณากรอกข้อมูล Ward / วันที่ / Shift ให้ครบ');
        }

        // Permission: nurse may only record for assigned wards
        if (! $this->canRecordForWard((int)$censusData['ward_id'])) {
            return redirect()->back()->withInput()->with('error', 'คุณไม่มีสิทธิ์บันทึกข้อมูลสำหรับ Ward นี้');
        }

        // Time window: max 12 hours after shift start
        if (! $this->isWithinRecordingWindow($censusData['record_date'], $censusData['shift'])) {
            return redirect()->back()->withInput()
                ->with('error', 'ไม่สามารถบันทึกย้อนหลังเกิน 12 ชั่วโมง สำหรับกะ ' . $censusData['shift'] . ' วันที่ ' . $censusData['record_date']);
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
            return $this->response->setJSON(['success' => false, 'message' => 'เกินระยะเวลาบันทึกย้อนหลัง 12 ชั่วโมง']);
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

        // Nurses: restrict to their assigned wards
        if ($this->isNurse()) {
            $assignedIds = $this->userWardModel->getWardIdsForUser((int)auth()->id());
            if ($wardIdInt !== null && ! in_array($wardIdInt, $assignedIds)) {
                return $this->response->setStatusCode(403)->setJSON(['error' => 'ไม่มีสิทธิ์ดู Ward นี้']);
            }
            if ($wardIdInt === null && ! empty($assignedIds)) {
                // Default to first assigned ward so nurses don't see all records
                $wardIdInt = $assignedIds[0];
            }
        }

        $shiftLabels = ['Night' => 'ดึก', 'Morning' => 'เช้า', 'Afternoon' => 'บ่าย'];

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
                'patients_level_5'     => (int)$row['patients_level_5'],
                'patients_level_4'     => (int)$row['patients_level_4'],
                'patients_level_3'     => (int)$row['patients_level_3'],
                'patients_level_2'     => (int)$row['patients_level_2'],
                'patients_level_1'     => (int)$row['patients_level_1'],
                'total_patients'       => (int)$row['total_patients'],
                'carried_forward_patients'  => (int)($row['carried_forward_patients'] ?? 0),
                'movement_expected_patients' => (int)($row['movement_expected_patients'] ?? 0),
                'movement_variance'          => (int)($row['movement_variance'] ?? 0),
                'admissions'           => (int)$row['admissions'],
                'discharges'           => (int)$row['discharges'],
                'transfers_in'         => (int)$row['transfers_in'],
                'transfers_out'        => (int)$row['transfers_out'],
                'deaths'               => (int)$row['deaths'],
                'nurses_rn'            => (int)$row['nurses_rn'],
                'nurses_tn'            => (int)$row['nurses_tn'],
                'nurses_pn'            => (int)$row['nurses_pn'],
                'equipment_ventilator'       => (int)($row['equipment_ventilator'] ?? 0),
                'equipment_hfnc'             => (int)($row['equipment_hfnc'] ?? 0),
                'working_hours'        => $row['working_hours'],
                'required_care_hours'  => $row['required_care_hours'],
                'productivity'         => $row['productivity'] !== null ? round((float)$row['productivity'], 2) : null,
                'recorder_username'    => $row['recorder_username'] ?? '—',
                'updated_at'           => $row['updated_at'],
            ];
        }

        return $this->response->setJSON(['rows' => $out]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

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
     * Nurses (and anyone, for consistency) may only record data within 12 hours
     * of a shift's start. Superadmin and Manager are exempt.
     *
     * Shift starts: Night=00:00, Morning=08:00, Afternoon=16:00
     * Deadline = shift_start + 12 hours
     */
    private function isWithinRecordingWindow(string $date, string $shift): bool
    {
        // Superadmin / Manager bypass the time restriction
        if (! $this->isNurse()) {
            return true;
        }

        $startHour = self::SHIFT_START_HOUR[$shift] ?? 0;
        $shiftStart = strtotime($date . ' ' . sprintf('%02d:00:00', $startHour));
        $deadline   = $shiftStart + (12 * 3600);

        return time() <= $deadline;
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
