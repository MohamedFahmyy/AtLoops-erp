<?php

declare(strict_types=1);

namespace Tests\Financial;

use App\Core\Database;
use App\Services\PaymentAllocationService;
use PDO;
use RuntimeException;

class PaymentAllocationTest
{
    private string $conn = 'testing';

    public function run(): void
    {
        $pdo = Database::pdo($this->conn);
        $pdo->exec("DELETE FROM `payment_allocations` WHERE `payment_id` IN (SELECT id FROM `payments` WHERE `idempotency_key` LIKE 'IDEMP-TEST-%')");
        $pdo->exec("DELETE FROM `payment_allocations` WHERE `invoice_id` IN (SELECT id FROM `invoices` WHERE `invoice_number` IN ('TEST-INV-001', 'TEST-INV-OVERPAY'))");
        $pdo->exec("DELETE FROM `payments` WHERE `idempotency_key` LIKE 'IDEMP-TEST-%'");
        $pdo->exec("DELETE FROM `invoices` WHERE `invoice_number` IN ('TEST-INV-001', 'TEST-INV-OVERPAY')");

        $this->testTwoPartialPaymentsAndIdempotency();
        $this->testOverpaymentRejection();
        $this->testPaymentReversalWorkflow();
    }

    private function testTwoPartialPaymentsAndIdempotency(): void
    {
        echo "  [Test] Two partial payments, outstanding balance & idempotency... ";

        $pdo = Database::pdo($this->conn);

        // Fetch or create a test invoice in testing database
        $pdo->exec("
            INSERT INTO `invoices` (`invoice_number`, `company_id`, `created_by`, `issue_date`, `due_date`, `currency`, `subtotal`, `total_amount`, `paid_amount`, `status`)
            VALUES ('TEST-INV-001', 1, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'EGP', 10000.00, 10000.00, 0.00, 'unpaid')
            ON DUPLICATE KEY UPDATE `paid_amount` = 0.00, `status` = 'unpaid'
        ");
        $invId = (int)$pdo->query("SELECT id FROM `invoices` WHERE `invoice_number` = 'TEST-INV-001'")->fetchColumn();
        $accId = (int)$pdo->query("SELECT id FROM `cash_accounts` WHERE `currency` = 'EGP' LIMIT 1")->fetchColumn();
        $initialAccBal = (float)$pdo->query("SELECT current_balance FROM `cash_accounts` WHERE id = {$accId}")->fetchColumn();

        // 1. First Partial Payment: 4,000 EGP
        $pay1 = PaymentAllocationService::recordPayment([
            'invoice_id' => $invId,
            'cash_account_id' => $accId,
            'amount' => 4000.00,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'idempotency_key' => 'IDEMP-TEST-001',
        ], $this->conn);

        $invAfter1 = $pdo->query("SELECT * FROM `invoices` WHERE id = {$invId}")->fetch(PDO::FETCH_ASSOC);
        if ((float)$invAfter1['paid_amount'] !== 4000.00 || $invAfter1['status'] !== 'partially_paid') {
            throw new RuntimeException("First payment failed: paid={$invAfter1['paid_amount']}, status={$invAfter1['status']}");
        }

        // 2. Replay First Payment Request with same key -> Must be detected as replay
        $replay = PaymentAllocationService::recordPayment([
            'invoice_id' => $invId,
            'cash_account_id' => $accId,
            'amount' => 4000.00,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'idempotency_key' => 'IDEMP-TEST-001',
        ], $this->conn);

        if (!($replay['is_replay'] ?? false)) {
            throw new RuntimeException("Idempotency replay detection failed!");
        }

        // Verify balance was NOT doubled
        $invAfterReplay = $pdo->query("SELECT paid_amount FROM `invoices` WHERE id = {$invId}")->fetchColumn();
        if ((float)$invAfterReplay !== 4000.00) {
            throw new RuntimeException("Idempotency failed: paid amount doubled to {$invAfterReplay}");
        }

        // 3. Second Partial Payment: 6,000 EGP (Full settlement)
        $pay2 = PaymentAllocationService::recordPayment([
            'invoice_id' => $invId,
            'cash_account_id' => $accId,
            'amount' => 6000.00,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'idempotency_key' => 'IDEMP-TEST-002',
        ], $this->conn);

        $invAfter2 = $pdo->query("SELECT * FROM `invoices` WHERE id = {$invId}")->fetch(PDO::FETCH_ASSOC);
        if ((float)$invAfter2['paid_amount'] !== 10000.00 || $invAfter2['status'] !== 'paid') {
            throw new RuntimeException("Second payment failed: paid={$invAfter2['paid_amount']}, status={$invAfter2['status']}");
        }

        // Verify cash account balance increased exactly by 10,000
        $finalAccBal = (float)$pdo->query("SELECT current_balance FROM `cash_accounts` WHERE id = {$accId}")->fetchColumn();
        if (round($finalAccBal - $initialAccBal, 2) !== 10000.00) {
            throw new RuntimeException("Cash account reconciliation error: diff=" . ($finalAccBal - $initialAccBal));
        }

        echo "PASSED\n";
    }

    private function testOverpaymentRejection(): void
    {
        echo "  [Test] Rejection of payments exceeding outstanding balance... ";

        $pdo = Database::pdo($this->conn);

        // Create invoice of 1,000 EGP
        $pdo->exec("
            INSERT INTO `invoices` (`invoice_number`, `company_id`, `created_by`, `issue_date`, `due_date`, `currency`, `subtotal`, `total_amount`, `paid_amount`, `status`)
            VALUES ('TEST-INV-OVERPAY', 1, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'EGP', 1000.00, 1000.00, 0.00, 'unpaid')
            ON DUPLICATE KEY UPDATE `paid_amount` = 0.00, `status` = 'unpaid'
        ");
        $invId = (int)$pdo->query("SELECT id FROM `invoices` WHERE `invoice_number` = 'TEST-INV-OVERPAY'")->fetchColumn();
        $accId = (int)$pdo->query("SELECT id FROM `cash_accounts` WHERE `currency` = 'EGP' LIMIT 1")->fetchColumn();

        $rejected = false;
        try {
            // Attempt paying 1,500 on 1,000 invoice
            PaymentAllocationService::recordPayment([
                'invoice_id' => $invId,
                'cash_account_id' => $accId,
                'amount' => 1500.00,
                'payment_date' => date('Y-m-d'),
                'payment_method' => 'cash',
            ], $this->conn);
        } catch (\InvalidArgumentException $e) {
            $rejected = true;
        }

        if (!$rejected) {
            throw new RuntimeException("Overpayment of 1500 on 1000 invoice was not rejected!");
        }

        echo "PASSED\n";
    }

    private function testPaymentReversalWorkflow(): void
    {
        echo "  [Test] Payment reversal restores balances and preserves audit... ";

        $pdo = Database::pdo($this->conn);

        $invId = (int)$pdo->query("SELECT id FROM `invoices` WHERE `invoice_number` = 'TEST-INV-OVERPAY'")->fetchColumn();
        $accId = (int)$pdo->query("SELECT id FROM `cash_accounts` WHERE `currency` = 'EGP' LIMIT 1")->fetchColumn();
        $initialAccBal = (float)$pdo->query("SELECT current_balance FROM `cash_accounts` WHERE id = {$accId}")->fetchColumn();

        // 1. Record valid 500 EGP payment
        $payResult = PaymentAllocationService::recordPayment([
            'invoice_id' => $invId,
            'cash_account_id' => $accId,
            'amount' => 500.00,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'cash',
        ], $this->conn);
        $payId = (int)$payResult['payment']['id'];

        // 2. Reverse Payment
        PaymentAllocationService::reversePayment($payId, "Customer deposited incorrect check", $this->conn);

        // Verify Payment is marked reversed
        $payRecord = $pdo->query("SELECT * FROM `payments` WHERE id = {$payId}")->fetch(PDO::FETCH_ASSOC);
        if ($payRecord['status'] !== 'reversed') {
            throw new RuntimeException("Payment was not marked reversed!");
        }

        // Verify Invoice paid amount restored to 0
        $invRecord = $pdo->query("SELECT * FROM `invoices` WHERE id = {$invId}")->fetch(PDO::FETCH_ASSOC);
        if ((float)$invRecord['paid_amount'] !== 0.00) {
            throw new RuntimeException("Invoice paid amount not restored: {$invRecord['paid_amount']}");
        }

        // Verify Cash Account balance restored to original
        $restoredAccBal = (float)$pdo->query("SELECT current_balance FROM `cash_accounts` WHERE id = {$accId}")->fetchColumn();
        if ($restoredAccBal !== $initialAccBal) {
            throw new RuntimeException("Cash balance not restored: expected {$initialAccBal}, got {$restoredAccBal}");
        }

        echo "PASSED\n";
    }
}
