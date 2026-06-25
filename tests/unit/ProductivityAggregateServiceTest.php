<?php

use App\Services\ProductivityAggregateService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ProductivityAggregateServiceTest extends CIUnitTestCase
{
    public function testBuildDailyRowsKeepsMovementAndProductivityPayload(): void
    {
        $service = new ProductivityAggregateService();
        $rows = [
            [
                'record_date'      => '2026-06-01',
                'shift'            => 'Morning',
                'total_patients'   => 5,
                'patients_level_3' => 5,
                'admissions'       => 1,
                'discharges'       => 2,
                'transfers_in'     => 3,
                'transfers_out'    => 4,
                'deaths'           => 0,
                'working_hours'    => 21.0,
            ],
        ];

        $daily = $service->buildDailyRows(
            $rows,
            ['code' => 'PP'],
            static fn(string $date): string => 'D:' . $date,
            static fn(string $date): string => 'W:' . $date,
        );

        $day = $daily['2026-06-01'];
        $this->assertSame('D:2026-06-01', $day['day_label']);
        $this->assertSame('W:2026-06-01', $day['weekday_label']);
        $this->assertSame(1, $day['recorded_shifts']);
        $this->assertSame(1, $day['admissions']);
        $this->assertSame(2, $day['discharges']);
        $this->assertSame(3, $day['transfers_in']);
        $this->assertSame(4, $day['transfers_out']);
        $this->assertEqualsWithDelta(27.5, $day['required_care_hours'], 0.01);
        $this->assertNotNull($day['productivity']);
    }

    public function testSummarizeDailyRowsIncludesMovementTotals(): void
    {
        $service = new ProductivityAggregateService();

        $summary = $service->summarizeDailyRows([
            [
                'recorded_shifts'     => 1,
                'patient_days'        => 5,
                'required_care_hours' => 27.5,
                'working_hours'       => 21.0,
                'admissions'          => 1,
                'discharges'          => 2,
                'transfers_in'        => 3,
                'transfers_out'       => 4,
                'deaths'              => 0,
            ],
        ]);

        $this->assertSame(1, $summary['recorded_days']);
        $this->assertSame(5, $summary['patient_days']);
        $this->assertSame(1, $summary['admissions']);
        $this->assertSame(4, $summary['transfers_out']);
        $this->assertEqualsWithDelta(130.95, $summary['productivity'], 0.01);
    }
}
