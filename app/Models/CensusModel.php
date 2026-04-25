<?php

namespace App\Models;

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
        // Patient levels (ปกติ + พิเศษ รวมกัน)
        'patients_level_5', 'patients_level_4', 'patients_level_3',
        'patients_level_2', 'patients_level_1', 'total_patients',
        // Movements
        'admissions', 'discharges', 'transfers_in', 'transfers_out', 'deaths',
        // Nursing staff
        'nurses_hw', 'nurses_rn', 'nurses_tn', 'nurses_pn', 'nurses_aide', 'nurses_ward',
        // Calculated (stored)
        'working_hours', 'required_care_hours', 'productivity',
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
        'patients_level_5' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_level_4' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_level_3' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_level_2' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'patients_level_1' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'admissions'    => 'permit_empty|numeric|greater_than_equal_to[0]',
        'discharges'    => 'permit_empty|numeric|greater_than_equal_to[0]',
        'transfers_in'  => 'permit_empty|numeric|greater_than_equal_to[0]',
        'transfers_out' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'deaths'        => 'permit_empty|numeric|greater_than_equal_to[0]',
        'nurses_rn'     => 'permit_empty|numeric|greater_than_equal_to[0]',
        'nurses_tn'     => 'permit_empty|numeric|greater_than_equal_to[0]',
        'nurses_pn'     => 'permit_empty|numeric|greater_than_equal_to[0]',
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
        // Total patients
        $data['total_patients'] = (int)($data['patients_level_5'] ?? 0)
            + (int)($data['patients_level_4'] ?? 0)
            + (int)($data['patients_level_3'] ?? 0)
            + (int)($data['patients_level_2'] ?? 0)
            + (int)($data['patients_level_1'] ?? 0);

        // Working hours = (RN + TN + PN) × 7 for THIS shift only
        // Stored per shift; productivity is calculated separately for Afternoon
        $rn = (int)($data['nurses_rn'] ?? 0);
        $tn = (int)($data['nurses_tn'] ?? 0);
        $pn = (int)($data['nurses_pn'] ?? 0);
        $data['working_hours'] = ($rn + $tn + $pn) * self::SHIFT_HOURS;
    }

    /**
     * Calculate and store productivity for the Afternoon shift of a given ward+date.
     * Called after Afternoon shift is saved.
     * Productivity uses Afternoon patient levels + sum of all 3 shifts' working hours.
     */
    public function recalculateProductivity(int $wardId, string $date): void
    {
        // Get all shifts for this ward+date
        $shifts = $this->where('ward_id', $wardId)
                       ->where('record_date', $date)
                       ->findAll();

        $shiftMap = [];
        foreach ($shifts as $s) {
            $shiftMap[$s['shift']] = $s;
        }

        $afternoon = $shiftMap['Afternoon'] ?? null;
        if (! $afternoon) {
            return; // Cannot calculate without afternoon data
        }

        // Required care hours using Afternoon patient levels
        $requiredHours = (self::LEVEL_HOURS[5] * (int)$afternoon['patients_level_5'])
            + (self::LEVEL_HOURS[4] * (int)$afternoon['patients_level_4'])
            + (self::LEVEL_HOURS[3] * (int)$afternoon['patients_level_3'])
            + (self::LEVEL_HOURS[2] * (int)$afternoon['patients_level_2'])
            + (self::LEVEL_HOURS[1] * (int)$afternoon['patients_level_1']);

        // Working hours = sum of (RN+TN+PN) across all 3 shifts × 7
        $totalProfNurses = 0;
        foreach ($shifts as $s) {
            $totalProfNurses += (int)$s['nurses_rn'] + (int)$s['nurses_tn'] + (int)$s['nurses_pn'];
        }
        $workingHours = $totalProfNurses * self::SHIFT_HOURS;

        $productivity = ($workingHours > 0 && $requiredHours > 0)
            ? round(($requiredHours * 100) / $workingHours, 4)
            : null;

        // Store in Afternoon record
        $this->update($afternoon['id'], [
            'working_hours'       => $workingHours,
            'required_care_hours' => $requiredHours,
            'productivity'        => $productivity,
        ]);
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
            'daily_census.patients_level_5',
            'daily_census.patients_level_4',
            'daily_census.patients_level_3',
            'daily_census.patients_level_2',
            'daily_census.patients_level_1',
            'daily_census.total_patients',
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
        $builder->orderBy('FIELD(daily_census.shift, "Night", "Morning", "Afternoon")', null, false);

        return $builder->get($limit)->getResultArray();
    }

    /**
     * Existing census for a specific ward/date/shift (for autosave upsert).
     */
    public function findByShift(int $wardId, string $date, string $shift): ?array
    {
        return $this->where('ward_id', $wardId)
                    ->where('record_date', $date)
                    ->where('shift', $shift)
                    ->first();
    }
}
