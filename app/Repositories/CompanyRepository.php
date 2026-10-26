<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class CompanyRepository
{
    private PDO $pdo;

    public function __construct(?string $connection = null)
    {
        $this->pdo = Database::pdo($connection);
    }

    public function getAll(?string $status = null, ?string $search = null): array
    {
        $sql = "SELECT c.*,
                    (SELECT COUNT(*) FROM contacts WHERE company_id = c.id) AS contacts_count,
                    (SELECT COUNT(*) FROM projects WHERE company_id = c.id) AS projects_count,
                    (SELECT COUNT(*) FROM invoices WHERE company_id = c.id) AS invoices_count
                FROM `companies` c WHERE 1=1";
        $params = [];

        if ($status) {
            $sql .= " AND c.status = :status";
            $params['status'] = $status;
        }

        if ($search) {
            $sql .= " AND (c.name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        $sql .= " ORDER BY c.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `companies` WHERE `id` = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findWithContacts(int $id): ?array
    {
        $company = $this->find($id);
        if (!$company) {
            return null;
        }

        $contactStmt = $this->pdo->prepare("SELECT * FROM `contacts` WHERE `company_id` = :id ORDER BY `is_primary` DESC, `id` ASC");
        $contactStmt->execute(['id' => $id]);
        $company['contacts'] = $contactStmt->fetchAll(PDO::FETCH_ASSOC);

        return $company;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO `companies` (
                `name`, `industry`, `country`, `city`, `address`, `website`,
                `phone`, `email`, `tax_number`, `status`
            ) VALUES (
                :name, :industry, :country, :city, :address, :website,
                :phone, :email, :tax_number, :status
            )
        ");

        $stmt->execute([
            'name' => trim((string)$data['name']),
            'industry' => $data['industry'] ?? null,
            'country' => $data['country'] ?? 'Egypt',
            'city' => $data['city'] ?? null,
            'address' => $data['address'] ?? null,
            'website' => $data['website'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => !empty($data['email']) ? strtolower(trim((string)$data['email'])) : null,
            'tax_number' => $data['tax_number'] ?? null,
            'status' => $data['status'] ?? 'lead',
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE `companies` SET
                `name` = :name,
                `industry` = :industry,
                `country` = :country,
                `city` = :city,
                `address` = :address,
                `website` = :website,
                `phone` = :phone,
                `email` = :email,
                `tax_number` = :tax_number,
                `status` = :status,
                `updated_at` = NOW()
            WHERE `id` = :id
        ");

        return $stmt->execute([
            'name' => trim((string)$data['name']),
            'industry' => $data['industry'] ?? null,
            'country' => $data['country'] ?? 'Egypt',
            'city' => $data['city'] ?? null,
            'address' => $data['address'] ?? null,
            'website' => $data['website'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => !empty($data['email']) ? strtolower(trim((string)$data['email'])) : null,
            'tax_number' => $data['tax_number'] ?? null,
            'status' => $data['status'] ?? 'lead',
            'id' => $id,
        ]);
    }

    public function checkDuplicate(string $name, ?string $email, ?string $phone, ?int $ignoreId = null): ?array
    {
        $sql = "SELECT id, name, email, phone FROM `companies` WHERE (`name` = :name";
        $params = ['name' => trim($name)];

        if ($email) {
            $sql .= " OR `email` = :email";
            $params['email'] = strtolower(trim($email));
        }
        if ($phone) {
            $sql .= " OR `phone` = :phone";
            $params['phone'] = trim($phone);
        }
        $sql .= ")";

        if ($ignoreId) {
            $sql .= " AND `id` != :ignore_id";
            $params['ignore_id'] = $ignoreId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
