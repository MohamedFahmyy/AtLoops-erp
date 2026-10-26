# AT Loops OS — Financial Engine Rules & Math Specification

## 1. Golden Rules of Financial Engineering

1. **No Floating-Point Arithmetic for Money:** All financial computations must be executed using fixed-point decimal arithmetic with explicit rounding. Never use IEEE 754 binary floating points (`float`, `double`) to represent monetary balances.
2. **Single Authoritative Calculation Engine:** All figures displayed on the UI, persisted to the database, rendered on PDF invoices, and summarized on financial reports are produced by `App\Services\FinancialCalculationService`. No controller, repository, or view may invent or re-implement invoice math.
3. **Multi-Currency Strict Isolation:** Financial amounts in different currencies (`EGP`, `SAR`, `USD`, `EUR`) must **never be summed together** without an explicit, documented conversion exchange rate. Cash accounts and financial reports group balances strictly by currency.
4. **Immutable Financial History:** Finalized financial records (paid invoices, recorded payments, and cash ledger transactions) must never be silently edited or physically deleted. Corrections must take the form of authorized reversals, refunds, or debit/credit adjustments.

---

## 2. Authoritative Invoice Formula

The calculation engine processes an invoice in four sequential steps:

### Step 1: Line Item Subtotal
For each line item $i \in \{1, \dots, n\}$:
$$\text{Line Total}_i = \text{round}(\text{Quantity}_i \times \text{Unit Price}_i, 2)$$
$$\text{Document Subtotal} = \sum_{i=1}^{n} \text{Line Total}_i$$

### Step 2: Discount Application
- If `discount_type` is **percentage**:
  $$\text{Discount Amount} = \text{round}\left(\text{Subtotal} \times \frac{\min(\text{Percentage}, 100)}{100}, 2\right)$$
- If `discount_type` is **fixed**:
  $$\text{Discount Amount} = \min(\text{Fixed Amount}, \text{Subtotal})$$
- Taxable Base Amount:
  $$\text{Taxable Base} = \max(0, \text{Subtotal} - \text{Discount Amount})$$

### Step 3: Tax Calculation
$$\text{Tax Amount} = \text{round}\left(\text{Taxable Base} \times \frac{\text{Tax Rate}}{100}, 2\right)$$
*(Default Egyptian Value Added Tax: $\text{Tax Rate} = 14.00\%$)*

### Step 4: Final Total
$$\text{Total Amount} = \text{Taxable Base} + \text{Tax Amount}$$

---

## 3. Payment Allocations & Status Derivation

### 3.1 Outstanding Balance Calculation
$$\text{Outstanding Balance} = \text{Total Amount} - \sum \text{Valid Allocated Payments}$$

### 3.2 Allocation Constraints
- **Overpayment Prevention:** The sum of allocated payments against an invoice cannot exceed the `Total Amount`. Any allocation attempt that causes $\sum \text{Allocated} > \text{Total Amount}$ is rejected and rolled back.
- **Payment Exhaustion:** A payment cannot be allocated for an amount greater than its unallocated remainder:
  $$\text{Available to Allocate} = \text{Payment Amount} - \sum_{\text{other}} \text{Allocations}$$
- **Currency Parity:** The currency of the payment must strictly match the currency of the invoice being settled.

### 3.3 Status State Machine
- **`draft`:** Invoice created but not yet finalized or sent to client.
- **`unpaid`:** Finalized with $\text{Paid Amount} = 0$ and $\text{Due Date} \ge \text{Today}$.
- **`partially_paid`:** $0 < \text{Paid Amount} < \text{Total Amount}$.
- **`paid`:** $\text{Paid Amount} = \text{Total Amount}$.
- **`overdue`:** $\text{Paid Amount} < \text{Total Amount}$ and $\text{Due Date} < \text{Today}$.
- **`void`:** Officially cancelled prior to collection; outstanding balance zeroed.

---

## 4. Atomic Inter-Account Cash Transfers

Internal transfers between company accounts (e.g. from CIB Current Bank Account to Commercial International Bank Tax Reserve, or Petty Cash) are governed by `CashAccountTransferService`:

1. **Pre-Conditions Validation:**
   - Source Account and Destination Account must exist and be active.
   - Source Account and Destination Account cannot be the same entity.
   - Both accounts must operate in the **same currency**.
   - Transfer Amount must be strictly greater than $0.00$.
   - Source Account current balance must satisfy: $\text{Source Balance} \ge \text{Transfer Amount}$.
2. **Transaction Execution:**
   - Both account rows are acquired inside a transaction using `SELECT ... FOR UPDATE` ordered by `id ASC` to eliminate deadlocks.
   - Generate unique shared `transfer_link_id`.
   - Record `transfer_out` transaction against Source Account:
     $$\text{Source Balance}_{\text{new}} = \text{Source Balance}_{\text{old}} - \text{Amount}$$
   - Record `transfer_in` transaction against Destination Account:
     $$\text{Dest Balance}_{\text{new}} = \text{Dest Balance}_{\text{old}} + \text{Amount}$$
   - Update both accounts' `current_balance` columns atomically.
3. **Audit & Rollback:**
   - If any step throws an exception, the entire transfer rolls back completely, preserving previous balances.
   - Append-only audit record created detailing actor, accounts, and transfer reference.

---

## 5. Transaction Reversals

When an erroneous payment must be reversed:
1. Validate authorization (`finance.manage`).
2. Mark payment row as `status = 'reversed'` with timestamp and reason.
3. For each invoice previously allocated by this payment:
   - Deduct the allocation amount from `invoices.paid_amount`.
   - Recompute and update invoice status (e.g. from `paid` back to `partially_paid` or `unpaid`).
4. Reconcile affected cash account balance by deducting the reversed amount.
5. Create reversing ledger transaction entry.
6. The original payment record remains in the database to preserve historical auditability.
