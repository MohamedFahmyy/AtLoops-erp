<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class OpportunityRepository
{
    private PDO $pdo;

    public function __construct(?string $connection = null)
    {
        $this->pdo = Database::pdo($connection);
    }

    public function getAll(?string $stage = null, ?string $currency = null): array
    {
        $sql = "
            SELECT o.*,
                c.name AS company_name,
                CONCAT(ct.first_name, ' ', COALESCE(ct.last_name, '')) AS contact_name,
                u.name AS assigned_to_name
            FROM `opportunities` o
            JOIN `companies` c ON c.id = o.company_id
            LEFT JOIN `contacts` ct ON ct.id = o.contact_id
            LEFT JOIN `users` u ON u.id = o.assigned_to
            WHERE 1=1
        ";
        $params = [];

        if ($stage) {
            $sql .= " AND o.stage = :stage";
            $params['stage'] = $stage;
        }

        if ($currency) {
            $sql .= " AND o.currency = :currency";
            $params['currency'] = $currency;
        }

        $sql .= " ORDER BY o.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*,
                c.name AS company_name,
                CONCAT(ct.first_name, ' ', COALESCE(ct.last_name, '')) AS contact_name,
                u.name AS assigned_to_name
            FROM `opportunities` o
            JOIN `companies` c ON c.id = o.company_id
            LEFT JOIN `contacts` ct ON ct.id = o.contact_id
            LEFT JOIN `users` u ON u.id = o.assigned_to
            WHERE o.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO `opportunities` (
                `company_id`, `contact_id`, `assigned_to`, `title`,
                `stage`, `probability`, `estimated_value`, `currency`, `expected_close_date`
            ) VALUES (
                :company_id, :contact_id, :assigned_to, :title,
                :stage, :probability, :estimated_value, :currency, :expected_close_date
            )
        ");

        $stmt->execute([
            'company_id' => (int)$data['company_id'],
            'contact_id' => !empty($data['contact_id']) ? (int)$data['contact_id'] : null,
            'assigned_to' => !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null,
            'title' => trim((string)$data['title']),
            'stage' => $data['stage'] ?? 'new',
            'probability' => (int)($data['probability'] ?? 20),
            'estimated_value' => (float)($data['estimated_value'] ?? 0.00),
            'currency' => $data['currency'] ?? 'EGP',
            'expected_close_date' => !empty($data['expected_close_date']) ? $data['expected_close_date'] : null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateStage(int $id, string $stage): bool
    {
        $probability = match ($stage) {
            'new' => 10,
            'qualified' => 30,
            'contacted' => 40,
            'meeting' => 50,
            'proposal' => 70,
            'negotiation' => 85,
            'won' => 100,
            'lost' => 0,
            default => 20,
        };

        $stmt = $this->pdo->prepare("
            UPDATE `opportunities`
            SET `stage` = :stage, `probability` = :prob, `updated_at` = NOW()
            WHERE `id` = :id
        ");

        return $stmt->execute([
            'stage' => $stage,
            'prob' => $probability,
            'id' => $id,
        ]);
    }
}
