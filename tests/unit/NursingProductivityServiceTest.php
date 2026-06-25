<?php

use App\Services\NursingProductivityService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class NursingProductivityServiceTest extends CIUnitTestCase
{
    private NursingProductivityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NursingProductivityService();
    }

    public function testTurnoverCareCasesIncludesDepartures(): void
    {
        $cases = $this->service->turnoverCareCases([
            'total_patients' => 5,
            'discharges'     => 1,
            'transfers_out'  => 0,
            'deaths'         => 0,
        ]);

        $this->assertSame(6, $cases);
    }

    public function testTurnoverRequiredHoursAddsDepartedPatients(): void
    {
        // 5 × L3 (5.5h) remaining + 1 discharge at same average
        $hours = $this->service->requiredCareHoursFromRow([
            'total_patients'   => 5,
            'patients_level_3' => 5,
            'discharges'       => 1,
            'transfers_out'    => 0,
            'deaths'           => 0,
        ], NursingProductivityService::MODE_TURNOVER);

        $this->assertEqualsWithDelta(33.0, $hours, 0.01);
    }

    public function testTurnoverOnlyDeparturesUsesDefaultAcuity(): void
    {
        $hours = $this->service->requiredCareHoursFromRow([
            'total_patients'   => 0,
            'patients_level_3' => 0,
            'discharges'       => 3,
            'transfers_out'    => 0,
            'deaths'           => 0,
        ], NursingProductivityService::MODE_TURNOVER);

        $this->assertEqualsWithDelta(16.5, $hours, 0.01);
    }

    public function testBuildDailyMetricsSumsAllShiftsForTurnoverWard(): void
    {
        $metrics = $this->service->buildDailyMetrics([
            'Morning' => [
                'shift'            => 'Morning',
                'total_patients'   => 2,
                'patients_level_3' => 2,
                'discharges'       => 1,
                'transfers_out'    => 0,
                'deaths'           => 0,
                'working_hours'    => 21.0,
            ],
            'Afternoon' => [
                'shift'            => 'Afternoon',
                'total_patients'   => 1,
                'patients_level_3' => 1,
                'discharges'       => 2,
                'transfers_out'    => 0,
                'deaths'           => 0,
                'working_hours'    => 28.0,
            ],
        ], NursingProductivityService::MODE_TURNOVER);

        $this->assertEqualsWithDelta(49.0, $metrics['working_hours'], 0.01);
        $this->assertGreaterThan(0, $metrics['required_care_hours']);
        $this->assertSame(6, $metrics['turnover_cases']);
        $this->assertNotNull($metrics['productivity']);
    }

    public function testModeForWardDetectsLaborRoomCode(): void
    {
        $this->assertSame(
            NursingProductivityService::MODE_TURNOVER,
            NursingProductivityService::modeForWard(['code' => 'LR'])
        );
        $this->assertSame(
            NursingProductivityService::MODE_STANDARD,
            NursingProductivityService::modeForWard(['code' => 'PP'])
        );
    }
}
