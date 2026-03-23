# CLAUDE.md: Nurse Ward Patient Statistics System

## Project Overview

A web-based patient statistics and productivity tracking system for nursing wards.
- **Framework**: CodeIgniter 4 (CI4)
- **Frontend**: AJAX, jQuery, Bootstrap 5 (Responsive)
- **Database**: MySQL
- **Core Value**: Accurate and efficient daily recording of patient data for monthly management reporting.

## GSD Workflow

This project follows the **Get Shit Done (GSD)** framework.

### Available Commands

- `/gsd:new-project` — Initialize project (Complete ✓)
- `/gsd:discuss-phase 1` — Research and design Phase 1
- `/gsd:plan-phase 1` — Create execution plan for Phase 1
- `/gsd:execute-phase 1` — Start building Phase 1
- `/gsd:complete-phase 1` — Finalize and verify Phase 1
- `/gsd:status` — Check project status

### Planning Artifacts

- `.planning/PROJECT.md` — Project context and goals
- `.planning/REQUIREMENTS.md` — Scoped requirements and traceability
- `.planning/ROADMAP.md` — Phase structure and success criteria
- `.planning/STATE.md` — Current execution state
- `.planning/config.json` — Workflow preferences
- `.planning/research/` — Domain research findings

## Development Guide

- **Style**: CI4 MVC architecture. Use Entities for domain logic.
- **Frontend**: All data entry must use AJAX to handle interruptions. Auto-save is preferred.
- **Reporting**: Monthly summaries must match existing Excel report formats.
- **Security**: Implement Role-Based Access Control (RBAC) via CI4 Shield or custom middleware.

## Roadmap Summary

1. **Phase 1: Foundation & Daily Data Entry**
   - Auth, Ward Management, Daily Census Recording (AJAX).
2. **Phase 2: Monthly Reporting & Metrics**
   - Monthly summary generation and productivity (NHPPD) calculations.
3. **Phase 3: Interactive Dashboards**
   - Visual trend graphs and departmental comparisons (Chart.js).

---
*Last updated: 2026-03-23*
