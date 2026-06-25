<?php

namespace App\Services;

use Config\HosxpLevel;

/**
 * Extract patients_level_1..5 from a HosXP API item (flexible field names).
 */
class HosxpHourlyLevelParser
{
    public function __construct(
        private ?HosxpLevelMapper $mapper = null,
        private ?HosxpLevel $config = null,
    ) {
        $this->mapper = $mapper ?? new HosxpLevelMapper();
        $this->config = $config ?? config(HosxpLevel::class);
    }

    /**
     * @param array<string, mixed> $item
     * @return array{
     *   patients_level_1: int,
     *   patients_level_2: int,
     *   patients_level_3: int,
     *   patients_level_4: int,
     *   patients_level_5: int,
     *   has_level_data: bool
     * }
     */
    public function parseItemLevels(array $item): array
    {
        $levels = $this->emptyLevels();

        foreach ([5, 4, 3, 2, 1] as $wardLevel) {
            foreach ($this->config->itemLevelFieldPatterns as $pattern) {
                $key = sprintf($pattern, $wardLevel);
                if (array_key_exists($key, $item) && $item[$key] !== null && $item[$key] !== '') {
                    $levels["patients_level_{$wardLevel}"] += max(0, (int) $item[$key]);
                }
            }
        }

        foreach ($this->config->itemNestedLevelKeys as $nestedKey) {
            if (! isset($item[$nestedKey]) || ! is_array($item[$nestedKey])) {
                continue;
            }
            $this->mergeNestedLevels($levels, $item[$nestedKey]);
        }

        if (isset($item['level_items']) && is_array($item['level_items'])) {
            foreach ($item['level_items'] as $levelItem) {
                if (! is_array($levelItem)) {
                    continue;
                }
                $this->mergeLevelItem($levels, $levelItem);
            }
        }

        $levels['has_level_data'] = $this->sumLevels($levels) > 0;

        return $levels;
    }

    /**
     * @return array{patients_level_1: int, patients_level_2: int, patients_level_3: int, patients_level_4: int, patients_level_5: int, has_level_data: bool}
     */
    public function emptyLevels(): array
    {
        return [
            'patients_level_1' => 0,
            'patients_level_2' => 0,
            'patients_level_3' => 0,
            'patients_level_4' => 0,
            'patients_level_5' => 0,
            'has_level_data'   => false,
        ];
    }

    /**
     * @param array<string, int|bool> $levels
     * @param array<mixed> $nested
     */
    private function mergeNestedLevels(array &$levels, array $nested): void
    {
        foreach ($nested as $code => $count) {
            if (is_array($count)) {
                $this->mergeLevelItem($levels, $count);

                continue;
            }
            $wardLevel = $this->mapper->toWardLevel($code) ?? $this->mapper->toWardLevel(
                is_string($code) ? preg_replace('/\D/', '', $code) : $code
            );
            if ($wardLevel !== null) {
                $levels["patients_level_{$wardLevel}"] += max(0, (int) $count);
            }
        }
    }

    /**
     * @param array<string, int|bool> $levels
     * @param array<string, mixed> $levelItem
     */
    private function mergeLevelItem(array &$levels, array $levelItem): void
    {
        $code = $levelItem['level'] ?? $levelItem['nursing_level'] ?? $levelItem['level_code'] ?? null;
        $count = $levelItem['count'] ?? $levelItem['patient_count'] ?? $levelItem['total'] ?? 0;
        $wardLevel = $this->mapper->toWardLevel($code);
        if ($wardLevel === null) {
            return;
        }
        $levels["patients_level_{$wardLevel}"] += max(0, (int) $count);
    }

    /**
     * @param array<string, int|bool> $levels
     */
    private function sumLevels(array $levels): int
    {
        $sum = 0;
        foreach ([1, 2, 3, 4, 5] as $lv) {
            $sum += (int) ($levels["patients_level_{$lv}"] ?? 0);
        }

        return $sum;
    }
}
