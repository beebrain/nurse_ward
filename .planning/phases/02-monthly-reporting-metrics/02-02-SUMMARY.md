# Plan 02-02 Summary: Monthly Report Viewer

## Objective
Create the web-based report viewer for monthly summaries.

## Completed Tasks
- [x] Task 1: ReportController and Routing
  - Created `app/Controllers/ReportController.php`.
  - Implemented `monthly()` for the view and `getData()` for AJAX retrieval.
  - Registered routes in `Config/Routes.php` protected by `permission:reports.view`.
- [x] Task 2: Monthly Summary Dashboard UI
  - Created `app/Views/reports/monthly_summary.php`.
  - Implemented filters for Ward, Month, and Year.
  - Built a dynamic table that populates via AJAX.
  - Added summary stats cards (Patient Days, Beds, Capacity, Productivity).

## Verification
- `php spark routes` shows report routes with correct filters.
- UI renders correctly and triggers AJAX on form submission.
- Logic correctly bridges the UI to `ReportService`.
