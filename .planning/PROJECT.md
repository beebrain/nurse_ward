# Nurse Ward Patient Statistics System

## What This Is

A web-based application designed for nurses to record daily patient counts across various departments. The system provides monthly statistical summaries and dashboards (primarily bar graphs) for management. It features dynamic department management to allow Super Admins to add new departments as needed.

The system is built using the CodeIgniter 4 (CI4) framework, with AJAX and jQuery as primary front-end technologies to ensure a responsive and smooth experience on both PC and mobile devices.

## Core Value

Accurate and efficient daily recording of patient data to enable data-driven monthly management decisions.

## Requirements

### Validated

(None yet)

### Active (v1)

- **Daily Recording**: Nurses can record daily patient counts for their assigned department.
- **Monthly Summary**: Management can view monthly statistical summaries of patient data.
- **Dashboard**: Interactive bar graphs to visualize patient statistics for each month.
- **Dynamic Departments**: Super Admin can create and manage departments dynamically.
- **Responsive UI**: Optimized for both PC and mobile use.
- **Framework & Tech**: Built on CI4 with AJAX and jQuery.

### Backlog (Future)

- (None yet)

### Out of Scope

- (None yet)

## Key Decisions

- **Framework**: CodeIgniter 4 (CI4)
- **Front-end**: AJAX, jQuery, Responsive (Bootstrap or similar)
- **Data Input**: Daily per department (based on Excel structure)
- **Frequency**: 1 Excel file = 1 month of data (system will mimic this)

## Key Context

- Users: Nurses (recording), Management (viewing), Super Admin (management).
- Device: PC and Mobile support.
- Performance: Use AJAX for smooth interactions.
