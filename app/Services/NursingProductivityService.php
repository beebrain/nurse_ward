<?php

namespace App\Services;

use App\Models\CensusModel;

/**
 * Nursing productivity: required care hours vs working hours.
 *
 * Standard wards use end-of-shift acuity snapshot (one care row per day).
 * Turnover wards (e.g. LR) count patients who left during the shift and sum all shifts.
 */
class NursingProductivityService
{
    public const MODE_STANDARD = 'standard';
    public const MODE_TURNOVER = 'turnover';

    /** @var list<string> */
    public const TURNOVER_WARD_CODES = ['LR'];

    public static function modeForWard(?array $ward): string
    {
        if ($ward === null) {
            return self::MODE_STANDARD;
        }

        $mode = strtolower(trim((string) ($ward['productivity_mode'] ?? '')));
        if (in_array($mode, [self::MODE_STANDARD, self::MODE_TURNOVER], true)) {
            return $mode;
        }

        $code = strtoupper(trim((string) ($ward['code'] ?? '')));

        return in_array($code, self::TURNOVER_WARD_CODES, true)
            ? self::MODE_TURNOVER
            : self::MODE_STANDARD;
    }

    /**
     * Patients actually cared for during the shift (LR-style turnover count).
     */
    public function turnoverCareCases(array $row): int
    {
        return (int) ($row['total_patients'] ?? 0)
            + (int) ($row['discharges'] ?? 0)
            + (int) ($row['transfers_out'] ?? 0)
            + (int) ($row['deaths'] ?? 0);
    }

    public function requiredCareHoursFromRow(array $row, string $mode = self::MODE_STANDARD): float
    {
        if ($mode === self::MODE_TURNOVER) {
            return $this->requiredCareHoursTurnover($row);
        }

        if ((float) ($row['required_care_hours'] ?? 0) > 0) {
            return (float) $row['required_care_hours'];
        }

        $hours = 0.0;
        foreach (CensusModel::LEVEL_HOURS as $level => $hourPerPatient) {
            $hours += (int) ($row["patients_level_{$level}"] ?? 0) * $hourPerPatient;
        }

        return $hours;
    }

