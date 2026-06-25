<?php

namespace App\Models;

use App\Services\NursingProductivityService;
use App\Services\HosxpLevelDiffService;
use App\Models\WardModel;
use CodeIgniter\Model;

class CensusModel extends Model
{
    protected $table            = 'daily_census';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ward_id', 'record_date', 'shift',
        // Patient levels by patient type, plus combined totals.
        'patients_general_level_5', 'patients_general_level_4', 'patients_general_level_3',
        'patients_general_level_2', 'patients_general_level_1',
        'patients_special_level_5', 'patients_special_level_4', 'patients_special_level_3',
        'patients_special_level_2', 'patients_special_level_1',
        'patients_level_5', 'patients_level_4', 'patients_level_3',
        'patients_level_2', 'patients_level_1', 'total_patients',
        'carried_forward_patients', 'movement_expected_patients', 'movement_variance',
        // Movements
        'admissions', 'discharges', 'transfers_in', 'transfers_out', 'deaths',
        // Nursing staff
        'nurses_hw', 'nurses_rn', 'nurses_tn', 'nurses_pn', 'nurses_aide', 'nurses_ward',
        // Patients using ward equipment (counts are people, not devices)
        'equipment_ventilator', 'equipment_hfnc',
        // Calculated (stored)
        'working_hours', 'required_care_hours', 'productivity',
        'api_discrepancy_reasons',
        'notes', 'created_by',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'ward_id'     => 'required|numeric|is_not_unique[wards.id]',
        'record_date' => 'required|valid_date',
        'shift'       => 'required|in_list[Night,Morning,Afternoon]',
        'patients_general_level_5' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_general_level_4' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_general_level_3' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_general_level_2' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_general_level_1' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_special_level_5' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_special_level_4' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_special_level_3' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_special_level_2' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_special_level_1' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_level_5' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_level_4' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_level_3' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_level_2' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_level_1' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'carried_forward_patients'  => 'permit_empty|numeric|greater_than_equal_to[0]',
        'movement_expected_patients' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'admissions'    => 'permit_empty|numeric|greater_than_equal_to[0]',
        'discharges'    => 'permit_empty|numeric|greater_than_equal_to[0]',
        'transfers_in'  => 'permit_empty|numeric|greater_than_equal_to[0]',
        'transfers_out' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'deaths'        => 'permit_empty|numeric|greater_than_equal_to[0]',
        'nurses_rn'     => 'permit_empty|numeric|greater_than_equal_to[0]',
        'nurses_tn'     => 'permit_empty|numeric|greater_than_equal_to[0]',
        'nurses_pn'     => 'permit_empty|numeric|greater_than_equal_to[0]',
        'equipment_ventilator'       => 'permit_empty|numeric|greater_than_equal_to[0]',
        'equipment_hfnc'             => 'permit_empty|numeric|greater_than_equal_to[0]',
    ];

    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // ── Standard hours per patient level (from Excel formula) ────────────────
    public const LEVEL_HOURS = [
        5 => 12.0,
        4 => 7.5,
        3 => 5.5,
        2 => 3.5,
        1 => 1.5,
    ];

    public const SHIFT_HOURS = 7; // hours per shift for professional nurses (from Excel: ×7)

    /**
     * Compute derived fields before insert/update.
     * Should be called before saving to ensure consistency.
     */
    public function computeDerived(array &$data): void
    {
        foreach ([5, 4, 3, 2, 1] as $level) {
            $general = (int)($data["patients_general_level_{$level}"] ?? 0);
            $special = (int)($data["patients_special_level_{$level}"] ?? 0);

            if ($general > 0 || $special > 0) {
                $data["patients_level_{$level}"] = $general + $special;
            } else {
                $data["patients_level_{$level}"] = (int)($data["patients_level_{$level}"] ?? 0);
                $data["patients_general_level_{$level}"] = $data["patients_level_{$level}"];
                $data["patients_special_level_{$level}"] = 0;
            }
        }

        // Total patients
        $data['total_patients'] = (int)($data['patients_level_5'] ?? 0)
            + (int)($data['patients_level_4'] ?? 0)
            + (int)($data['patients_level_3'] ?? 0)
            + (int)($data['patients_level_2'] ?? 0)
            + (int)($data['patients_level_1'] ?? 0);

        $carriedForward = (int)($data['carried_forward_patients'] ?? 0);
        $expected = $carriedForward
            + (int)($data['admissions'] ?? 0)
            + (int)($data['transfers_in'] ?? 0)
            - (int)($data['discharges'] ?? 0)
            - (int)($data['transfers_out'] ?? 0)
            - (int)($data['deaths'] ?? 0);
        $data['movement_expected_patients'] = max(0, $expected);
        $data['movement_variance'] = $data['total_patients'] - $data['movement_expected_patients'];

        // Working hours = (RN + TN + PN) × 7 for THIS shift only
        // Stored per shift; productivity is calculated separately for Afternoon
        $rn = (int)($data['nurses_rn'] ?? 0);
        $tn = (int)($data['nurses_tn'] ?? 0);
        $pn = (int)($data['nurses_pn'] ?? 0);
        $data['working_hours'] = ($rn + $tn + $pn) * self::SHIFT_HOURS;
    }

    /**
     * Calculate and store productivity for the Afternoon shift of a given ward+date.
     * Standard wards: Afternoon acuity snapshot + all shifts' working hours.
     * Turnover wards (LR): sum turnover-based required hours across all shifts.
     */
    public function recalculateProductivity(int $wardId, string $date, ?array $ward = null, ?string $savedShift = null): void
    {
        $shifts = $this->where('ward_id', $wardId)
                       ->where('record_date', $date)
                       ->findAll();

        if ($shifts === []) {
            return;
        }

        if ($ward === null) {
            $ward = model(WardModel::class)->find($wardId);
        }

        $mode    = NursingProductivityService::modeForWard($ward);
        $service = new NursingProductivityService();
        $hosxp   = new HosxpLevelDiffService();
        $metrics = $service->recalculateForDay($shifts, $mode, $wardId, $date, $hosxp);

        $target = null;
        foreach ($shifts as $shift) {
            if ($shift['shift'] === 'Afternoon') {
                $target = $shift;
                break;
            }
        }

        if ($target === null && $mode === NursingProductivityService::MODE_TURNOVER && $savedShift !== null) {
            foreach ($shifts as $shift) {
                if ($shift['shift'] === $savedShift) {
                    $target = $shift;
                    break;
                }
            }
        }

        if ($target === null) {
            return;
        }

        $this->update($target['id'], [
            'working_hours'       => $metrics['working_hours'],
            'required_care_hours' => $metrics['required_care_hours'],
            'productivity'        => $metrics['productivity'] !== null
                ? round($metrics['productivity'], 4)
                : null,
        ]);
    }

    public function shouldRecalculateProductivityOnSave(?array $ward, string $shift): bool
    {
        if (NursingProductivityService::modeForWard($ward) === NursingProductivityService::MODE_TURNOVER) {
            return true;
        }

        return $shift === 'Afternoon';
    }

    /**
     * History list for nurses UI.
     */
    public function getHistoryForList(?int $wardId, string $dateFrom, string $dateTo, int $limit = 400): array
    {
        $builder = $this->builder();
        $builder->select([
            'daily_census.id',
            'daily_census.ward_id',
            'daily_census.record_date',
            'daily_census.shift',
            'daily_census.patients_general_level_5',
            'daily_census.patients_general_level_4',
            'daily_census.patients_general_level_3',
            'daily_census.patients_general_level_2',
            'daily_census.patients_general_level_1',
            'daily_census.patients_special_level_5',
            'daily_census.patients_special_level_4',
            'daily_census.patients_special_level_3',
            'daily_census.patients_special_level_2',
            'daily_census.patients_special_level_1',
            'daily_census.patients_level_5',
            'daily_census.patients_level_4',
            'daily_census.patients_level_3',
            'daily_census.patients_level_2',
            'daily_census.patients_level_1',
            'daily_census.total_patients',
            'daily_census.carried_forward_patients',
            'daily_census.movement_expected_patients',
            'daily_census.movement_variance',
            'daily_census.admissions',
            'daily_census.discharges',
            'daily_census.transfers_in',
            'daily_census.transfers_out',
            'daily_census.deaths',
            'daily_census.nurses_hw',
            'daily_census.nurses_rn',
            'daily_census.nurses_tn',
            'daily_census.nurses_pn',
            'daily_census.nurses_aide',
            'daily_census.nurses_ward',
            'daily_census.equipment_ventilator',
            'daily_census.equipment_hfnc',
            'daily_census.working_hours',
            'daily_census.required_care_hours',
            'daily_census.productivity',
            'daily_census.created_at',
            'daily_census.updated_at',
            'wards.name AS ward_name',
            'wards.code AS ward_code',
            'users.username AS recorder_username',
        ]);
        $builder->join('wards', 'wards.id = daily_census.ward_id', 'left');
        $builder->join('users', 'users.id = daily_census.created_by', 'left');
        $builder->where('daily_census.record_date >=', $dateFrom);
        $builder->where('daily_census.record_date <=', $dateTo);
        if ($wardId !== null) {
            $builder->where('daily_census.ward_id', $wardId);
        }
        $builder->orderBy('daily_census.record_date', 'DESC');
        $builder->orderBy('FIELD(daily_census.shift, "Night", "Morning", "Afternoon")', '', false);

        return $builder->get($limit)->getResultArray();
    }

    /**
     * Existing census for a specific ward/date/shift (for confirm-save upsert).
     */
    public function findByShift(int $wardId, string $date, string $shift): ?array
    {
        return $this->where('ward_id', $wardId)
                    ->where('record_date', $date)
                    ->where('shift', $shift)
                    ->first();
    }

    public function getPreviousShiftRecord(int $wardId, string $date, string $shift): ?array
    {
        [$previousDate, $previousShift] = $this->previousShiftKey($date, $shift);

        return $this->findByShift($wardId, $previousDate, $previousShift);
    }

    public function getNextShiftRecord(int $wardId, string $date, string $shift): ?array
    {
        [$nextDate, $nextShift] = $this->nextShiftKey($date, $shift);

        return $this->findByShift($wardId, $nextDate, $nextShift);
    }

    public function previousShiftKey(string $date, string $shift): array
    {
        if ($shift === 'Morning') {
            return [$date, 'Night'];
        }

        if ($shift === 'Afternoon') {
            return [$date, 'Morning'];
        }

        return [date('Y-m-d', strtotime($date . ' -1 day')), 'Afternoon'];
    }

    public function nextShiftKey(string $date, string $shift): array
    {
        if ($shift === 'Night') {
            return [$date, 'Morning'];
        }

        if ($shift === 'Morning') {
            return [$date, 'Afternoon'];
        }

        return [date('Y-m-d', strtotime($date . ' +1 day')), 'Night'];
    }
}
