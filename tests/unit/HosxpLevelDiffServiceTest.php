<?php

use App\Services\HosxpLevelDiffService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class HosxpLevelDiffServiceTest extends CIUnitTestCase
{
    private HosxpLevelDiffService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HosxpLevelDiffService();
    }

    public function testDiffIntervalAttributesDepartureToDroppedLevel(): void
    {
        $prev = [
            'patients_level_3' => 5,
            'discharges_today' => 0,
            'moves_out_today'  => 0,
            'deaths_today'     => 0,
        ];
        $curr = [
            'patients_level_3' => 4,
            'discharges_today' => 1,
            'moves_out_today'  => 0,
            'deaths_today'     => 0,
        ];

        $departed = $this->service->diffInterval($prev, $curr);

        $this->assertSame(1, $departed[3]);
        $this->assertSame(0, $departed[4]);
    }

    public function testDiffIntervalReturnsEmptyWhenNoOutEvents(): void
    {
        $prev = ['patients_level_3' => 5, 'discharges_today' => 0, 'moves_out_today' => 0, 'deaths_today' => 0];
        $curr = ['patients_level_3' => 4, 'discharges_today' => 0, 'moves_out_today' => 0, 'deaths_today' => 0];

        $departed = $this->service->diffInterval($prev, $curr);

        $this->assertSame(0, array_sum($departed));
    }

    public function testCareHoursFromDepartedUsesLevelHours(): void
    {
        $hours = $this->service->careHoursFromDeparted([3 => 2, 4 => 0, 5 => 0, 2 => 0, 1 => 0]);

        $this->assertEqualsWithDelta(11.0, $hours, 0.01);
    }

    public function testTimelineHasLevelDataDetectsLevels(): void
    {
        $this->assertTrue($this->service->timelineHasLevelData([
            ['has_level_data' => 0, 'patients_level_3' => 2],
        ]));
        $this->assertFalse($this->service->timelineHasLevelData([
            ['has_level_data' => 0, 'patients_level_3' => 0],
        ]));
    }
}
