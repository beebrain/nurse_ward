# Domain Pitfalls: Patient Recording & Nursing Productivity

**Domain:** Health Informatics / Nursing Documentation
**Researched:** March 2026
**Overall Confidence:** HIGH

## Critical Pitfalls (High Risk)

### 1. Workflow Misalignment (The "Linearity" Trap)
*   **What goes wrong:** The system assumes a nurse follows a strict Step 1 → Step 2 → Step 3 flow.
*   **Why it happens:** Designers use standard CRUD (Create, Read, Update, Delete) patterns that don't account for the high-interruption environment of a ward.
*   **Consequences:** Nurses "lose" unsaved data when interrupted, or they develop "paper workarounds" (noting on scraps and entering later), leading to transcription errors.
*   **Prevention:** Support "draft" states, auto-save, and the ability to jump between sections without losing progress.
*   **Detection:** High rates of "incomplete" records or logs showing users navigating away and starting over.

### 2. Double Documentation & Redundancy (Data Integrity)
*   **What goes wrong:** Requiring the same data (e.g., patient count, acuity score) in multiple modules or sheets.
*   **Why it happens:** Fragmented database design where different reports (Productivity vs. Clinical Census) don't share the same source table.
*   **Consequences:** Data inconsistency (Report A says 20 patients, Report B says 22). Nurses spend 20%+ of their time on redundant data entry.
*   **Prevention:** Use a "Single Source of Truth." If a patient is admitted in the census, they should automatically appear in the productivity/acuity list.
*   **Detection:** Audits showing conflicting totals for the same metric across different reports.

### 3. Alert Fatigue & "Noise" (UX)
*   **What goes wrong:** Excessive pop-ups, non-critical validation errors, or constant "reminder" notifications.
*   **Why it happens:** Over-zealous management requirements for "mandatory" fields or safety checks.
*   **Consequences:** Nurses become desensitized. When a *truly* critical error occurs (e.g., incorrect medication dose), it is ignored along with the "noise."
*   **Prevention:** Tiered alerts (Information vs. Warning vs. Critical). Only "Critical" should be interruptive. Use subtle UI cues (colors, icons) for non-critical reminders.
*   **Detection:** High override rates (90%+) on system alerts.

### 4. Delayed or "Batch" Charting
*   **What goes wrong:** Nurses wait until the end of a 12-hour shift to enter all data from the day.
*   **Why it happens:** Clunky UI that requires a desktop workstation, making real-time entry difficult during active care.
*   **Consequences:** Inaccurate timestamps, "recall bias" (guessing values like blood pressure or time of medication), and skewed "time-to-treatment" metrics for management.
*   **Prevention:** Mobile-first or tablet-optimized data entry. Point-of-care workstations. Offline support for zones with poor Wi-Fi.
*   **Detection:** Data entry logs showing massive spikes in activity during the last 30 minutes of a shift.

## Moderate Pitfalls (Reporting & Management)

### 1. Ignoring Patient Acuity in Productivity
*   **What goes wrong:** Measuring productivity solely by "Patient Count" or "Bed Occupancy."
*   **Why it happens:** Easier to calculate than a complex "Nursing Hours per Patient Day" (NHPPD) based on severity.
*   **Consequences:** Understaffing in high-intensity units. A ward with 10 stable patients is very different from 10 critical patients. Management sees "100% occupancy" but misses the "150% workload."
*   **Prevention:** Integrate an Acuity/Level-of-Care score (e.g., 1-5) into the daily totals.
*   **Detection:** High nurse burnout/turnover in units that "on paper" look well-staffed.

### 2. "Ghost" Records (Inaccurate Patient ID)
*   **What goes wrong:** Entering data into the wrong patient's chart.
*   **Why it happens:** Similar names or bed numbers; lack of barcode scanning/photo verification.
*   **Consequences:** Serious clinical errors and "ghost" medical histories that skew longitudinal management reports.
*   **Prevention:** Multi-factor identification (Name + DOB + Room) and mandatory barcode scanning if hardware is available.
*   **Detection:** High volume of record "deletion" or "amendment" requests from nursing supervisors.

### 3. Financial & Reimbursement Under-coding
*   **What goes wrong:** Documentation fails to support the complexity of care provided.
*   **Why it happens:** Nurses focusing on care rather than "billing" language; lack of prompts for high-resource interventions.
*   **Consequences:** Lower reimbursement levels (under-coding) or claim denials during audits.
*   **Prevention:** Real-time prompts for documentation completion based on clinical indicators.
*   **Detection:** High discrepancy between supplies used (from pharmacy/supply logs) and care documented.

## Minor Pitfalls (UX/Maintenance)

### 1. Inflexible Templates
*   **What goes wrong:** Using rigid dropdowns that don't allow for the "patient story."
*   **Prevention:** Provide "Comment" fields alongside structured data, but keep them optional for reporting.

### 2. "Copy-Paste" (Note Cloning)
*   **What goes wrong:** Reusing notes from previous shifts to save time.
*   **Consequences:** "Note bloat" where critical changes are buried in repetitive text.
*   **Prevention:** Disable global copy-paste in clinical notes or implement "smart templates" that highlight only changes.

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|---------------|------------|
| **Data Migration** | Moving from Excel (like `6. ยอดรายวัน...xlsx`) | Audit the Excel formulas first. Many "system" errors are actually legacy formula bugs being ported over. |
| **UX Design** | Mobile vs. Desktop | Don't just "shrink" the desktop view. Nurses use mobile for *entry* and desktop for *review/reports*. |
| **Reporting** | Management Dashboards | Ensure "Total Patients" matches "Sum of Acuity levels" to maintain data integrity. |

## Sources

- [NIH: Nursing Documentation Burden & Data Integrity](https://www.ncbi.nlm.nih.gov/pmc/articles/PMC7349635/)
- [AMA: EHR Usability for Nurses](https://www.ama-assn.org/practice-management/digital/ehr-usability-nurses)
- [HealthIT.gov: Reducing Documentation Burden](https://www.healthit.gov/topic/usability-health-it/reducing-documentation-burden)
- [Joint Commission: Alert Fatigue in Healthcare](https://www.jointcommission.org/resources/patient-safety-topics/alert-fatigue/)
