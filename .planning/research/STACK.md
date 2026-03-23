# Technology Stack: Nurse Ward Daily Recording & Productivity System

**Project:** Nurse Ward Management System
**Researched:** March 2024
**Target Framework:** CodeIgniter 4 (CI4)

## Recommended Stack

### Core Framework
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| PHP | 8.1+ | Runtime | Required for CI4 and modern security features. |
| CodeIgniter | 4.4+ | Web Framework | Specifically requested; excellent performance and small footprint. |

### Database
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| MySQL / MariaDB | 8.0+ / 10.6+ | Relational DB | Strong support for aggregation and complex JOINs needed for productivity calculations. |

### Frontend
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Bootstrap | 5.3+ | UI Framework | Mobile-responsive design for tablets/phones used in wards. |
| Chart.js | 4.0+ | Data Viz | Visualizing productivity trends and patient counts. |
| HTMX | 1.9+ | Dynamic UI | Improving form responsiveness without full page reloads (e.g., adding patient rows). |

### Supporting Libraries
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| PHPOffice/PhpSpreadsheet | 2.0+ | Excel Export | Generating the monthly "ยอดรายวัน" report in .xlsx format. |
| Shield | 1.0+ | Auth/Security | Official CI4 authentication and role-based access control (RBAC). |

## Alternatives Considered

| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| Framework | CI4 | Laravel | CI4 has lower overhead and simpler configuration for hospital-local servers. |
| Database | MySQL | SQLite | SQLite lacks advanced window functions and concurrent writing performance needed for multi-ward systems. |

## Installation

```bash
# Core
composer create-project codeigniter4/appstarter nurse_ward

# Authentication
composer require codeigniter4/shield

# Reporting
composer require phpoffice/phpspreadsheet
```

## Sources
- [CodeIgniter 4 User Guide](https://codeigniter.com/user_guide/)
- [Shield Auth Documentation](https://github.com/codeigniter4/shield)
- [PhpSpreadsheet Docs](https://phpspreadsheet.readthedocs.io/)
