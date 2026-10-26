# AT Loops OS — Automated Testing Suite & QA Specification

## 1. Testing Philosophy

The AT Loops OS automated test framework provides strict, deterministic regression and acceptance verification without relying on external testing dependencies or mock networks.

All tests execute against an isolated database target (`atloops_os_test`), guaranteeing that production data in `atloops_os` is never touched, modified, or truncated.

---

## 2. Test Execution Command

```bash
php tests/run_tests.php
```

---

## 3. Test Suites & Acceptance Cases

### Suite 1: Financial Math & Calculations (`Tests\Financial\FinancialCalculationTest`)
- **Multi-line Invoice Calculation:** Multi-item invoices with decimal quantities, discounts, and 14% Egyptian VAT compute exact mathematical totals without floating-point drift.
- **Fixed Discount Capping:** Validates boundary conditions where fixed discount exceeds document subtotal (subtotal capped at 0.00 without negative taxable balance).
- **Outstanding Balance & Transitions:** Verifies invoice status transitions (`unpaid`, `partially_paid`, `paid`, `overdue`) derived accurately from collections and due dates.

### Suite 2: Payment Allocations & Reversals (`Tests\Financial\PaymentAllocationTest`)
- **Two Partial Payments:** Verifies that sequential partial payments update `invoices.paid_amount` incrementally and reconcile `total_amount - paid_amount = outstanding`.
- **Overpayment Prevention:** Rejects any allocation attempt where `allocated_amount > outstanding_balance` with a database rollback.
- **Idempotent Payment Replays:** Replaying payment requests with identical `idempotency_key` returns the existing payment record without creating duplicates.
- **Payment Reversal Workflow:** Reversing a finalized payment decrements cash account balance, deducts paid amounts from affected invoices, restores invoice statuses, and retains immutable audit log entries.

### Suite 3: Cash Accounts & Atomic Transfers (`Tests\Financial\CashTransferTest`)
- **Atomic Ledger Transfers:** Inter-account transfer validates debit from Source, credit to Destination, balance updates, and linking reference IDs in a single atomic transaction.
- **Insufficient Funds Guard:** Rejects transfers where `amount > source_account.current_balance`.
- **Currency Mismatch Guard:** Rejects cross-currency transfers (e.g., EGP to USD) unless an explicit conversion workflow is supplied.

### Suite 4: Security Acceptance Tests (`Tests\Security\SecurityAcceptanceTest`)
- **CSRF Token Enforcement:** State-changing verbs (`POST`, `PUT`, `DELETE`) without valid CSRF tokens receive HTTP 419 Page Expired.
- **RBAC & Permission Gates:** Accessing restricted controller actions with insufficient role permissions receives HTTP 403 Forbidden.
- **Prohibited File Uploads:** Uploading files with executable extensions (`.php`, `.exe`, `.sh`, `.phtml`) is blocked by validation and MIME inspection.
- **Production Error Concealment:** When `APP_DEBUG=false`, uncaught exceptions conceal stack traces, database credentials, and internal paths from HTTP responses.

### Suite 5: Operational Idempotency & Localization (`Tests\Operational\OperationalAcceptanceTest`)
- **Sales Conversion Idempotency:** Converting Lead to Opportunity, Quotation to Contract, and Contract to Project is idempotent. Repeated submissions are rejected.
- **Recurring Draft Generation Idempotency:** Recurring billing cron worker creates exactly one draft invoice per contract period. Re-running on the same date produces 0 duplicate drafts.
- **Localization Dictionary Integrity:** Validates that Arabic (`ar`) returns Cairo RTL strings and English (`en`) returns Inter LTR strings without missing dictionary keys.
