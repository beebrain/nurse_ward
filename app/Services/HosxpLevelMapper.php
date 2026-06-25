<?php

namespace App\Services;

use App\Models\HosxpNursingLevelMapModel;
use Config\HosxpLevel;

/**
 * Maps HosXP nursing grade codes/names to ward levels L1–L5.
 */
class HosxpLevelMapper
{
    /** @var array<string, int>|null */
    private static ?array $codeCache = null;

    public function __construct(
        private ?HosxpNursingLevelMapModel $mapModel = null,
        private ?HosxpLevel $config = null,
    ) {
        $this->mapModel = $mapModel ?? new HosxpNursingLevelMapModel();
        $this->config   = $config ?? config(HosxpLevel::class);
    }

    public function toWardLevel(mixed $hosxpCode): ?int
    {
        if ($hosxpCode === null || $hosxpCode === '') {
            return null;
        }

        $key = strtoupper(trim((string) $hosxpCode));
        $map = $this->codeMap();

        if (isset($map[$key])) {
            return $map[$key];
        }

        if (ctype_digit($key)) {
            $n = (int) $key;

            return ($n >= 1 && $n <= 5) ? $n : null;
        }

        return null;
    }

    public function defaultLevel(): int
    {
        return $this->config->unmappedDefaultLevel;
    }

    /**
     * @return array<string, int>
     */
    private function codeMap(): array
    {
        if (self::$codeCache !== null) {
            return self::$codeCache;
        }

        self::$codeCache = [];
        $rows            = $this->mapModel->orderBy('sort_order', 'ASC')->findAll();
        foreach ($rows as $row) {
            $code = strtoupper(trim((string) $row['hosxp_code']));
            if ($code !== '') {
                self::$codeCache[$code] = (int) $row['ward_level'];
            }
        }

        return self::$codeCache;
    }

    public static function clearCache(): void
    {
        self::$codeCache = null;
    }
}
