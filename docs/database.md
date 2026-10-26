# AT Loops OS — Database Architecture & Data Dictionary

## 1. Database Standards & Conventions

- **Engine:** MySQL 8.x InnoDB exclusively to support ACID transactions and foreign key constraints.
- **Character Set & Collation:** `utf8mb4` with `utf8mb4_unicode_ci` for full international Arabic and emoji support.
- **Monetary Storage Rule:** All financial balances, amounts, prices, costs, and taxes are strictly stored in `DECIMAL(15,2)`. Binary floating-point types (`FLOAT`, `DOUBLE`) are prohibited for financial calculations.
- **Foreign Key Integrity:** Enforced at database level. State-altering financial relations (such as `payment_allocations` referencing `invoices` or `payments`) utilize `ON DELETE RESTRICT` to prevent silent data loss or orphaning.
- **Audit Columns:** Core business tables feature `created_at` and `updated_at` timestamps with automatic triggers (`ON UPDATE CURRENT_TIMESTAMP`).

---

## 2. Table Catalog (37 Tables)

### Authentication & Access Control
1. **`migrations`**: Migration batch history and timestamps.
2. **`users`**: System operators with hashed passwords (`PASSWORD_BCRYPT`), hourly cost rate, and active status flag.
3. **`roles`**: System roles (`super_admin`, `manager`, `finance_officer`, `developer`, `sales_representative`).
4. **`permissions`**: Granular capability keys categorized by module (`crm.*`, `projects.*`, `finance.*`, `settings.*`).
5. **`role_permissions`**: Many-to-many pivot mapping roles to permissions.
6. **`user_roles`**: Many-to-many pivot mapping users to roles.
7. **`password_resets`**: Expiring, single-use password reset tokens.

### CRM & Sales Pipeline
8. **`companies`**: Client companies and prospective corporate accounts with tax numbers and status (`lead`, `prospect`, `active_client`, `archived`).
9. **`contacts`**: Individual stakeholders linked to companies with email, phone, and role.
10. **`leads`**: Top-of-funnel inbound inquiries with estimated value and currency.
11. **`pipeline_stages`**: Configurable stages for sales opportunities with probability percentage and ordering.
12. **`opportunities`**: Sales deals linked to companies, contacts, and pipeline stages.
13. **`activities`**: CRM engagement log (calls, meetings, emails, reminders).
14. **`quotations`**: Formal sales price proposals with multi-line items, discounts, 14% VAT, expiry dates, and approval status.
15. **`quotation_items`**: Individual service/product lines for quotations.
16. **`contracts`**: Legally binding business agreements linked to projects and quotations.

### Project Management & Execution
17. **`projects`**: Software solutions and IT projects with managers, budgets, currencies, and status (`planning`, `active`, `on_hold`, `completed`, `cancelled`).
18. **`project_members`**: Assigned team members with custom hourly rates.
19. **`milestones`**: Project delivery checkpoints with target completion dates.
20. **`tasks`**: Work items organized into status (`todo`, `in_progress`, `review`, `completed`), priority, and assigned developers.
21. **`subtasks`**: Granular checklists inside tasks.
22. **`task_comments`**: Collaboration discussions on individual tasks.
23. **`time_entries`**: Billable and non-billable time logs linked to tasks, users, and projects with calculated labor costs.
24. **`change_requests`**: Scope change tickets with financial cost impact, hour adjustments, and manager approval gates.

### Operational Finance & Cash Management
25. **`cash_accounts`**: Bank accounts, petty cash registers, and digital wallets with dedicated currency codes, opening balances, and live balances.
26. **`invoices`**: Formal financial billing documents with currency, subtotal, discount, VAT rate/amount, total, paid amount, and derived status (`draft`, `unpaid`, `partially_paid`, `paid`, `overdue`, `void`).
27. **`invoice_items`**: Authoritative line items for invoices.
28. **`payments`**: Recorded cash receipts linked to client companies, cash accounts, and payment methods with idempotency keys.
29. **`payment_allocations`**: Many-to-many junction linking individual payment transactions to invoices with exact allocated amounts.
30. **`expense_categories`**: Categorization for operational overhead (e.g., cloud hosting, salaries, software licenses, office rent).
31. **`vendors`**: Suppliers and service providers with tax IDs.
32. **`expenses`**: Outflow records linked to categories, cash accounts, optional project attribution, and receipt attachments.
33. **`cash_transactions`**: Authoritative ledger recording all inflows, outflows, and transfers with running balance tracking and linking IDs.

### Retainers, Documents, Auditing & Configuration
34. **`recurring_contracts`**: Recurring client service retainers (monthly, quarterly, annual) with automated billing dates.
35. **`documents`**: Document vault metadata for files stored in private storage.
36. **`audit_logs`**: Append-only log recording actor, IP address, user agent, target entity, action, and JSON delta.
37. **`settings`**: Key-value system configurations (company name, VAT registration, default currency, logo).

---

## 3. Indexing & Optimization Strategy

- **Unique Constraints:** Applied to natural keys including `users.email`, `companies.name`, `quotations.quotation_number`, `contracts.contract_number`, `invoices.invoice_number`, `payments.payment_number`, and `payments.idempotency_key`.
- **Composite Indexes:**
  - `recurring_contracts(next_billing_date, status)`: Enables near-instant scanning of due recurring retainers during cron runs.
  - `invoices(company_id, status)`: Optimizes receivables and aging queries per customer.
  - `time_entries(project_id, is_billable)`: Speeds up project labor cost aggregations.
  - `cash_transactions(cash_account_id, created_at)`: Accelerates ledger reconciliation statements.
