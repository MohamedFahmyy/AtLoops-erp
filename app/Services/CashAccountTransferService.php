<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use InvalidArgumentException;
use PDO;
use RuntimeException;

class CashAccountTransferService
{
    /**
     * Execute an internal cash transfer between two accounts within a strict atomic transaction.
     *
     * @param int $fromAccountId
     * @param int $toAccountId
     * @param float|int|string $amount
     * @param string|null $notes
     * @param string|null $idempotencyKey
     * @param string|null $connection
     * @return array
     */
    public static function transfer(
        int $fromAccountId,
        int $toAccountId,
        mixed $amount,
        ?string $notes = null,
        ?string $idempotencyKey = null,
        ?string $connection = null
    ): array {
        $amount = round((float)$amount, 2);
        if ($amount <= 0.00) {
            throw new InvalidArgumentException("Transfer amount must be strictly greater than zero.");
        }

        if ($fromAccountId === $toAccountId) {
            throw new InvalidArgumentException("Source and destination accounts cannot be the same.");
        }

        $pdo = Database::pdo($connection);

        // Idempotency check using transfer_link_id
        if ($idempotencyKey) {
            $checkStmt = $pdo->prepare("SELECT * FROM `cash_transactions` WHERE `transfer_link_id` = :key LIMIT 1");
            $checkStmt->execute(['key' => $idempotencyKey]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                return [
                    'transfer_link_id' => $idempotencyKey,
                    'is_replay' => true,
                    'message' => 'Transfer already executed with this reference key.'
                ];
            }
        }

        $transferLinkId = $idempotencyKey ?: ('TXF-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)));

        return Database::transaction(function (PDO $pdo) use ($fromAccountId, $toAccountId, $amount, $notes, $transferLinkId) {
            // Lock accounts in consistent order by ID to avoid database deadlocks
            $firstId = min($fromAccountId, $toAccountId);
            $secondId = max($fromAccountId, $toAccountId);

            $lockStmt = $pdo->prepare("SELECT * FROM `cash_accounts` WHERE `id` IN (:first, :second) FOR UPDATE");
            $lockStmt->execute(['first' => $firstId, 'second' => $secondId]);
            $accounts = $lockStmt->fetchAll(PDO::FETCH_ASSOC);

            $accountsById = [];
            foreach ($accounts as $acc) {
                $accountsById[(int)$acc['id']] = $acc;
            }

            if (!isset($accountsById[$fromAccountId])) {
                throw new RuntimeException("Source cash account not found.");
            }
            if (!isset($accountsById[$toAccountId])) {
                throw new RuntimeException("Destination cash account not found.");
            }

            $fromAcc = $accountsById[$fromAccountId];
            $toAcc = $accountsById[$toAccountId];

            if (!($fromAcc['is_active'] ?? 1) || !($toAcc['is_active'] ?? 1)) {
                throw new RuntimeException("One or both cash accounts are deactivated.");
            }

            // Enforce currency compatibility
            if ($fromAcc['currency'] !== $toAcc['currency']) {
                throw new InvalidArgumentException(
                    "Cross-currency transfer without explicit conversion rate is prohibited. " .
                    "Source is {$fromAcc['currency']}, Destination is {$toAcc['currency']}."
                );
            }

            $currentSourceBal = (float)$fromAcc['current_balance'];
            if ($currentSourceBal < $amount) {
                throw new InvalidArgumentException(
                    "Insufficient funds in source account '{$fromAcc['name']}'. " .
                    "Available: {$currentSourceBal} {$fromAcc['currency']}, Required: {$amount} {$fromAcc['currency']}."
                );
            }

            // Calculate new balances
            $newSourceBal = round($currentSourceBal - $amount, 2);
            $newDestBal = round((float)$toAcc['current_balance'] + $amount, 2);

            // Update source account
            $updFrom = $pdo->prepare("UPDATE `cash_accounts` SET `current_balance` = :bal, `updated_at` = NOW() WHERE `id` = :id");
            $updFrom->execute(['bal' => $newSourceBal, 'id' => $fromAccountId]);

            // Update destination account
            $updTo = $pdo->prepare("UPDATE `cash_accounts` SET `current_balance` = :bal, `updated_at` = NOW() WHERE `id` = :id");
            $updTo->execute(['bal' => $newDestBal, 'id' => $toAccountId]);

            // Insert Source Debit (transfer_out)
            $txStmt = $pdo->prepare("
                INSERT INTO `cash_transactions` (
                    `cash_account_id`, `type`, `amount`, `balance_after`,
                    `currency`, `reference_type`, `transfer_link_id`, `description`, `transaction_date`
                ) VALUES (
                    :account_id, :type, :amount, :balance_after,
                    :currency, 'transfer', :transfer_link_id, :description, NOW()
                )
            ");

            $descSource = "Transfer to [{$toAcc['name']}]" . ($notes ? " - {$notes}" : "");
            $txStmt->execute([
                'account_id' => $fromAccountId,
                'type' => 'transfer_out',
                'amount' => $amount,
                'balance_after' => $newSourceBal,
                'currency' => $fromAcc['currency'],
                'transfer_link_id' => $transferLinkId,
                'description' => $descSource,
            ]);

            // Insert Destination Credit (transfer_in)
            $descDest = "Transfer from [{$fromAcc['name']}]" . ($notes ? " - {$notes}" : "");
            $txStmt->execute([
                'account_id' => $toAccountId,
                'type' => 'transfer_in',
                'amount' => $amount,
                'balance_after' => $newDestBal,
                'currency' => $toAcc['currency'],
                'transfer_link_id' => $transferLinkId,
                'description' => $descDest,
            ]);

            AuditLogger::log('cash_account.transfer', 'cash_account', $fromAccountId, null, [
                'from_account_id' => $fromAccountId,
                'to_account_id' => $toAccountId,
                'amount' => $amount,
                'currency' => $fromAcc['currency'],
                'transfer_link_id' => $transferLinkId,
            ]);

            return [
                'transfer_link_id' => $transferLinkId,
                'from_account_id' => $fromAccountId,
                'to_account_id' => $toAccountId,
                'amount' => $amount,
                'currency' => $fromAcc['currency'],
                'new_source_balance' => $newSourceBal,
                'new_destination_balance' => $newDestBal,
                'is_replay' => false,
                'message' => 'Inter-account transfer completed successfully.'
            ];
        }, $connection);
    }
}