    /**
     * Productivity metrics for a single shift row (form preview).
     *
     * @return array{required_care_hours: float, working_hours: float, productivity: float|null}
     */
    public function previewShift(array $row, string $mode = self::MODE_STANDARD): array
    {
        $required    = $this->requiredCareHoursFromRow($row, $mode);
        $working     = (float) ($row['working_hours'] ?? 0);

        return [
            'required_care_hours' => round($required, 2),
            'working_hours'       => round($working, 2),
            'productivity'        => $this->productivityPercent($required, $working),
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $shiftsByKey shift => row
     * @return array{
     *   required_care_hours: float,
     *   working_hours: float,
     *   productivity: float|null,
     *   care_shift: string|null,
     *   patient_days: int,
     *   patient_day_shift: string|null,
     *   turnover_cases: int|null,
     *   level_source: string|null
     * }
     */
    public function buildDailyMetrics(
        array $shiftsByKey,
        string $mode = self::MODE_STANDARD,
        ?int $wardId = null,
        ?string $recordDate = null,
        ?HosxpLevelDiffService $hosxpDiff = null,
    ): array {
        $workingHours = 0.0;
        foreach ($shiftsByKey as $shiftRow) {
            $workingHours += (float) ($shiftRow['working_hours'] ?? 0);
        }

        $patientDayRow = $this->pickShiftRow($shiftsByKey, ['Night', 'Afternoon', 'Morning']);
        $levelSource   = null;

        if ($mode === self::MODE_TURNOVER) {
            $requiredCareHours = 0.0;
            $turnoverCases     = 0;
            $hosxpDiff         = $hosxpDiff ?? new HosxpLevelDiffService();

            foreach ($shiftsByKey as $shiftKey => $shiftRow) {
                $turnoverCases += $this->turnoverCareCases($shiftRow);

                $hosxpShift = ($wardId && $recordDate)
                    ? $hosxpDiff->analyzeShift($wardId, $recordDate, (string) $shiftKey)
                    : null;

                if (is_array($hosxpShift) && $hosxpShift['has_level_data']) {
                    $requiredCareHours += $hosxpShift['total_care_hours'];
                    $levelSource = 'hosxp_diff';
                } else {
                    $requiredCareHours += $this->requiredCareHoursTurnover($shiftRow);
                    $levelSource = $levelSource ?? 'estimated';
                }
            }

            return [
                'required_care_hours' => round($requiredCareHours, 2),
                'working_hours'       => round($workingHours, 2),
                'productivity'        => $this->productivityPercent($requiredCareHours, $workingHours),
                'care_shift'          => null,
                'patient_days'        => (int) ($patientDayRow['total_patients'] ?? 0),
                'patient_day_shift'   => $patientDayRow['shift'] ?? null,
                'turnover_cases'      => $turnoverCases,
                'level_source'        => $levelSource,
            ];
        }

        $careRow           = $this->pickShiftRow($shiftsByKey, ['Afternoon', 'Night', 'Morning']);
        $requiredCareHours = $careRow ? $this->requiredCareHoursFromRow($careRow, self::MODE_STANDARD) : 0.0;

        return [
            'required_care_hours' => round($requiredCareHours, 2),
            'working_hours'       => round($workingHours, 2),
            'productivity'        => $this->productivityPercent($requiredCareHours, $workingHours),
            'care_shift'          => $careRow['shift'] ?? null,
            'patient_days'        => (int) ($patientDayRow['total_patients'] ?? 0),
            'patient_day_shift'   => $patientDayRow['shift'] ?? null,
            'turnover_cases'      => null,
            'level_source'        => null,
        ];
    }

    /**
     * @param list<array<string, mixed>> $shifts
     * @return array{required_care_hours: float, working_hours: float, productivity: float|null}
     */
    public function recalculateForDay(
        array $shifts,
        string $mode = self::MODE_STANDARD,
        ?int $wardId = null,
        ?string $recordDate = null,
        ?HosxpLevelDiffService $hosxpDiff = null,
    ): array {
        $shiftMap = [];
        foreach ($shifts as $shift) {
            $shiftMap[$shift['shift']] = $shift;
        }

        $metrics = $this->buildDailyMetrics($shiftMap, $mode, $wardId, $recordDate, $hosxpDiff);

        return [
            'required_care_hours' => $metrics['required_care_hours'],
            'working_hours'       => $metrics['working_hours'],
            'productivity'        => $metrics['productivity'],
        ];
    }

    private function requiredCareHoursTurnover(array $row): float
    {
        $remainingPatients = (int) ($row['total_patients'] ?? 0);
        $departures        = (int) ($row['discharges'] ?? 0)
            + (int) ($row['transfers_out'] ?? 0)
            + (int) ($row['deaths'] ?? 0);

        if ($remainingPatients === 0 && $departures === 0) {
            return 0.0;
        }

        $remainingHours = 0.0;
        foreach (CensusModel::LEVEL_HOURS as $level => $hourPerPatient) {
            $remainingHours += (int) ($row["patients_level_{$level}"] ?? 0) * $hourPerPatient;
        }

        if ($departures === 0) {
            return $remainingHours;
        }

        $avgHourPerPatient = $remainingPatients > 0
            ? $remainingHours / $remainingPatients
            : CensusModel::LEVEL_HOURS[3];

        return $remainingHours + ($departures * $avgHourPerPatient);
    }

    private function productivityPercent(float $requiredCareHours, float $workingHours): ?float
    {
        if ($workingHours <= 0 || $requiredCareHours <= 0) {
            return null;
        }

        return round(($requiredCareHours * 100) / $workingHours, 2);
    }

    /**
     * @param array<string, array<string, mixed>> $shifts
     * @param list<string> $priority
     */
    private function pickShiftRow(array $shifts, array $priority): ?array
    {
        foreach ($priority as $shift) {
            if (isset($shifts[$shift])) {
                return $shifts[$shift];
            }
        }

        return null;
    }
}
