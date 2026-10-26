<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Auth;
use App\Core\Database;
use App\Services\FinancialCalculationService;
use PDO;

class InvoiceRepository
{
    private PDO $pdo;

    public function __construct(?string $connection = null)
    {
        $this->pdo = Database::pdo($connection);
    }

    public function getAll(?string $status = null, ?string $currency = null): array
    {
        $sql = "
            SELECT i.*,
                c.name AS company_name,
                p.name AS project_name,
                (i.total_amount - i.paid_amount) AS outstanding_amount
            FROM `invoices` i
            JOIN `companies` c ON c.id = i.company_id
            LEFT JOIN `projects` p ON p.id = i.project_id
            WHERE 1=1
        ";
        $params = [];

        if ($status) {
            $sql .= " AND i.status = :status";
            $params['status'] = $status;
        }

        if ($currency) {
            $sql .= " AND i.currency = :currency";
            $params['currency'] = $currency;
        }

        $sql .= " ORDER BY i.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT i.*,
                c.name AS company_name,
                c.address AS company_address,
                c.phone AS company_phone,
                c.email AS company_email,
                c.tax_number AS company_tax_number,
                p.name AS project_name,
                (i.total_amount - i.paid_amount) AS outstanding_amount
            FROM `invoices` i
            JOIN `companies` c ON c.id = i.company_id
            LEFT JOIN `projects` p ON p.id = i.project_id
            WHERE i.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findWithItemsAndPayments(int $id): ?array
    {
        $invoice = $this->find($id);
        if (!$invoice) {
            return null;
        }

        // Items
        $itemStmt = $this->pdo->prepare("SELECT * FROM `invoice_items` WHERE `invoice_id` = :id ORDER BY `item_order` ASC, `id` ASC");
        $itemStmt->execute(['id' => $id]);
        $invoice['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        // Payments
        $payStmt = $this->pdo->prepare("
            SELECT p.*, pa.allocated_amount, ca.name AS account_name
            FROM `payments` p
            JOIN `payment_allocations` pa ON pa.payment_id = p.id
            JOIN `cash_accounts` ca ON ca.id = p.cash_account_id
            WHERE pa.invoice_id = :id
            ORDER BY p.id DESC
        ");
        $payStmt->execute(['id' => $id]);
        $invoice['payments'] = $payStmt->fetchAll(PDO::FETCH_ASSOC);

        return $invoice;
    }

    public function create(array $data, array $items): int
    {
        return Database::transaction(function (PDO $pdo) use ($data, $items) {
            $calc = FinancialCalculationService::calculateDocument(
                $items,
                $data['discount_type'] ?? 'fixed',
                $data['discount_value'] ?? 0.00,
                $data['tax_rate'] ?? 0.00,
                false
            );

            $invNumber = 'INV-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));

            $stmt = $pdo->prepare("
                INSERT INTO `invoices` (
                    `invoice_number`, `project_id`, `company_id`, `created_by`,
                    `issue_date`, `due_date`, `currency`, `subtotal`,
                    `discount_type`, `discount_value`, `discount_amount`,
                    `tax_rate`, `tax_amount`, `total_amount`, `paid_amount`,
                    `status`, `notes`, `terms`
                ) VALUES (
                    :invoice_number, :project_id, :company_id, :created_by,
                    :issue_date, :due_date, :currency, :subtotal,
                    :discount_type, :discount_value, :discount_amount,
                    :tax_rate, :tax_amount, :total_amount, 0.00,
                    :status, :notes, :terms
                )
            ");

            $status = $data['status'] ?? 'unpaid';
            if ($status === 'unpaid' && strtotime($data['due_date']) < strtotime(date('Y-m-d'))) {
                $status = 'overdue';
            }

            $stmt->execute([
                'invoice_number' => $invNumber,
                'project_id' => !empty($data['project_id']) ? (int)$data['project_id'] : null,
                'company_id' => (int)$data['company_id'],
                'created_by' => Auth::id() ?? 1,
                'issue_date' => $data['issue_date'] ?? date('Y-m-d'),
                'due_date' => $data['due_date'] ?? date('Y-m-d', strtotime('+14 days')),
                'currency' => $data['currency'] ?? 'EGP',
                'subtotal' => $calc['subtotal'],
                'discount_type' => $calc['discount_type'],
                'discount_value' => $calc['discount_value'],
                'discount_amount' => $calc['discount_amount'],
                'tax_rate' => $calc['tax_rate'],
                'tax_amount' => $calc['tax_amount'],
                'total_amount' => $calc['total_amount'],
                'status' => $status,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? 'Payment due within 14 days of invoice date.',
            ]);

            $invoiceId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare("
                INSERT INTO `invoice_items` (`invoice_id`, `item_order`, `description`, `quantity`, `unit_price`, `line_total`)
                VALUES (:invoice_id, :item_order, :description, :quantity, :unit_price, :line_total)
            ");

            foreach ($calc['items'] as $idx => $it) {
                $itemStmt->execute([
                    'invoice_id' => $invoiceId,
                    'item_order' => $idx + 1,
                    'description' => $it['description'],
                    'quantity' => $it['quantity'],
                    'unit_price' => $it['unit_price'],
                    'line_total' => $it['line_total'],
                ]);
            }

            return $invoiceId;
        });
    }

    public function voidInvoice(int $id, string $reason): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE `invoices`
            SET `status` = 'void', `notes` = CONCAT(COALESCE(notes, ''), '\n[VOIDED]: ', :reason), `updated_at` = NOW()
            WHERE `id` = :id AND `paid_amount` = 0.00
        ");
        return $stmt->execute(['reason' => $reason, 'id' => $id]);
    }
}
