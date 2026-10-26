<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class ContactRepository
{
    private PDO $pdo;

    public function __construct(?string $connection = null)
    {
        $this->pdo = Database::pdo($connection);
    }

    public function findByCompany(int $companyId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `contacts` WHERE `company_id` = :id ORDER BY `is_primary` DESC, `id` ASC");
        $stmt->execute(['id' => $companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO `contacts` (
                `company_id`, `first_name`, `last_name`, `email`,
                `phone`, `job_title`, `is_primary`, `provenance`, `lawful_basis`, `opt_out`
            ) VALUES (
                :company_id, :first_name, :last_name, :email,
                :phone, :job_title, :is_primary, :provenance, :lawful_basis, :opt_out
            )
        ");

        $stmt->execute([
            'company_id' => (int)$data['company_id'],
            'first_name' => trim((string)$data['first_name']),
            'last_name' => !empty($data['last_name']) ? trim((string)$data['last_name']) : null,
            'email' => !empty($data['email']) ? strtolower(trim((string)$data['email'])) : null,
            'phone' => !empty($data['phone']) ? trim((string)$data['phone']) : null,
            'job_title' => $data['job_title'] ?? null,
            'is_primary' => !empty($data['is_primary']) ? 1 : 0,
            'provenance' => $data['provenance'] ?? 'direct_inquiry',
            'lawful_basis' => $data['lawful_basis'] ?? 'legitimate_interest',
            'opt_out' => !empty($data['opt_out']) ? 1 : 0,
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
