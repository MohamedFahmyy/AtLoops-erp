<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use InvalidArgumentException;
use PDO;
use RuntimeException;

class PaymentAllocationService
{
    /**
     * Record a payment and allocate it against an invoice within a strict database transaction.
     *
     * @param array{
     *   invoice_id: int,
     *   cash_account_id: int,
     *   amount: float|int|string,
     *   payment_date: string,
     *   payment_method: string,
     *   reference_number?: ?string,
     *   idempotency_key?: ?string,
     *   notes?: ?string
     * } $data
     * @return array
     */
    public static function recordPayment(array $data, ?string $connection = null): array
    {
        $amount = round((float)($data['amount'] ?? 0), 2);
        if ($amount <= 0.00) {
            throw new InvalidArgumentException("Payment amount must be strictly greater than zero.");
        }

        $invoiceId = (int)($data['invoice_id'] ?? 0);
        $accountId = (int)($data['cash_account_id'] ?? 0);
        $idempotencyKey = !empty($data['idempotency_key']) ? trim((string)$data['idempotency_key']) : null;

        $pdo = Database::pdo($connection);

        // 1. Idempotency Check
        if ($idempotencyKey) {
            $stmt = $pdo->prepare("SELECT * FROM `payments` WHERE `idempotency_key` = :key LIMIT 1");
            $stmt->execute(['key' => $idempotencyKey]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                return [
                    'payment' => $existing,
                    'is_replay' => true,
                    'message' => 'Payment already processed with this idempotency key.'
                ];
            }
        }

        try {
            return Database::transaction(function (PDO $pdo) use ($data, $amount, $invoiceId, $accountId, $idempotencyKey) {
            // 2. Lock & Fetch Invoice
            $invStmt = $pdo->prepare("SELECT * FROM `invoices` WHERE `id` = :id FOR UPDATE");
            $invStmt->execute(['id' => $invoiceId]);
            $invoice = $invStmt->fetch(PDO::FETCH_ASSOC);

            if (!$invoice) {
                throw new RuntimeException("Invoice #{$invoiceId} not found.");
            }

            if ($invoice['status'] === 'void') {
                throw new RuntimeException("Cannot allocate payment to a voided invoice.");
            }

            $currentPaid = (float)$invoice['paid_amount'];
            $totalAmount = (float)$invoice['total_amount'];
            $outstanding = FinancialCalculationService::calculateOutstanding($totalAmount, $currentPaid);

            if ($amount > $outstanding) {
                throw new InvalidArgumentException(
                    "Payment amount ({$amount}) exceeds the invoice outstanding balance ({$outstanding})."
                );
            }

            // 3. Lock & Fetch Cash Account
            $accStmt = $pdo->prepare("SELECT * FROM `cash_accounts` WHERE `id` = :id FOR UPDATE");
            $accStmt->execute(['id' => $accountId]);
            $account = $accStmt->fetch(PDO::FETCH_ASSOC);

            if (!$account || !($account['is_active'] ?? 1)) {
                throw new RuntimeException("Selected cash account is invalid or inactive.");
            }

            // Currency matching check
            if ($account['currency'] !== $invoice['currency']) {
                throw new InvalidArgumentException(
                    "Currency mismatch: Invoice is in {$invoice['currency']}, but Account is in {$account['currency']}."
                );
            }

            // 4. Create Payment Record
            $paymentNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $receivedBy = Auth::id() ?? 1;

            $payStmt = $pdo->prepare("
                INSERT INTO `payments` (
                    `payment_number`, `company_id`, `cash_account_id`, `received_by`,
                    `payment_date`, `amount`, `currency`, `payment_method`,
                    `reference_number`, `idempotency_key`, `notes`, `status`
                ) VALUES (
                    :payment_number, :company_id, :cash_account_id, :received_by,
                    :payment_date, :amount, :currency, :payment_method,
                    :reference_number, :idempotency_key, :notes, 'completed'
                )
            ");

            $payStmt->execute([
                'payment_number' => $paymentNumber,
                'company_id' => $invoice['company_id'],
                'cash_account_id' => $accountId,
                'received_by' => $receivedBy,
                'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                'amount' => $amount,
                'currency' => $invoice['currency'],
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'reference_number' => $data['reference_number'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'notes' => $data['notes'] ?? null,
            ]);

            $paymentId = (int)$pdo->lastInsertId();

            // 5. Create Payment Allocation
            $allocStmt = $pdo->prepare("
                INSERT INTO `payment_allocations` (`payment_id`, `invoice_id`, `allocated_amount`)
                VALUES (:payment_id, :invoice_id, :allocated_amount)
            ");
            $allocStmt->execute([
                'payment_id' => $paymentId,
                'invoice_id' => $invoiceId,
                'allocated_amount' => $amount,
            ]);

            // 6. Update Invoice Paid Amount & Status
            $newPaid = round($currentPaid + $amount, 2);
            $newStatus = FinancialCalculationService::determineInvoiceStatus(
                $totalAmount,
                $newPaid,
                $invoice['due_date'],
                $invoice['status']
            );

            $updateInv = $pdo->prepare("
                UPDATE `invoices`
                SET `paid_amount` = :paid_amount, `status` = :status, `updated_at` = NOW()
                WHERE `id` = :id
            ");
            $updateInv->execute([
                'paid_amount' => $newPaid,
                'status' => $newStatus,
                'id' => $invoiceId,
            ]);

            // 7. Update Cash Account Balance & Record Transaction
            $newAccBalance = round((float)$account['current_balance'] + $amount, 2);
            $updateAcc = $pdo->prepare("
                UPDATE `cash_accounts`
                SET `current_balance` = :current_balance, `updated_at` = NOW()
                WHERE `id` = :id
            ");
            $updateAcc->execute([
                'current_balance' => $newAccBalance,
                'id' => $accountId,
            ]);

            $txStmt = $pdo->prepare("
                INSERT INTO `cash_transactions` (
                    `cash_account_id`, `type`, `amount`, `balance_after`,
                    `currency`, `reference_type`, `reference_id`, `description`, `transaction_date`
                ) VALUES (
                    :account_id, 'deposit', :amount, :balance_after,
                    :currency, 'payment', :reference_id, :description, NOW()
                )
            ");
            $txStmt->execute([
                'account_id' => $accountId,
                'amount' => $amount,
                'balance_after' => $newAccBalance,
                'currency' => $account['currency'],
                'reference_id' => $paymentId,
                'description' => "Payment #{$paymentNumber} for Invoice #{$invoice['invoice_number']}",
            ]);

            // 8. Audit Log
            AuditLogger::log('payment.create', 'payment', $paymentId, null, [
                'payment_number' => $paymentNumber,
                'amount' => $amount,
                'currency' => $invoice['currency'],
                'invoice_id' => $invoiceId,
                'account_id' => $accountId,
            ]);

            return [
                'payment' => [
                    'id' => $paymentId,
                    'payment_number' => $paymentNumber,
                    'amount' => $amount,
                    'invoice_id' => $invoiceId,
                    'new_invoice_status' => $newStatus,
                    'new_account_balance' => $newAccBalance,
                ],
                'is_replay' => false,
                'message' => 'Payment recorded and allocated successfully.'
            ];
        }, $connection);
        } catch (\Throwable $e) {
            // Concurrency-safe idempotency: if another worker inserted the payment concurrently
            if ($idempotencyKey && (
                $e->getCode() == 23000 ||
                str_contains($e->getMessage(), '1062') ||
                str_contains(strtolower($e->getMessage()), 'duplicate') ||
                str_contains(strtolower($e->getMessage()), 'idempotency_key')
            )) {
                $checkStmt = $pdo->prepare("SELECT * FROM `payments` WHERE `idempotency_key` = :key LIMIT 1");
                $checkStmt->execute(['key' => $idempotencyKey]);
                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if ($existing) {
                    return [
                        'payment' => $existing,
                        'is_replay' => true,
                        'message' => 'Payment already processed with this idempotency key.'
                    ];
                }
            }
            throw $e;
        }
    }

    /**
     * Reverse a completed payment, restoring invoice balances and cash account.
     */
    public static function reversePayment(int $paymentId, string $reason, ?string $connection = null): bool
    {
        return Database::transaction(function (PDO $pdo) use ($paymentId, $reason) {
            // Lock payment
            $stmt = $pdo->prepare("SELECT * FROM `payments` WHERE `id` = :id FOR UPDATE");
            $stmt->execute(['id' => $paymentId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                throw new RuntimeException("Payment #{$paymentId} not found.");
            }

            if ($payment['status'] === 'reversed') {
                throw new RuntimeException("Payment #{$paymentId} is already reversed.");
            }

            $amount = (float)$payment['amount'];
            $accountId = (int)$payment['cash_account_id'];

            // Find all allocations
            $allocStmt = $pdo->prepare("SELECT * FROM `payment_allocations` WHERE `payment_id` = :payment_id FOR UPDATE");
            $allocStmt->execute(['payment_id' => $paymentId]);
            $allocations = $allocStmt->fetchAll(PDO::FETCH_ASSOC);

            // Revert allocations from invoices
            foreach ($allocations as $alloc) {
                $invId = (int)$alloc['invoice_id'];
                $allocAmount = (float)$alloc['allocated_amount'];

                $invStmt = $pdo->prepare("SELECT * FROM `invoices` WHERE `id` = :id FOR UPDATE");
                $invStmt->execute(['id' => $invId]);
                $invoice = $invStmt->fetch(PDO::FETCH_ASSOC);

                if ($invoice) {
                    $newPaid = max(0.00, round((float)$invoice['paid_amount'] - $allocAmount, 2));
                    $newStatus = FinancialCalculationService::determineInvoiceStatus(
                        (float)$invoice['total_amount'],
                        $newPaid,
                        $invoice['due_date'],
                        $invoice['status']
                    );

                    $updateInv = $pdo->prepare("UPDATE `invoices` SET `paid_amount` = :paid, `status` = :status WHERE `id` = :id");
                    $updateInv->execute(['paid' => $newPaid, 'status' => $newStatus, 'id' => $invId]);
                }
            }

            // Deduct payment amount from cash account
            $accStmt = $pdo->prepare("SELECT * FROM `cash_accounts` WHERE `id` = :id FOR UPDATE");
            $accStmt->execute(['id' => $accountId]);
            $account = $accStmt->fetch(PDO::FETCH_ASSOC);

            if ($account) {
                $newBal = round((float)$account['current_balance'] - $amount, 2);
                $updateAcc = $pdo->prepare("UPDATE `cash_accounts` SET `current_balance` = :bal WHERE `id` = :id");
                $updateAcc->execute(['bal' => $newBal, 'id' => $accountId]);

                // Record reversal withdrawal
                $txStmt = $pdo->prepare("
                    INSERT INTO `cash_transactions` (
                        `cash_account_id`, `type`, `amount`, `balance_after`,
                        `currency`, `reference_type`, `reference_id`, `description`, `transaction_date`
                    ) VALUES (
                        :account_id, 'withdrawal', :amount, :balance_after,
                        :currency, 'reversal', :reference_id, :description, NOW()
                    )
                ");
                $txStmt->execute([
                    'account_id' => $accountId,
                    'amount' => $amount,
                    'balance_after' => $newBal,
                    'currency' => $account['currency'],
                    'reference_id' => $paymentId,
                    'description' => "Reversal of Payment #{$payment['payment_number']}: {$reason}",
                ]);
            }

            // Update payment record to reversed
            $updatePay = $pdo->prepare("
                UPDATE `payments`
                SET `status` = 'reversed', `reversed_at` = NOW(), `reversed_by` = :by, `reversal_reason` = :reason
                WHERE `id` = :id
            ");
            $updatePay->execute([
                'by' => Auth::id() ?? 1,
                'reason' => $reason,
                'id' => $paymentId,
            ]);

            AuditLogger::log('payment.reverse', 'payment', $paymentId, $payment, [
                'status' => 'reversed',
                'reason' => $reason,
            ]);

            return true;
        }, $connection);
    }
}
