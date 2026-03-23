# Roadmap: Nurse Ward Patient Statistics System

## Phases

- [ ] **Phase 1: Foundation & Daily Data Entry** - Secure environment for nurses to record daily patient counts on any device.
- [ ] **Phase 2: Monthly Reporting & Metrics** - Automated monthly summaries and productivity calculations.
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
- [ ] 01-01-PLAN.md — Initialize CI4 and setup Shield Authentication.
- [ ] 01-03-PLAN.md — Implement Ward management CRUD.
- [ ] 01-02-PLAN.md — Implement user registration approval workflow and admin dashboard.
- [ ] 01-04-PLAN.md — Create shift-based census recording transactional foundation and UI.
- [ ] 01-05-PLAN.md — Implement AJAX auto-save and input validation for census data.

### Phase 2: Monthly Reporting & Metrics
**Goal**: Automate monthly summary generation and productivity calculations.
**Depends on**: Phase 1
**Requirements**: REPT-01, REPT-02, REPT-03
**Success Criteria** (what must be TRUE):
  1. System generates monthly summary reports for specific departments.
  2. Productivity % and total patient counts are automatically calculated from daily records.
  3. The tabular report view matches the hospital's existing Excel template structure.
**Plans**: TBD

### Phase 3: Interactive Dashboards
**Goal**: Provide visual insights into patient trends and departmental performance.
**Depends on**: Phase 2
**Requirements**: DASH-01, DASH-02, DASH-03
**Success Criteria** (what must be TRUE):
  1. Dashboard displays monthly patient trend bar graphs using Chart.js.
  2. Management can view and compare metrics across multiple departments.
  3. Interactive filters allow users to slice data by month and year.
**Plans**: TBD

## Progress Tracking

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Foundation & Daily Data Entry | 0/5 | Not started | - |
| 2. Monthly Reporting & Metrics | 0/0 | Not started | - |
| 3. Interactive Dashboards | 0/0 | Not started | - |
