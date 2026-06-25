<?php

use App\Services\HosxpHourlyLevelParser;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class HosxpHourlyLevelParserTest extends CIUnitTestCase
{
    public function testParseFlatLevelFields(): void
    {
        $parser = new HosxpHourlyLevelParser();
        $result = $parser->parseItemLevels([
            'patients_level_3' => 4,
            'patients_level_4' => 1,
        ]);

        $this->assertTrue($result['has_level_data']);
        $this->assertSame(4, $result['patients_level_3']);
        $this->assertSame(1, $result['patients_level_4']);
    }

    public function testParseNestedLevelsMap(): void
    {
        $parser = new HosxpHourlyLevelParser();
        $result = $parser->parseItemLevels([
            'levels' => ['3' => 2, 'L4' => 1],
        ]);

        $this->assertSame(2, $result['patients_level_3']);
        $this->assertSame(1, $result['patients_level_4']);
    }
}
