# Feature Landscape: Nurse Ward Productivity

**Domain:** Nurse Ward Management
**Researched:** March 2024

## Table Stakes (Core Features)

These features are expected in a professional nursing management system.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Daily Census Entry | Primary data source for admissions/discharges. | Low | Must include validations (e.g., total remaining must match formula). |
| Acuity Assessment | Determines workload intensity. | Medium | Mapping patient counts to 5 standard levels. |
| Staffing Recording | Tracks resources (RN/PN/NA count). | Low | Required for productivity calculation. |
| Monthly Summary Table | Aggregates daily data for ward heads. | Medium | Must handle missing days gracefully. |
| Multi-Ward Support | Hospital-wide usage. | Low | Role-based access (Nurse vs. Head Nurse). |

## Differentiators (Value-Add)

Features that set this system apart from manual Excel tracking.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Automated Productivity Calculation | Real-time (NHPPD) instead of monthly manual work. | Medium | Uses the formula `Required Hours / Actual Hours`. |
| Productivity Trend Visualization | Dashboards showing workload vs. staffing. | Medium | Use Chart.js to show monthly efficiency. |
| Excel Export (Template Match) | Easy reporting for management. | High | Must exactly match the hospital's existing `.xlsx` format. |
| Shift-Level Granularity | Morning/Afternoon/Night productivity. | Medium | Captures variations in staffing levels. |

## Anti-Features

Features to explicitly NOT build to maintain focus.

| Anti-Feature | Why Avoid | What to Do Instead |
|--------------|-----------|-------------------|
| Patient Medical Records | Highly regulated (HIPAA/PDPA), out of scope. | Use only anonymized counts or HNs. |
| HR/Salary Integration | Massive complexity for payroll. | Only track "Worked Hours" for productivity. |
| Billing/Financials | Financial audit trails are complex. | Keep only productivity metrics. |

## Feature Dependencies

```
Daily Census Entry → Acuity Assessment → Productivity Calculation
Staffing Recording  → Productivity Calculation
```

## MVP Recommendation

Prioritize:
1.  **Daily Record Form**: A single view combining Census, Acuity, and Staffing.
2.  **Basic Monthly Table**: Aggregated sums of patients and staff hours.
3.  **Core Productivity Metric**: Display a single "Productivity %" for the month.

Defer:
-   Historical Trend Charts (Add in Phase 2).
-   Multi-Ward Departmental Views (Add in Phase 2).

## Sources
- [Existing Spreadsheet Analysis](C:\xampp\htdocs\nurse_ward\6. ยอดรายวันและ Productivity มี.ค. 68.xlsx)
- [Nursing Productivity Standards (Safer Nursing Care Tool)](https://www.england.nhs.uk/publication/safer-nursing-care-tool/)
