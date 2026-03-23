# Research Summary: Nurse Ward Patient Statistics System

## Executive Summary

The **Nurse Ward Patient Statistics System** is a productivity and census tracking application designed to replace manual Excel-based recording in a hospital environment. Built on **CodeIgniter 4**, the system focuses on three core data pillars: **Daily Census** (admissions/discharges), **Patient Acuity** (workload intensity), and **Staffing Levels** (nursing hours). 

The research indicates that the primary value proposition lies in automating the "Nursing Hours per Patient Day" (NHPPD) and "Productivity %" calculations, which are currently manual and error-prone. To succeed, the system must provide a "no-friction" data entry experience for busy nurses—utilizing **AJAX** to prevent data loss during interruptions—and must generate monthly reports that exactly match existing hospital Excel templates. The architectural approach favors a clean MVC structure in CI4 with heavy reliance on Database Views for high-performance monthly aggregations.

## Key Findings

### 🛠️ Technology Stack (Strict Adherence)
*   **Backend:** CodeIgniter 4.4+ (PHP 8.1+). Small footprint, high performance, specifically requested.
*   **Frontend:** jQuery 3.7+ with AJAX for dynamic form handling. Bootstrap 5.3 for mobile-responsive ward use.
*   **Database:** MySQL 8.0 / MariaDB 10.6. Relational structure handles complex joins for productivity metrics.
*   **Reporting:** `PHPOffice/PhpSpreadsheet` for generating the mandatory monthly ".xlsx" reports.
*   **Security:** CodeIgniter Shield for Role-Based Access Control (Nurse vs. Head Nurse).

### ✨ Feature Landscape
*   **Table Stakes:** Daily Census Entry (Admit/DC/Transfer), 5-Level Acuity Assessment, Staffing Recording (RN/PN/NA counts), and Monthly Summary Tables.
*   **Differentiators:** Real-time Productivity % calculation, Trend Visualization (Chart.js), and exact-match Excel Export.
*   **Anti-Features:** NO Medical Records (HIPAA/PDPA risk), NO Payroll/HR integration, NO Billing. Focus is strictly on productivity metrics.

### 🏗️ Architecture & Patterns
*   **Schema:** Decoupled master data (Wards/Acuity Levels) from transactional daily data (Census/Acuity/Staffing).
*   **Logic Location:** Productivity logic (Required vs. Actual Hours) encapsulated in **CI4 Entities** or a **ProductivityService** to keep controllers thin.
*   **Performance:** Use **MySQL Views** for monthly aggregations (e.g., `v_monthly_productivity`) to avoid heavy PHP-side processing.
*   **AJAX Pattern:** Use jQuery AJAX for "Save-as-you-go" functionality to mitigate data loss during ward interruptions.

### ⚠️ Critical Pitfalls
*   **Workflow Interruption:** Nurses are frequently interrupted. A standard "Submit" button isn't enough; the system needs auto-save or draft states.
*   **Double Documentation:** Data must flow from Census to Acuity automatically. Requiring redundant entry will lead to system abandonment.
*   **Acuity Neglect:** Measuring productivity by patient count alone is a "management trap." The system MUST use Acuity weights to reflect actual workload.

## Roadmap Implications

### Suggested Phase Structure

1.  **Phase 1: Foundation & Daily Entry (MVP)**
    *   *Rationale:* Establish the "Single Source of Truth" for daily data.
    *   *Deliverables:* Ward/Acuity setup, User Auth, and a unified Daily Entry Form (Census + Acuity + Staffing) using AJAX.
    *   *Pitfall Mitigation:* Focus on "Save-as-you-go" to handle ward interruptions.

2.  **Phase 2: Monthly Aggregation & Calculation**
    *   *Rationale:* Transform raw daily data into actionable management metrics.
    *   *Deliverables:* SQL Views for monthly summaries, Productivity calculation logic, and a Monthly Review Dashboard.
    *   *Pitfall Mitigation:* Ensure "Total Remaining" patients matches the sum of "Acuity Levels" counts.

3.  **Phase 3: Reporting & Visualization**
    *   *Rationale:* Replace the manual Excel reporting process and provide trend analysis.
    *   *Deliverables:* Excel Export (matching existing hospital template), Chart.js visualizations for 6-month trends.
    *   *Pitfall Mitigation:* Audit Excel formulas from the provided sample to ensure the system replicates them exactly.

### Research Flags
*   **Needs Research:** Specific hospital Excel template formulas (confirming standard hours for each acuity level).
*   **Standard Patterns:** CRUD for Wards, CI4 Shield authentication.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| **Stack** | HIGH | CI4 + jQuery/AJAX is a mature, well-documented approach. |
| **Features** | HIGH | Based on analysis of the existing "ยอดรายวัน" Excel sheet. |
| **Architecture** | HIGH | Standard Relational/MVC model fits this domain perfectly. |
| **Pitfalls** | MEDIUM | Requires careful UX testing with actual nursing staff to avoid "workflow misalignment." |

**Gaps:** Specific "Standard Hours" per acuity level used by the hospital need to be confirmed before final productivity logic is locked.

## Sources
*   *CodeIgniter 4 User Guide* (Official Documentation)
*   *Existing Spreadsheet Analysis* (`6. ยอดรายวันและ Productivity มี.ค. 68.xlsx`)
*   *Nursing Productivity Models (NHPPD/Safer Nursing Care Tool)*
*   *NIH & AMA Research on Nursing Documentation Burden*
