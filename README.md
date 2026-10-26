# AT Loops OS — Enterprise Business Management System

> **A native, high-performance, enterprise-grade internal business operating system built for AT Loops.**

---

## 1. System Overview

**AT Loops OS** is a custom, production-ready enterprise resource and business management application built specifically for **AT Loops** ([atloops.com](https://atloops.com/)), an IT and software solutions company.

The application delivers end-to-end management of:
- **CRM & Sales:** Companies, Contacts, Leads, Opportunities with pipeline tracking, Quotations with itemized pricing, and Sales Contracts.
- **Project Operations:** Projects, Milestones, Kanban Task Board, Subtasks, Time Entries with billable rates, Project Direct Expenses, and Scope Change Requests with real-time profitability analytics.
- **Operational Finance:** Authoritative Invoices (with 14% VAT, discounts, rounding), Payments, Transaction-safe Payment Allocations, Multi-Currency Cash & Bank Accounts, Atomic Inter-Account Transfers, Expense Tracking, Vendors, and Financial Ledger Reconciliation.
- **Recurring Contracts & Retainers:** Automated, idempotent draft invoice generation for ongoing maintenance, server support, and dev retainers.
- **Secure Document Vault:** Isolated document storage outside public root with strict resource-level permission gates and MIME-validation.
- **Executive Dashboards & BI:** Real-time collection metrics, expense breakdowns, currency-isolated balances, aging receivables, pipeline weighting, and 6-month trends.
- **Bilingual Experience:** Arabic (Default, Cairo font, RTL) & English (Inter font, LTR) with instant switching.

---

## 2. Technology Stack & Principles

- **Runtime & Language:** Native PHP 8.4+ (Strict Types, OOP, MVC Architecture).
- **Zero Heavy Frameworks:** Built without Laravel, Symfony full-stack, CodeIgniter, Vue, React, or Node.js runtime. Maximum performance and zero framework lock-in.
- **Database:** MySQL 8.x with InnoDB, `utf8mb4_unicode_ci`, strict foreign keys (`ON DELETE RESTRICT` on financial records), and `DECIMAL(15,2)` precision for all monetary figures.
- **Persistence Layer:** PDO with 100% prepared statements and row-level locking (`FOR UPDATE`).
- **Frontend:** Bootstrap 5 with native RTL/LTR layouts, Cairo & Inter typography, Vanilla JavaScript (ES6+) Fetch API, and Chart.js.
- **PDF Generation:** TCPDF with Unicode Arabic bi-directional layout support.
- **Security:** Synchronizer token CSRF protection, RBAC with granular permissions, file-based rate limiting, timing-safe string comparison, and production error concealment.

---

## 3. Directory Structure

```
atloops-os/
├── app/
│   ├── Controllers/        # HTTP Request handlers (CRM, Projects, Finance, etc.)
│   ├── Core/               # Micro-framework core (Router, DB, Session, CSRF, I18n, etc.)
│   │   └── Middleware/     # Auth, Guest, Role, Permission, CSRF Middleware
│   ├── Models/             # Domain entities
│   ├── Policies/           # Resource authorization policies
│   ├── Repositories/       # Data access layer & SQL queries
│   ├── Services/           # Authoritative business rules & transactions
│   └── Views/              # Localized PHP view templates (AR/EN)
├── config/                 # Environment and application configuration
├── database/
│   ├── migrations/         # 001_create_all_tables.sql (37 InnoDB tables)
│   ├── seeders/            # DatabaseSeeder.php
│   ├── migrate.php         # CLI migration runner
│   └── seed.php            # CLI seeder runner
├── docs/                   # Full technical & user documentation
├── public/                 # Web server document root (index.php, CSS, JS, assets)
├── routes/                 # Route definitions (web.php)
├── scripts/                # CLI workers (backup.php, restore.php, cron.php)
├── storage/                # Backups, private uploads, logs, cache (outside web root)
└── tests/                  # Automated acceptance test suites
```

---

## 4. Local Installation Guide (Laragon / Apache)

### Prerequisites
- PHP 8.4+ with `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `openssl`.
- MySQL 8.x running on `127.0.0.1:3306`.
- Composer 2.x.

### Step 1: Clone and Install Dependencies
```bash
cd D:\laragon\www\atloops-os
composer install --no-dev --optimize-autoloader
```

### Step 2: Environment Configuration
Copy `.env.example` to `.env` and verify database credentials:
```ini
APP_NAME="AT Loops OS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://atloops-os.test

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=atloops_os
DB_USERNAME=root
DB_PASSWORD=

DB_TEST_DATABASE=atloops_os_test
```

### Step 3: Run Database Migrations & Seeders
```bash
# Migrate production database
php database/migrate.php --database=mysql

# Populate roles, admin accounts, and synthetic demo data
php database/seed.php --database=mysql

# Setup isolated testing database for QA
php database/migrate.php --database=testing
php database/seed.php --database=testing
```

### Step 4: Configure Web Server VirtualHost
In Laragon or Apache:
- **ServerName:** `atloops-os.test`
- **DocumentRoot:** `D:/laragon/www/atloops-os/public`

Or run using PHP built-in server:
```bash
php -S 127.0.0.1:8000 -t public
```

---

## 5. Default User Accounts

| Role | Email | Password | Access Level |
|---|---|---|---|
| **Super Admin** | `admin@atloops.com` | `Password123!` | Full System Administration |
| **Manager** | `manager@atloops.com` | `Password123!` | CRM, Projects, Contracts, Approvals |
| **Finance Officer** | `finance@atloops.com` | `Password123!` | Invoices, Payments, Transfers, Expenses |
| **Developer** | `dev@atloops.com` | `Password123!` | Projects, Kanban, Tasks, Time Logging |
| **Sales Rep** | `sales@atloops.com` | `Password123!` | Leads, Opportunities, Quotations |

---

## 6. Running the Automated Acceptance Test Suite

To execute all 5 mandatory acceptance suites against the isolated test database `atloops_os_test`:
```bash
php tests/run_tests.php
```

Test results summary:
- **Suite 1:** Financial Math & Authoritative Invoice Calculations (`PASS`)
- **Suite 2:** Payment Allocations, Partial Payments & Reversals (`PASS`)
- **Suite 3:** Cash Accounts & Atomic Transfers (`PASS`)
- **Suite 4:** Security Hardening (CSRF, RBAC, File Uploads, Secrets) (`PASS`)
- **Suite 5:** Operational Idempotency, Recurring Billing & AR/EN Dictionaries (`PASS`)

---

## 7. CLI Maintenance & Cron Jobs

### Master Cron Job
Set up a Windows Scheduled Task or Linux cron to run every 15 minutes:
```bash
php scripts/cron.php
```
*Actions performed:*
1. Automatically detects recurring service contracts due for billing and generates draft invoices.
2. Identifies unpaid invoices past due date and transitions status to `overdue`.
3. Checks task deadlines and logs alerts for project managers.

### Database Backup & Verification
```bash
# Generate full SQL dump to storage/backups/ with automated 30-day retention pruning
php scripts/backup.php --database=mysql

# Restore backup into isolated test database for verification
php scripts/restore.php --file=storage/backups/backup_atloops_os_YYYYMMDD_HHMMSS.sql --database=testing
```

---

## 8. License & Commercial Rights
Proprietary software developed exclusively for **AT Loops** ([atloops.com](https://atloops.com/)). All rights reserved.
