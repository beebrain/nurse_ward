<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * HosXP nursing level field names and parsing defaults.
 * Adjust when hospital API documents exact payload keys.
 */
class HosxpLevel extends BaseConfig
{
    /** @var list<string> Keys tried on each API item for per-level counts (sprintf with level 1–5). */
    public array $itemLevelFieldPatterns = [
        'patients_level_%d',
        'patient_level_%d',
        'level_%d',
        'count_level_%d',
        'nlevel_%d',
        'nursing_level_%d',
    ];

    /** @var list<string> Keys for nested level maps on API items. */
    public array $itemNestedLevelKeys = [
        'levels',
        'nursing_levels',
        'patient_levels',
        'level_counts',
    ];

    /** Ward level used when HosXP sends count but level cannot be mapped. */
    public int $unmappedDefaultLevel = 3;
}
