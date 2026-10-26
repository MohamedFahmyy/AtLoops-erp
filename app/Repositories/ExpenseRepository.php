<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Auth;
use App\Core\Database;
use PDO;

class ExpenseRepository
{
    private PDO $pdo;

    public function __construct(?string $connection = null)
    {
        $this->pdo = Database::pdo($connection);
    }

    public function getAll(?string $currency = null): array
    {
        $sql = "
            SELECT e.*,
                ec.name AS category_name,
                v.name AS vendor_name,
                ca.name AS account_name,
                p.name AS project_name,
                u.name AS user_name
            FROM `expenses` e
            JOIN `expense_categories` ec ON ec.id = e.category_id
            LEFT JOIN `vendors` v ON v.id = e.vendor_id
            JOIN `cash_accounts` ca ON ca.id = e.cash_account_id
            LEFT JOIN `projects` p ON p.id = e.project_id
            JOIN `users` u ON u.id = e.user_id
            WHERE 1=1
        ";
        $params = [];

        if ($currency) {
            $sql .= " AND e.currency = :currency";
            $params['currency'] = $currency;
        }

        $sql .= " ORDER BY e.expense_date DESC, e.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategories(): array
    {
        return $this->pdo->query("SELECT * FROM `expense_categories` ORDER BY `name` ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVendors(): array
    {
        return $this->pdo->query("SELECT * FROM `vendors` ORDER BY `name` ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        return Database::transaction(function (PDO $pdo) use ($data) {
            $amount = round((float)($data['amount'] ?? 0), 2);
            $accountId = (int)$data['cash_account_id'];

            // Lock cash account
            $accStmt = $pdo->prepare("SELECT * FROM `cash_accounts` WHERE `id` = :id FOR UPDATE");
            $accStmt->execute(['id' => $accountId]);
            $account = $accStmt->fetch(PDO::FETCH_ASSOC);

            if (!$account) {
                throw new \RuntimeException("Selected cash account not found.");
            }

            $expNumber = 'EXP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

            $stmt = $pdo->prepare("
                INSERT INTO `expenses` (
                    `expense_number`, `category_id`, `vendor_id`, `cash_account_id`,
                    `project_id`, `user_id`, `expense_date`, `amount`, `currency`,
                    `description`, `receipt_path`, `status`
                ) VALUES (
                    :exp_num, :cat_id, :vendor_id, :account_id,
                    :project_id, :user_id, :expense_date, :amount, :currency,
                    :description, :receipt_path, 'paid'
                )
            ");

            $stmt->execute([
                'exp_num' => $expNumber,
                'cat_id' => (int)$data['category_id'],
                'vendor_id' => !empty($data['vendor_id']) ? (int)$data['vendor_id'] : null,
                'account_id' => $accountId,
                'project_id' => !empty($data['project_id']) ? (int)$data['project_id'] : null,
                'user_id' => Auth::id() ?? 1,
                'expense_date' => $data['expense_date'] ?? date('Y-m-d'),
                'amount' => $amount,
                'currency' => $account['currency'],
                'description' => trim((string)$data['description']),
                'receipt_path' => $data['receipt_path'] ?? null,
            ]);

            $expId = (int)$pdo->lastInsertId();

            // Deduct from cash account
            $newBal = round((float)$account['current_balance'] - $amount, 2);
            $updAcc = $pdo->prepare("UPDATE `cash_accounts` SET `current_balance` = :bal, `updated_at` = NOW() WHERE `id` = :id");
            $updAcc->execute(['bal' => $newBal, 'id' => $accountId]);

            // Cash Transaction Record
            $txStmt = $pdo->prepare("
                INSERT INTO `cash_transactions` (
                    `cash_account_id`, `type`, `amount`, `balance_after`,
                    `currency`, `reference_type`, `reference_id`, `description`, `transaction_date`
                ) VALUES (
                    :account_id, 'withdrawal', :amount, :balance_after,
                    :currency, 'expense', :reference_id, :description, NOW()
                )
            ");
            $txStmt->execute([
                'account_id' => $accountId,
                'amount' => $amount,
                'balance_after' => $newBal,
                'currency' => $account['currency'],
                'reference_id' => $expId,
                'description' => "Expense #{$expNumber}: " . substr($data['description'], 0, 50),
            ]);

            return $expId;
        });
    }
}
