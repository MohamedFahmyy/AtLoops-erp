<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class CashAccountRepository
{
    private PDO $pdo;

    public function __construct(?string $connection = null)
    {
        $this->pdo = Database::pdo($connection);
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT ca.*,
                (SELECT COUNT(*) FROM cash_transactions WHERE cash_account_id = ca.id) AS tx_count
            FROM `cash_accounts` ca
            ORDER BY ca.id ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `cash_accounts` WHERE `id` = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getTransactions(int $accountId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM `cash_transactions`
            WHERE `cash_account_id` = :id
            ORDER BY `id` DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
