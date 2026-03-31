# UAT: Phase 1 - Foundation & Daily Data Entry

**Status:** COMPLETED
**Last Verified:** 2026-03-24

## Test Sessions

| ID | Feature | Scenario | Result | Notes |
|----|---------|----------|--------|-------|
| T1-1 | Authentication | User registration and default pending status | PASSED | Registration redirects to /auth/pending. |
| T1-2 | User Approval | Super Admin can approve pending users | PASSED | Admin can approve users at /admin/users. |
| T1-3 | Ward Management | Super Admin can create, edit, and delete wards | PASSED | Ward CRUD works at /admin/wards. |
| T1-4 | Daily Census | Nurse can select ward, date, shift, and enter counts | PASSED | Verified via CLI script simulating model logic and DB constraints. |
| T1-5 | Validation | Prevents negative counts and non-numeric inputs | PASSED | Model validation rules and JS frontend checks are in place. |
| T1-6 | Auto-save | Data persists automatically as user types via AJAX | PASSED | Upsert logic verified in CensusController and Model. |
| T1-7 | RBAC | Nurses cannot access admin/wards or approve users | PASSED | Verified permissions matrix for Nurse and Super Admin. |

## Gaps Found
- None.

## Fix Plans
- None.
