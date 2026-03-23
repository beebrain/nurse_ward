# Requirements: Nurse Ward Patient Statistics System

**Defined:** 2026-03-23
**Core Value:** Accurate and efficient daily recording of patient data to enable data-driven monthly management decisions.

## v1 Requirements

### Authentication (AUTH)

- [ ] **AUTH-01**: Secure login for Nurses, Managers, and Super Admins.
- [ ] **AUTH-02**: Role-based access control (RBAC) to restrict features by user role.
- [ ] **AUTH-03**: Session persistence across browser refresh.

### Department Management (DEPT)

- [ ] **DEPT-01**: Super Admin can dynamically create new departments/wards.
- [ ] **DEPT-02**: Super Admin can edit or deactivate existing departments.
- [ ] **DEPT-03**: Departments are dynamically loaded into system selection menus.

### Daily Data Entry (DATA)

- [ ] **DATA-01**: Nurses can select a department and a date for data entry.
- [ ] **DATA-02**: Record daily patient census (e.g., Midnight Census).
- [ ] **DATA-03**: Record admissions, discharges, and transfers (ADT).
- [ ] **DATA-04**: AJAX-based submission to prevent page reload and data loss.
- [ ] **DATA-05**: Input validations to ensure data integrity (e.g., non-negative counts).

### Monthly Summaries & Reports (REPT)

- [ ] **REPT-01**: Generate monthly summary reports for each department.
- [ ] **REPT-02**: Calculate key metrics like total patients and productivity percentages.
- [ ] **REPT-03**: Tabular report view matching the hospital's existing Excel format.

### Dashboard & Visualization (DASH)

- [ ] **DASH-01**: Monthly trend bar graphs for patient statistics using Chart.js or similar.
- [ ] **DASH-02**: Departmental comparison charts for management overview.
- [ ] **DASH-03**: Interactive filters for selecting month and year.

### Responsive UI & Tech (UI)

- [ ] **UI-01**: Responsive layout optimized for both PC and mobile devices.
- [ ] **UI-02**: Implementation using CodeIgniter 4 framework.
- [ ] **UI-03**: Front-end interactions powered by jQuery and AJAX.

## v2 Requirements

- **EXCL-01**: Export reports directly to Excel (.xlsx) files.
- **EXCL-02**: Automated email notifications for missing daily reports.
- **EXCL-03**: Integration with existing hospital EHR systems (LDAP/AD).

## Out of Scope

| Feature | Reason |
|---------|--------|
| Real-time patient tracking | System is for daily statistical aggregation, not clinical monitoring. |
| Patient personal data (PII) | Only counts and statistics are recorded to simplify security/HIPAA compliance. |
| Multi-hospital support | Single hospital instance is the current requirement. |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| AUTH-01 | Phase 1 | Pending |
| AUTH-02 | Phase 1 | Pending |
| AUTH-03 | Phase 1 | Pending |
| DEPT-01 | Phase 1 | Pending |
| DEPT-02 | Phase 1 | Pending |
| DEPT-03 | Phase 1 | Pending |
| DATA-01 | Phase 1 | Pending |
| DATA-02 | Phase 1 | Pending |
| DATA-03 | Phase 1 | Pending |
| DATA-04 | Phase 1 | Pending |
| DATA-05 | Phase 1 | Pending |
| REPT-01 | Phase 2 | Pending |
| REPT-02 | Phase 2 | Pending |
| REPT-03 | Phase 2 | Pending |
| DASH-01 | Phase 3 | Pending |
| DASH-02 | Phase 3 | Pending |
| DASH-03 | Phase 3 | Pending |
| UI-01 | Phase 1 | Pending |
| UI-02 | Phase 1 | Pending |
| UI-03 | Phase 1 | Pending |

**Coverage:**
- v1 requirements: 20 total
- Mapped to phases: 20
- Unmapped: 0
