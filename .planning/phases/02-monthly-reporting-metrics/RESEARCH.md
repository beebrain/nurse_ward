# Phase 2: Monthly Reporting & Metrics - Research

**Researched:** 2026-03-23
**Domain:** Data Aggregation & Excel Reporting
**Confidence:** HIGH

## User Constraints
- **Locked Decisions**: Use CodeIgniter 4 for backend logic. Export to Excel format.
- **Agent's Discretion**: Choice of Excel library (PhpSpreadsheet recommended). Implementation of aggregation logic.

## Standard Stack
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PhpSpreadsheet | ^2.1 | Excel Generation | Full support for styles, formulas, and templates. |
| CI4 Query Builder | Native | MySQL Aggregation | Efficient data retrieval and grouping. |

## Architecture Patterns
- **Aggregation Service**: Create a `ReportService` to handle the math (Productivity/NHPPD) instead of putting it in the Controller.
- **Template-Based Export**: Load the hospital's `.xlsx` file as a template to preserve styling, then inject data into specific cells.

## Common Pitfalls
- **Memory Limits**: Large datasets can exhaust PHP memory during Excel generation. Use `chunk()` in CI4 and cell caching in PhpSpreadsheet.
- **SQL Grouping**: Ensure `record_date` is treated correctly across shifts to avoid double-counting "Remaining" patients.

## Code Example (Aggregation)
```php
$builder = $db->table('daily_census');
$builder->select('record_date, SUM(admissions) as total_adm, SUM(discharges) as total_dis');
$builder->where('ward_id', $wardId);
$builder->groupBy('record_date');
$results = $builder->get()->getResultArray();
```
