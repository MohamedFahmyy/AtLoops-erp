<?php

declare(strict_types=1);

namespace Tests\Financial;

use App\Core\Database;
use App\Services\CashAccountTransferService;
use PDO;
use RuntimeException;

class CashTransferTest
{
    private string $conn = 'testing';

    public function run(): void
    {
        $pdo = Database::pdo($this->conn);
        $pdo->exec("DELETE FROM `cash_transactions` WHERE `transfer_link_id` LIKE 'TXF-TEST-%'");

        $this->testAtomicCashTransfer();
        $this->testInsufficientFundsRejection();
        $this->testCurrencyMismatchRejection();
        $this->testAtomicFailureRollback();
    }

    private function testAtomicCashTransfer(): void
    {
        echo "  [Test] Atomic inter-account transfer and balance reconciliation... ";

        $pdo = Database::pdo($this->conn);

        // Fetch two EGP accounts
        $accounts = $pdo->query("SELECT id, current_balance FROM `cash_accounts` WHERE `currency` = 'EGP' LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
        if (count($accounts) < 2) {
            throw new RuntimeException("Need at least 2 EGP accounts for transfer test");
        }

        $acc1 = $accounts[0];
        $acc2 = $accounts[1];

        $bal1Before = (float)$acc1['current_balance'];
        $bal2Before = (float)$acc2['current_balance'];

        // Transfer 1,000 from acc1 to acc2
        $transferAmount = 1000.00;
        $result = CashAccountTransferService::transfer(
            (int)$acc1['id'],
            (int)$acc2['id'],
            $transferAmount,
            "Transfer Test",
            "TXF-TEST-KEY-001",
            $this->conn
        );

        $bal1After = (float)$pdo->query("SELECT current_balance FROM `cash_accounts` WHERE id = {$acc1['id']}")->fetchColumn();
        $bal2After = (float)$pdo->query("SELECT current_balance FROM `cash_accounts` WHERE id = {$acc2['id']}")->fetchColumn();

        if (round($bal1Before - $bal1After, 2) !== $transferAmount) {
            throw new RuntimeException("Source debit mismatch: before={$bal1Before}, after={$bal1After}");
        }
        if (round($bal2After - $bal2Before, 2) !== $transferAmount) {
            throw new RuntimeException("Destination credit mismatch: before={$bal2Before}, after={$bal2After}");
        }

        echo "PASSED\n";
    }

    private function testInsufficientFundsRejection(): void
    {
        echo "  [Test] Insufficient funds transfer rejection... ";

        $pdo = Database::pdo($this->conn);
        $accounts = $pdo->query("SELECT id, current_balance FROM `cash_accounts` WHERE `currency` = 'EGP' LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
        $acc1 = $accounts[0];
        $acc2 = $accounts[1];

        $insufficientAmount = (float)$acc1['current_balance'] + 500000.00;

        $rejected = false;
        try {
            CashAccountTransferService::transfer(
                (int)$acc1['id'],
                (int)$acc2['id'],
                $insufficientAmount,
                "Overdraft test",
                null,
                $this->conn
            );
        } catch (\InvalidArgumentException $e) {
            $rejected = true;
        }

        if (!$rejected) {
            throw new RuntimeException("Transfer with insufficient funds was not rejected!");
        }

        echo "PASSED\n";
    }

    private function testCurrencyMismatchRejection(): void
    {
        echo "  [Test] Currency mismatch transfer rejection... ";

        $pdo = Database::pdo($this->conn);
        $egpAccId = (int)$pdo->query("SELECT id FROM `cash_accounts` WHERE `currency` = 'EGP' LIMIT 1")->fetchColumn();
        $usdAccId = (int)$pdo->query("SELECT id FROM `cash_accounts` WHERE `currency` = 'USD' LIMIT 1")->fetchColumn();

        $rejected = false;
        try {
            CashAccountTransferService::transfer(
                $egpAccId,
                $usdAccId,
                100.00,
                "Cross currency test",
                null,
                $this->conn
            );
        } catch (\InvalidArgumentException $e) {
            $rejected = true;
        }

        if (!$rejected) {
            throw new RuntimeException("Cross-currency transfer without explicit conversion rate was not rejected!");
        }

        echo "PASSED\n";
    }

    private function testAtomicFailureRollback(): void
    {
        echo "  [Test] Transaction failure injection & complete rollback verification... ";

        $pdo = Database::pdo($this->conn);
        $accounts = $pdo->query("SELECT id, current_balance FROM `cash_accounts` WHERE `currency` = 'EGP' LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
        $acc1 = $accounts[0];
        $acc2 = $accounts[1];

        $bal1Before = (float)$acc1['current_balance'];
        $bal2Before = (float)$acc2['current_balance'];

        $exceptionTriggered = false;

        try {
            // Attempt a multi-step financial operation that deliberately fails midway after debit
            Database::transaction(function (PDO $pdo) use ($acc1, $acc2) {
                // Step 1: Debit source account
                $pdo->exec("UPDATE `cash_accounts` SET `current_balance` = `current_balance` - 999.00 WHERE id = {$acc1['id']}");

                // Step 2: Injected failure simulating network partition or constraint crash
                throw new \RuntimeException("INJECTED_CRITICAL_FAILURE_BEFORE_CREDIT");

                // Step 3 (Unreachable): Credit destination account
                $pdo->exec("UPDATE `cash_accounts` SET `current_balance` = `current_balance` + 999.00 WHERE id = {$acc2['id']}");
            }, $this->conn);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'INJECTED_CRITICAL_FAILURE_BEFORE_CREDIT') {
                $exceptionTriggered = true;
            }
        }

        if (!$exceptionTriggered) {
            throw new RuntimeException("Failure injection did not trigger expected exception!");
        }

        // Verify that Database::transaction rolled back Step 1 completely
        $bal1After = (float)$pdo->query("SELECT current_balance FROM `cash_accounts` WHERE id = {$acc1['id']}")->fetchColumn();
        $bal2After = (float)$pdo->query("SELECT current_balance FROM `cash_accounts` WHERE id = {$acc2['id']}")->fetchColumn();

        if ($bal1Before !== $bal1After) {
            throw new RuntimeException("Rollback failed! Source balance was modified: before={$bal1Before}, after={$bal1After}");
        }

        if ($bal2Before !== $bal2After) {
            throw new RuntimeException("Rollback failed! Destination balance was modified: before={$bal2Before}, after={$bal2After}");
        }

        echo "PASSED\n";
    }
}
