<?php

namespace Tests\Unit;

use App\Services\ReportService;
use CodeIgniter\Test\CIUnitTestCase;

class ReportServiceTest extends CIUnitTestCase
{
    public function testCalculateProductivity()
    {
        $service = new ReportService();

        // 500 patient days in a month of 30 days, with 20 beds.
        // Formula: (500 / (20 * 30)) * 100 = (500 / 600) * 100 = 83.333...
        $productivity = $service->calculateProductivity(500, 20, 30);
        $this->assertEqualsWithDelta(83.33, $productivity, 0.01);

        // Edge case: zero beds
        $productivity = $service->calculateProductivity(500, 0, 30);
        $this->assertEquals(0.0, $productivity);

        // Edge case: zero days in month
        $productivity = $service->calculateProductivity(500, 20, 0);
        $this->assertEquals(0.0, $productivity);
    }

    public function testGetMonthlyData()
    {
        $mockCensusModel = $this->getMockBuilder('App\Models\CensusModel')
            ->disableOriginalConstructor()
            ->onlyMethods(['builder'])
            ->getMock();

        $mockBuilder = $this->getMockBuilder('CodeIgniter\Database\BaseBuilder')
            ->disableOriginalConstructor()
            ->onlyMethods(['where', 'get'])
            ->getMock();

        $mockResult = $this->getMockBuilder('CodeIgniter\Database\ResultInterface')
            ->disableOriginalConstructor()
            ->onlyMethods(['getResultArray'])
            ->getMockForAbstractClass();

        $censusData = [
            [
                'ward_id' => 1,
                'record_date' => '2023-10-01',
                'shift' => 'Morning',
                'admissions' => 5,
                'discharges' => 2,
                'transfers_in' => 1,
                'transfers_out' => 1,
                'deaths' => 0,
                'total_remaining' => 20
            ],
            [
                'ward_id' => 1,
                'record_date' => '2023-10-01',
                'shift' => 'Night',
                'admissions' => 2,
                'discharges' => 1,
                'transfers_in' => 0,
                'transfers_out' => 0,
                'deaths' => 0,
                'total_remaining' => 21
            ],
            [
                'ward_id' => 1,
                'record_date' => '2023-10-02',
                'shift' => 'Night',
                'admissions' => 1,
                'discharges' => 3,
                'transfers_in' => 2,
                'transfers_out' => 0,
                'deaths' => 1,
                'total_remaining' => 19
            ]
        ];

        $mockCensusModel->expects($this->once())
            ->method('builder')
            ->willReturn($mockBuilder);

        $mockBuilder->expects($this->exactly(3))
            ->method('where')
            ->willReturnSelf();

        $mockBuilder->expects($this->once())
            ->method('get')
            ->willReturn($mockResult);

        $mockResult->expects($this->once())
            ->method('getResultArray')
            ->willReturn($censusData);

        $service = new ReportService($mockCensusModel);
        $result = $service->getMonthlyData(1, 10, 2023);

        // Totals:
        // Admissions: 5 + 2 + 1 = 8
        // Discharges: 2 + 1 + 3 = 6
        // Transfers In: 1 + 0 + 2 = 3
        // Transfers Out: 1 + 0 + 0 = 1
        // Deaths: 0 + 0 + 1 = 1
        // Patient Days: 21 (Night on Oct 1) + 19 (Night on Oct 2) = 40

        $this->assertEquals(8, $result['admissions']);
        $this->assertEquals(6, $result['discharges']);
        $this->assertEquals(3, $result['transfers_in']);
        $this->assertEquals(1, $result['transfers_out']);
        $this->assertEquals(1, $result['deaths']);
        $this->assertEquals(40, $result['patient_days']);
    }

    public function testGetYearlyTrend()
    {
        $service = new class extends ReportService {
            public function getMonthlyReport(int $wardId, int $month, int $year): array
            {
                return [
                    'patient_days' => $month * 10,
                    'productivity' => 0,
                ];
            }
        };

        $trend = $service->getYearlyTrend(1, 2026);

        $this->assertCount(12, $trend);
        $this->assertEquals(10, $trend[0]);
        $this->assertEquals(120, $trend[11]);
    }

    public function testGetWardComparison()
    {
        $fakeWardModel = new class {
            public function where($field, $value)
            {
                return $this;
            }

            public function findAll()
            {
                return [
                    ['id' => 1, 'name' => 'Ward A'],
                    ['id' => 2, 'name' => 'Ward B'],
                ];
            }
        };

        $service = new class(null, $fakeWardModel) extends ReportService {
            protected $mockWardModel;

            public function __construct($censusModel, $wardModel)
            {
                parent::__construct($censusModel, $wardModel);
                $this->mockWardModel = $wardModel;
            }

            protected function getWardModel()
            {
                return $this->mockWardModel;
            }

            public function getMonthlyReport(int $wardId, int $month, int $year): array
            {
                if ($wardId === 1) {
                    return ['patient_days' => 300, 'productivity' => 50.25];
                }

                return ['patient_days' => 420, 'productivity' => 70.5];
            }
        };

        $comparison = $service->getWardComparison(3, 2026);

        $this->assertEquals(['Ward A', 'Ward B'], $comparison['labels']);
        $this->assertEquals([50.25, 70.5], $comparison['productivity']);
        $this->assertEquals([300, 420], $comparison['patient_days']);
    }
}
