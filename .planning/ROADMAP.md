# Roadmap: Nurse Ward Patient Statistics System

## Phases

- [x] **Phase 1: Foundation & Daily Data Entry** - Secure environment for nurses to record daily patient counts on any device.
- [x] **Phase 2: Monthly Reporting & Metrics** - Automated monthly summaries and productivity calculations.
- [ ] **Phase 3: Interactive Dashboards** - Visual insights into patient trends and departmental performance.

## Phase Details

### Phase 1: Foundation & Daily Data Entry
**Goal**: Enable nurses to securely record daily patient counts on any device.
**Depends on**: Nothing
**Requirements**: AUTH-01, AUTH-02, AUTH-03, DEPT-01, DEPT-02, DEPT-03, DATA-01, DATA-02, DATA-03, DATA-04, DATA-05, UI-01, UI-02, UI-03
**Success Criteria** (what must be TRUE):
  1. User can log in and session persists across browser refreshes.
  2. Role-Based Access Control restricts feature access (e.g., Nurse vs. Super Admin).
  3. Super Admin can dynamically manage ward/department lists.
  4. Nurse can submit daily patient census via an AJAX-powered form on both PC and mobile.
  5. Input validation prevents submission of invalid data (e.g., negative counts).
**Plans**: 5 plans
- [x] 01-01-PLAN.md — Initialize CI4 and setup Shield Authentication.
- [x] 01-03-PLAN.md — Implement Ward management CRUD.
- [x] 01-02-PLAN.md — Implement user registration approval workflow and admin dashboard.
- [x] 01-04-PLAN.md — Create shift-based census recording transactional foundation and UI.
- [x] 01-05-PLAN.md — Implement AJAX auto-save and input validation for census data.

### Phase 2: Monthly Reporting & Metrics
**Goal**: Automate monthly summary generation and productivity calculations.
**Depends on**: Phase 1
**Requirements**: REPT-01, REPT-02, REPT-03, EXCL-01
**Success Criteria** (what must be TRUE):
  1. System generates monthly summary reports for specific departments.
  2. Productivity % and total patient counts are automatically calculated from daily records.
  3. The tabular report view matches the hospital's existing Excel template structure.
  4. User can export the monthly reports to Excel (.xlsx) files.
**Plans**: 3 plans
- [x] 02-01-PLAN.md — Implement ReportService Aggregation Logic and Metrics Math.
- [x] 02-02-PLAN.md — Create web-based Monthly Summary Dashboard.
- [x] 02-03-PLAN.md — Implement Excel Export functionality.

### Phase 3: Interactive Dashboards
**Goal**: Provide visual insights into patient trends and departmental performance.
**Depends on**: Phase 2
**Requirements**: DASH-01, DASH-02, DASH-03
**Success Criteria** (what must be TRUE):
  1. Dashboard displays monthly patient trend bar graphs using Chart.js.
  2. Management can view and compare metrics across multiple departments.
  3. Interactive filters allow users to slice data by month and year.
**Plans**: 1 plan
- [x] 03-01-PLAN.md — Build interactive trend and comparison dashboard with month/year filters.

## Progress Tracking

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Foundation & Daily Data Entry | 5/5 | Completed | 2026-03-24 |
| 2. Monthly Reporting & Metrics | 3/3 | Completed | 2026-03-24 |
| 3. Interactive Dashboards | 1/1 | Completed | 2026-03-24 |
