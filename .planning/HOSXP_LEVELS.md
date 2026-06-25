# HosXP Nursing Levels — Design & Implementation

**Status:** Schema + services implemented; awaiting API level fields from hospital  
**Updated:** 2026-06-02

## Overview

Store nursing levels L1–L5 per 30-minute HosXP snapshot. For turnover wards (LR), derive **departed patient levels** by diffing consecutive slots and use exact care hours instead of averaging.

## Database

### `hourly_patient_census` (new columns)

| Column | Purpose |
|--------|---------|
| `has_level_data` | 1 when any level count present |
| `patients_level_1` … `L5` | Snapshot per slot |

### `hosxp_nursing_level_map`

Maps HosXP grade codes (`1`, `L3`, hospital-specific codes) → ward level 1–5.  
Admin-editable when hospital documents codes.

## Services

| Service | Role |
|---------|------|
| `HosxpHourlyLevelParser` | Parse flexible API keys → L1–L5 |
| `HosxpLevelMapper` | HosXP code → ward level |
| `HosxpLevelDiffService` | Diff slots → `departed_by_level`, care hours |
| `NursingProductivityService` | Uses HosXP diff for LR when `has_level_data` |

## Productivity (LR)

Per shift, when hourly timeline has levels:

```
remaining_care_hours  = Σ (L ท้ายเวร × ชม./L)
departure_care_hours  = Σ (departed_L จาก diff × ชม./L)
total                 = remaining + departure
level_source          = hosxp_diff
```

Fallback (no HosXP levels): `level_source = estimated` (average from manual census).

## Fetch script

`scripts/fetch_ipd_hourly.py` — `parse_item_levels()` mirrors PHP parser; saves level columns when API sends them.

## Config

`app/Config/HosxpLevel.php` — field name patterns to try on API items.

## When API goes live

1. `php spark migrate`
2. Confirm API field names; update `HosxpLevel.php` / Python `LEVEL_FIELD_PATTERNS` if needed
3. Add rows to `hosxp_nursing_level_map` for hospital-specific codes
4. Verify LR productivity shows `level_source: hosxp_diff` on days with hourly level data

## Future

- Admin UI for `hosxp_nursing_level_map`
- Timeline-based productivity for all wards (not only LR turnover)
- Store `departed_by_level` JSON on `daily_census` for audit
