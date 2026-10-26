# AT Loops OS — System Architecture & Design Specification

## 1. Architectural Philosophy

AT Loops OS is designed with a **Clean Architecture** approach implemented in **Native PHP 8.4+**, strictly eliminating heavy full-stack frameworks (such as Laravel, Symfony, or Node.js runtimes) while maintaining industry-standard enterprise software engineering patterns:

- **Single Responsibility Principle (SRP):** Controllers only handle HTTP transport; business logic resides strictly in Services; data storage queries reside in Repositories.
- **Explicit Authoritative Calculations:** Financial formulas, VAT calculations, and discount rules are centralized in a single mathematical engine (`FinancialCalculationService`) to eliminate drift across controllers, UI, PDFs, and reports.
- **Transactional & Idempotent Operations:** State-changing financial and sales operations are governed by database transactions with row-level locks and idempotency keys to prevent race conditions and duplicate entries.
- **Fail-Safe Security by Default:** Prepared statements for 100% of queries, synchronizer token CSRF protection on all state-altering verbs, server-side RBAC, and strict isolation of uploaded files outside the web root.

---

## 2. Layered Component Architecture

```
HTTP Request (Browser / Fetch API)
       │
       ▼
[public/index.php] ── Front Controller
       │
       ▼
[app/Core/Application] ── Bootstrap, Dotenv, Exception Handling, SAPI Detection
       │
       ▼
[app/Core/Router] ── Route Matching, Param Extraction, Middleware Pipeline
       │
       ├── Middleware: [CSRFMiddleware] -> [AuthMiddleware] -> [RoleMiddleware] -> [PermissionMiddleware]
       │
       ▼
[app/Controllers/*] ── Input Extraction, HTTP Validation, View/JSON Dispatch
       │
       ▼
[app/Services/*] ── Domain Rules, Business Transactions, Financial Math, Idempotency
       │
       ├── Authoritative Services:
       │     - FinancialCalculationService (Decimal Math, VAT, Discounts, Rounding)
       │     - PaymentAllocationService (Atomic Payment & Invoices Allocation, Reversals)
       │     - CashAccountTransferService (Atomic Inter-Account Ledger Transfers)
       │     - ConversionService (Lead -> Opp -> Quote -> Contract -> Project)
       │     - RecurringBillingService (Automated Retainer Billing & Cycle Advancement)
       │     - ProjectProfitabilityService (Cost, Margins, Health Classification)
       │     - AuditLogger (Append-Only Sensitive Event Logging)
       │     - PDFService (Arabic Bi-Directional TCPDF Generation)
       │
       ▼
[app/Repositories/*] ── PDO Prepared Statements, Complex Data Queries, Aggregations
       │
       ▼
[app/Core/Database] ── PDO Singleton, Strict Transaction Lifecycle, Connection Pools
       │
       ▼
[MySQL 8.x Database Engine] (InnoDB, Foreign Keys, Row Locks)
```

---

## 3. Core Framework Subsystems (`app/Core/`)

### 3.1 Application (`Application.php`)
- Initializes environment configuration via `vlucas/phpdotenv`.
- Sets up unified PHP error and uncaught exception handlers.
- Detects CLI versus web SAPI to prevent sending HTTP headers during terminal test runs.
- In production (`APP_DEBUG=false`), conceals all stack traces, database credentials, and internal paths, returning a generic, localized HTTP 500 error view.

### 3.2 Routing & Middleware Pipeline (`Router.php`)
- Fast regex-based route matcher with typed URI parameter capture (`{id:\d+}`).
- Supports standard RESTful verbs (`GET`, `POST`, `PUT`, `DELETE`) with HTML form method spoofing (`_method`).
- Supports route grouping with prefixing and nested middleware stacks.

### 3.3 Database Singleton (`Database.php`)
- Manages PDO connections for both production (`mysql`) and isolated testing (`testing`).
- Configured with `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`, `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`, and `PDO::ATTR_EMULATE_PREPARES => false`.
- Provides an atomic `transaction(callable $callback, ?string $conn = null)` helper that manages `beginTransaction()`, execution, rollback on exception, and commit.

### 3.4 Session & Security (`Session.php`, `CSRF.php`, `RateLimiter.php`)
- **Session Management:** Enforces cookie security parameters (`HttpOnly`, `SameSite=Lax`, environment-aware `Secure` flag), 120-minute idle expiration, and session ID regeneration upon privilege transition.
- **CSRF Token:** Synchronizer token pattern with cryptographically secure random bytes (`bin2hex(random_bytes(32))`) and timing-safe comparison via `hash_equals()`. Token is validated for `POST`, `PUT`, and `DELETE`.
- **Rate Limiter:** File-based leaky bucket implementation tracking IP and user account attempts (locks out after 5 consecutive failed attempts for 5 minutes).

### 3.5 Internationalization Engine (`I18n.php`)
- Supports dynamic locale negotiation between Arabic (`ar`) and English (`en`).
- Loads dictionary arrays from `app/Views/lang/{locale}/`.
- Provides the global helper `__('key.subkey')` and RTL detection helper `is_rtl()`.
- Sets `dir="rtl"` and loads Cairo font for Arabic, and `dir="ltr"` with Inter font for English.

---

## 4. Service Layer Responsibilities

1. **`FinancialCalculationService`:** Authoritative source of financial truth. Implements precision decimal calculations for invoice subtotals, line items, discounts (percentage or capped fixed), 14% Egyptian VAT, and standard rounding.
2. **`PaymentAllocationService`:** Manages money collections. Validates invoice outstanding balance using row-level locking (`SELECT ... FOR UPDATE`), enforces idempotency, handles partial payments, and manages full transaction reversals.
3. **`CashAccountTransferService`:** Executes inter-account fund transfers atomically, recording debit and credit transaction ledger entries with a shared `transfer_link_id` within a single database transaction.
4. **`ConversionService`:** Manages the CRM sales pipeline conversions (`Lead -> Opportunity -> Quotation -> Contract -> Project`), enforcing workflow invariants, idempotency, and foreign key integrity.
5. **`RecurringBillingService`:** Automates recurring client retainers (monthly, quarterly, annually) by inspecting due contracts, generating draft invoices, and advancing billing cycles safely.
6. **`AuditLogger`:** Records sensitive actions into an append-only `audit_logs` table, stripping passwords, authorization tokens, and private secrets from log payloads.
7. **`PDFService`:** Generates branded, RTL-compliant PDF invoices using TCPDF with UTF-8 Arabic text shaping.
