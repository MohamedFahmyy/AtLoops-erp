<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class RecurringBillingService
{
    /**
     * Process active recurring contracts and generate draft invoices idempotently.
     *
     * @param string|null $targetDate Defaults to current date
     * @param string|null $connection
     * @return array
     */
    public static function processRecurringContracts(?string $targetDate = null, ?string $connection = null): array
    {
        $date = $targetDate ?: date('Y-m-d');
        $pdo = Database::pdo($connection);

        $stmt = $pdo->prepare("
            SELECT * FROM `recurring_contracts`
            WHERE `status` = 'active'
              AND `next_billing_date` <= :target_date
            ORDER BY `id` ASC
        ");
        $stmt->execute(['target_date' => $date]);
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [
            'total_processed' => 0,
            'invoices_created' => [],
            'skipped_replays' => [],
        ];

        foreach ($contracts as $contract) {
            $contractId = (int)$contract['id'];
            $billingDate = $contract['next_billing_date'];
            $companyId = (int)$contract['company_id'];
            $monthlyFee = (float)$contract['monthly_fee'];
            $currency = $contract['currency'];
            $title = $contract['title'];
            $frequency = $contract['billing_frequency'] ?? 'monthly';

            // Calculate multiplier for billing frequency
            $multiplier = match ($frequency) {
                'quarterly' => 3,
                'annually' => 12,
                default => 1,
            };

            $subtotal = round($monthlyFee * $multiplier, 2);

            // Compute authoritative tax & total using jurisdiction-aware tax rate
            $taxRate = TaxService::resolveForCompany($companyId, $connection);
            $calc = FinancialCalculationService::calculateDocument([
                [
                    'description' => "{$title} ({$frequency} retainer - period {$billingDate})",
                    'quantity' => 1.00,
                    'unit_price' => $subtotal,
                ]
            ], 'fixed', 0.00, $taxRate, false);

            $idempotencyNote = "Recurring Contract #{$contractId} Billing Period {$billingDate}";

            // Check if draft invoice already exists for this contract and billing period
            $checkStmt = $pdo->prepare("
                SELECT id, invoice_number FROM `invoices`
                WHERE `company_id` = :company_id
                  AND `notes` LIKE :note
                LIMIT 1
            ");
            $checkStmt->execute([
                'company_id' => $companyId,
                'note' => "%{$idempotencyNote}%",
            ]);
            $existingInv = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingInv) {
                $results['skipped_replays'][] = [
                    'contract_id' => $contractId,
                    'invoice_id' => $existingInv['id'],
                    'invoice_number' => $existingInv['invoice_number'],
                    'reason' => 'Invoice already created for this period.',
                ];
                continue;
            }

            // Create draft invoice inside transaction
            Database::transaction(function (PDO $pdo) use ($contract, $contractId, $billingDate, $companyId, $calc, $idempotencyNote, $currency, $frequency, &$results) {
                $invNumber = 'INV-REC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
                $dueDate = date('Y-m-d', strtotime($billingDate . ' +14 days'));

                $invStmt = $pdo->prepare("
                    INSERT INTO `invoices` (
                        `invoice_number`, `contract_id`, `company_id`, `created_by`,
                        `issue_date`, `due_date`, `currency`, `subtotal`,
                        `discount_amount`, `tax_rate`, `tax_amount`, `total_amount`,
                        `paid_amount`, `status`, `notes`
                    ) VALUES (
                        :inv_num, :contract_id, :company_id, 1,
                        :issue_date, :due_date, :currency, :subtotal,
                        0.00, :tax_rate, :tax_amount, :total_amount,
                        0.00, 'draft', :notes
                    )
                ");

                $invStmt->execute([
                    'inv_num' => $invNumber,
                    'contract_id' => $contract['contract_id'],
                    'company_id' => $companyId,
                    'issue_date' => $billingDate,
                    'due_date' => $dueDate,
                    'currency' => $currency,
                    'subtotal' => $calc['subtotal'],
                    'tax_rate' => $calc['tax_rate'],
                    'tax_amount' => $calc['tax_amount'],
                    'total_amount' => $calc['total_amount'],
                    'notes' => $idempotencyNote,
                ]);

                $invId = (int)$pdo->lastInsertId();

                // Insert Item
                $itemStmt = $pdo->prepare("
                    INSERT INTO `invoice_items` (`invoice_id`, `item_order`, `description`, `quantity`, `unit_price`, `line_total`)
                    VALUES (:inv_id, 1, :desc, 1.00, :price, :total)
                ");
                $itemStmt->execute([
                    'inv_id' => $invId,
                    'desc' => $calc['items'][0]['description'],
                    'price' => $calc['items'][0]['unit_price'],
                    'total' => $calc['items'][0]['line_total'],
                ]);

                // Advance next billing date
                $interval = match ($frequency) {
                    'quarterly' => '+3 months',
                    'annually' => '+1 year',
                    default => '+1 month',
                };
                $nextDate = date('Y-m-d', strtotime($billingDate . ' ' . $interval));

                $updContract = $pdo->prepare("
                    UPDATE `recurring_contracts`
                    SET `next_billing_date` = :next_date, `updated_at` = NOW()
                    WHERE `id` = :id
                ");
                $updContract->execute([
                    'next_date' => $nextDate,
                    'id' => $contractId,
                ]);

                AuditLogger::log('recurring.invoice_draft', 'invoice', $invId, null, [
                    'contract_id' => $contractId,
                    'invoice_number' => $invNumber,
                    'total_amount' => $calc['total_amount'],
                    'next_billing_date' => $nextDate,
                ]);

                $results['total_processed']++;
                $results['invoices_created'][] = [
                    'contract_id' => $contractId,
                    'invoice_id' => $invId,
                    'invoice_number' => $invNumber,
                    'total_amount' => $calc['total_amount'],
                    'currency' => $currency,
                    'next_billing_date' => $nextDate,
                ];
            }, $connection);
        }

        return $results;
    }
}
