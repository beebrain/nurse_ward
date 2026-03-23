# Architecture: Daily Recording & Productivity System

**Domain:** Nurse Ward Management (Productivity tracking)
**Researched:** March 2024
**Core Question:** Efficient daily patient count recording and monthly aggregation in CI4.

## Recommended Architecture

A standard MVC approach using **CI4 Entities** for business logic and **Database Views** for efficient monthly aggregation.

### 1. Database Design (Schema)

The schema separates master data (Wards, Acuity Levels) from daily transaction data (Census, Acuity, Staffing).

#### Master Data
- **Wards**: `id`, `name`, `total_beds`
- **AcuityLevels**: `id`, `level_name` (e.g., Level 1-5), `standard_hours` (e.g., 2.0, 4.0)

#### Daily Transactions
- **DailyCensus**: `id`, `ward_id`, `record_date`, `admit`, `discharge`, `transfer_in`, `transfer_out`, `death`, `total_remaining`
- **DailyAcuity**: `id`, `census_id`, `acuity_level_id`, `patient_count`
- **DailyStaffing**: `id`, `census_id`, `shift_type` (M/E/N), `rn_count`, `pn_count`, `na_count`, `total_hours`

### 2. MVC Structure in CI4

| Component | Responsibility | Communicates With |
|-----------|---------------|-------------------|
| `DailyRecordModel` | Primary data access for daily entries. | `DailyCensus`, `DailyAcuity`, `DailyStaffing` tables |
| `ProductivityEntity` | Holds logic for calculating required vs actual hours. | Used by Models to map results. |
| `DailyEntryController` | Receives and validates nurse input. | `DailyRecordModel` |
| `ProductivityService` | Calculates KPIs (NHPPD, Productivity %). | `DailyRecordModel`, `WardModel` |
| `ReportController` | Aggregates data for monthly views. | `ProductivityService` |

### 3. Data Flow

1.  **Input**: Nurses enter daily patient counts (Census), Acuity levels, and Staff counts.
2.  **Processing**: The `ProductivityService` calculates:
    -   `Required Hours` = Σ (Acuity Count * Standard Hours)
    -   `Actual Hours` = Σ (Staff Count * Shift Hours)
    -   `Productivity %` = (Required / Actual) * 100
3.  **Storage**: Transactional data is stored across three tables (`DailyCensus`, `DailyAcuity`, `DailyStaffing`).
4.  **Aggregation**: Monthly reports use a SQL View to join and sum these tables efficiently.

## Optimization: Monthly Aggregation

For efficient monthly aggregation in CI4, follow these steps:

### A. Use a Database View
Instead of complex JOINs in PHP, create a MySQL View:
```sql
CREATE VIEW v_monthly_productivity AS
SELECT 
    ward_id, 
    YEAR(record_date) as year, 
    MONTH(record_date) as month,
    SUM(total_remaining) as total_patient_days,
    SUM(required_hours) as total_req_hours,
    SUM(actual_hours) as total_act_hours
FROM daily_productivity_summary -- Pre-calculated daily table or complex JOIN
GROUP BY ward_id, year, month;
```

### B. CI4 Query Builder for Aggregation
When using the Query Builder:
```php
$builder = $this->db->table('daily_census');
$builder->select('MONTH(record_date) as month, SUM(total_remaining) as total_patients');
$builder->where('YEAR(record_date)', $year);
$builder->groupBy('month');
$results = $builder->get()->getResultArray();
```

## Patterns to Follow

### Pattern 1: Repository for Aggregation
**What:** Encapsulate the complex monthly reporting queries in a Repository or a specialized Model method.
**Why:** Keeps controllers clean and logic reusable for both web views and Excel exports.

### Pattern 2: Entity-Based Productivity Logic
**What:** Use a CI4 Entity for a `DailyRecord` that has methods like `calculateRequiredHours()`.
**Example:**
```typescript
class DailyRecord extends \CodeIgniter\Entity\Entity {
    public function getProductivityPercentage() {
        return ($this->required_hours / $this->actual_hours) * 100;
    }
}
```

## Anti-Patterns to Avoid

- **Fat Controllers**: Moving the calculation logic (Productivity %) into the Controller.
- **Loop-and-Query**: Fetching all daily records and calculating monthly totals in a PHP `foreach` loop. Use SQL `SUM()` and `GROUP BY`.
- **Schema Overlap**: Storing calculated Productivity % in the `DailyCensus` table without the source data. Always store raw counts so you can re-calculate if standard hours change.

## Scalability Considerations

| Concern | At 1 ward (Daily) | At 50 wards (Monthly) | At 1M records (History) |
|---------|-------------------|-----------------------|-------------------------|
| Query Performance | Negligible | Fast with indexes on `ward_id`, `record_date` | May need a Materialized View for monthly reports. |
| UI Responsiveness | Fast | Pagination needed for report tables. | Async loading of charts (Chart.js via AJAX). |

## Sources
- [CodeIgniter 4 Model Documentation](https://codeigniter.com/user_guide/models/model.html)
- [Database View Best Practices](https://dev.mysql.com/doc/refman/8.0/en/views.html)
- [Nursing Productivity Models (NHPPD)](https://www.cdc.gov/niosh/topics/healthcare/staffing.html)
