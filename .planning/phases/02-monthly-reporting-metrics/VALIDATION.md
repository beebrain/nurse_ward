# Phase 2 Validation: Monthly Reporting & Metrics

## Goal
Automate monthly summary generation and productivity calculations, providing both web-based views and Excel exports.

## Observable Truths
- [x] User can select a ward and a month/year to view a monthly summary report.
- [x] The summary report correctly aggregates daily census data (admissions, discharges, transfers, deaths, midnight census).
- [x] Productivity percentage is calculated using the formula: (Total Patient Days / (Beds * Days in Month)) * 100.
- [x] The tabular report view in the browser matches the layout of the hospital's existing Excel template.
- [x] User can download the monthly report as an Excel (.xlsx) file.
- [x] The downloaded Excel file contains the same data and styling as the web view.

## Required Artifacts
- [x] **app/Services/ReportService.php**: Handles business logic for data aggregation and productivity math.
- [x] **app/Controllers/ReportController.php**: Manages report viewing and export requests.
- [x] **app/Views/reports/monthly_summary.php**: Tabular UI for displaying monthly metrics.
- [x] **tests/unit/ReportServiceTest.php**: Unit tests verifying the accuracy of aggregations and formulas.

## Key Links
- [x] **Controller -> Service**: `ReportController` uses `ReportService` to fetch aggregated data.
- [x] **Service -> Database**: `ReportService` queries census records by ward and date range.
- [x] **Controller -> Excel**: `ReportController` uses `PhpSpreadsheet` to generate `.xlsx` downloads.

## Automated Verification
- `vendor/bin/phpunit tests/unit/ReportServiceTest.php` (pass with warning about Xdebug coverage mode only).
- `php spark routes` includes `/reports/monthly`, `/reports/getData`, `/reports/export`.
- `composer show phpoffice/phpspreadsheet` confirms dependency installation.
- `php -l app/Controllers/ReportController.php` and `php -l app/Services/ReportService.php` passed.
| Requirement | Command |
|-------------|---------|
| Aggregation Logic | `php spark test tests/unit/ReportServiceTest.php` |
| Routing | `php spark routes | grep -i reports` |
| Dependencies | `composer show phpoffice/phpspreadsheet` |
