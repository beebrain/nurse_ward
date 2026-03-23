# Phase 1: Foundation & Daily Data Entry - Context

**Gathered:** 2026-03-24
**Status:** Ready for planning
**Source:** Discuss Phase 1 workflow

## User Management
- **Signup Flow**: Nurses can sign up themselves. Account activation requires Super Admin approval.
- **Admin Controls**: Super Admin has full CRUD access to all users and can deactivate accounts to maintain audit trails.
- **Session Duration**: Standard 2-hour session timeout.

## Data Entry Granularity
- **Shift-based Entry**: Patient counts are recorded per shift (Morning, Afternoon, Night).
- **Census Type**: Cumulative count for the shift.
- **Data Sub-components**: ADT (Admissions, Discharges, Transfers) are included as specific sub-fields in the entry form.
- **Auto-save Logic**: AJAX-based auto-save on field change to prevent data loss during interruptions.

## Visual Language
- **UI Theme**: Use a medical-themed dashboard (e.g., AdminLTE 3 or similar Bootstrap-based template).
- **Responsive Design**: Mobile-first approach for all data entry screens.
- **UI Density**: "Comfortable" density for touch-friendly interactions on mobile devices.

## Code Context & Assets
- **Framework**: CodeIgniter 4 (CI4)
- **Frontend**: Bootstrap 5, jQuery, AJAX
- **Patterns**: MVC (CI4), Repository/Service patterns for data logic.
