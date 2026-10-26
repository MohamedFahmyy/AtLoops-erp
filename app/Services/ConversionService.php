<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use PDO;
use RuntimeException;

class ConversionService
{
    /**
     * Convert a Lead into an Opportunity.
     */
    public static function convertLeadToOpportunity(int $leadId, array $oppData = [], ?string $connection = null): array
    {
        return Database::transaction(function (PDO $pdo) use ($leadId, $oppData) {
            $stmt = $pdo->prepare("SELECT * FROM `leads` WHERE `id` = :id FOR UPDATE");
            $stmt->execute(['id' => $leadId]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lead) {
                throw new RuntimeException("Lead #{$leadId} not found.");
            }

            if ($lead['status'] === 'converted') {
                throw new RuntimeException("This lead has already been converted to an opportunity.");
            }

            // Check if company exists or create from lead title/notes
            $companyId = (int)($lead['company_id'] ?? 0);
            if (!$companyId) {
                $compStmt = $pdo->prepare("
                    INSERT INTO `companies` (`name`, `status`)
                    VALUES (:name, 'prospect')
                ");
                $compStmt->execute(['name' => $lead['title'] . ' (Company)']);
                $companyId = (int)$pdo->lastInsertId();

                $pdo->prepare("UPDATE `leads` SET `company_id` = :cid WHERE `id` = :id")
                    ->execute(['cid' => $companyId, 'id' => $leadId]);
            }

            $oppStmt = $pdo->prepare("
                INSERT INTO `opportunities` (
                    `lead_id`, `company_id`, `contact_id`, `assigned_to`,
                    `title`, `stage`, `probability`, `estimated_value`, `currency`, `expected_close_date`
                ) VALUES (
                    :lead_id, :company_id, :contact_id, :assigned_to,
                    :title, :stage, :probability, :estimated_value, :currency, :expected_close_date
                )
            ");

            $title = $oppData['title'] ?? $lead['title'];
            $stage = $oppData['stage'] ?? 'qualified';
            $prob = (int)($oppData['probability'] ?? 30);
            $val = (float)($oppData['estimated_value'] ?? $lead['estimated_value']);
            $curr = $oppData['currency'] ?? $lead['currency'];
            $closeDate = $oppData['expected_close_date'] ?? date('Y-m-d', strtotime('+30 days'));

            $oppStmt->execute([
                'lead_id' => $leadId,
                'company_id' => $companyId,
                'contact_id' => $lead['contact_id'],
                'assigned_to' => $lead['assigned_to'] ?? Auth::id(),
                'title' => $title,
                'stage' => $stage,
                'probability' => $prob,
                'estimated_value' => $val,
                'currency' => $curr,
                'expected_close_date' => $closeDate,
            ]);

            $oppId = (int)$pdo->lastInsertId();

            // Mark lead as converted
            $updateLead = $pdo->prepare("UPDATE `leads` SET `status` = 'converted', `converted_at` = NOW() WHERE `id` = :id");
            $updateLead->execute(['id' => $leadId]);

            AuditLogger::log('lead.convert', 'lead', $leadId, ['status' => $lead['status']], [
                'status' => 'converted',
                'opportunity_id' => $oppId
            ]);

            return [
                'lead_id' => $leadId,
                'opportunity_id' => $oppId,
                'company_id' => $companyId,
            ];
        }, $connection);
    }

    /**
     * Convert an Opportunity into a Quotation.
     */
    public static function convertOpportunityToQuotation(int $oppId, array $items = [], ?string $connection = null): array
    {
        return Database::transaction(function (PDO $pdo) use ($oppId, $items, $connection) {
            $stmt = $pdo->prepare("SELECT * FROM `opportunities` WHERE `id` = :id FOR UPDATE");
            $stmt->execute(['id' => $oppId]);
            $opp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$opp) {
                throw new RuntimeException("Opportunity #{$oppId} not found.");
            }

            // Check if quotation already exists for this opportunity
            $checkQuote = $pdo->prepare("SELECT id FROM `quotations` WHERE `opportunity_id` = :opp_id LIMIT 1");
            $checkQuote->execute(['opp_id' => $oppId]);
            $existing = $checkQuote->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                throw new RuntimeException("A quotation has already been generated for Opportunity #{$oppId}.");
            }

            // If items are empty, create default line item from opportunity
            if (empty($items)) {
                $items = [
                    [
                        'description' => $opp['title'],
                        'quantity' => 1.00,
                        'unit_price' => (float)$opp['estimated_value'],
                    ]
                ];
            }

            $taxRate = TaxService::resolveForCompany((int)$opp['company_id'], $connection);
            $calc = FinancialCalculationService::calculateDocument($items, 'fixed', 0.00, $taxRate, false);

            $quoteNumber = 'QT-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $quoteStmt = $pdo->prepare("
                INSERT INTO `quotations` (
                    `quotation_number`, `opportunity_id`, `company_id`, `contact_id`, `created_by`,
                    `issue_date`, `expiry_date`, `currency`, `subtotal`, `discount_type`,
                    `discount_value`, `discount_amount`, `tax_rate`, `tax_amount`,
                    `total_amount`, `status`, `notes`
                ) VALUES (
                    :quotation_number, :opportunity_id, :company_id, :contact_id, :created_by,
                    CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), :currency, :subtotal, 'fixed',
                    0.00, 0.00, :tax_rate, :tax_amount,
                    :total_amount, 'draft', :notes
                )
            ");

            $quoteStmt->execute([
                'quotation_number' => $quoteNumber,
                'opportunity_id' => $oppId,
                'company_id' => $opp['company_id'],
                'contact_id' => $opp['contact_id'],
                'created_by' => Auth::id() ?? 1,
                'currency' => $opp['currency'],
                'subtotal' => $calc['subtotal'],
                'tax_rate' => $calc['tax_rate'],
                'tax_amount' => $calc['tax_amount'],
                'total_amount' => $calc['total_amount'],
                'notes' => 'Quotation generated from Opportunity #' . $oppId,
            ]);

            $quoteId = (int)$pdo->lastInsertId();

            // Insert quotation line items
            $itemStmt = $pdo->prepare("
                INSERT INTO `quotation_items` (`quotation_id`, `item_order`, `description`, `quantity`, `unit_price`, `line_total`)
                VALUES (:quote_id, :order, :description, :qty, :price, :total)
            ");

            foreach ($calc['items'] as $idx => $it) {
                $itemStmt->execute([
                    'quote_id' => $quoteId,
                    'order' => $idx + 1,
                    'description' => $it['description'],
                    'qty' => $it['quantity'],
                    'price' => $it['unit_price'],
                    'total' => $it['line_total'],
                ]);
            }

            // Update opportunity stage to 'proposal'
            $pdo->prepare("UPDATE `opportunities` SET `stage` = 'proposal', `updated_at` = NOW() WHERE `id` = :id")
                ->execute(['id' => $oppId]);

            AuditLogger::log('quotation.create_from_opp', 'quotation', $quoteId, null, [
                'opportunity_id' => $oppId,
                'quotation_number' => $quoteNumber,
                'total_amount' => $calc['total_amount'],
            ]);

            return [
                'opportunity_id' => $oppId,
                'quotation_id' => $quoteId,
                'quotation_number' => $quoteNumber,
                'total_amount' => $calc['total_amount'],
            ];
        }, $connection);
    }

    /**
     * Convert an Accepted Quotation into a Signed Contract and Project.
     */
    public static function convertQuotationToContractAndProject(int $quoteId, array $contractData = [], ?string $connection = null): array
    {
        return Database::transaction(function (PDO $pdo) use ($quoteId, $contractData, $connection) {
            $stmt = $pdo->prepare("SELECT * FROM `quotations` WHERE `id` = :id FOR UPDATE");
            $stmt->execute(['id' => $quoteId]);
            $quote = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$quote) {
                throw new RuntimeException("Quotation #{$quoteId} not found.");
            }

            // Check if contract already exists
            $checkContract = $pdo->prepare("SELECT id FROM `contracts` WHERE `quotation_id` = :qid LIMIT 1");
            $checkContract->execute(['qid' => $quoteId]);
            $existingContract = $checkContract->fetch(PDO::FETCH_ASSOC);
            if ($existingContract) {
                throw new RuntimeException("A contract has already been generated for Quotation #{$quoteId}.");
            }

            // 1. Create Project
            $projectCode = 'PRJ-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $projectName = $contractData['project_name'] ?? ('Project for Quote ' . $quote['quotation_number']);
            $projStmt = $pdo->prepare("
                INSERT INTO `projects` (
                    `company_id`, `manager_id`, `code`, `name`, `description`,
                    `status`, `start_date`, `target_end_date`, `budget_amount`, `currency`, `estimated_hours`
                ) VALUES (
                    :company_id, :manager_id, :code, :name, :description,
                    'planning', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), :budget, :currency, 100.00
                )
            ");
            $projStmt->execute([
                'company_id' => $quote['company_id'],
                'manager_id' => Auth::id() ?? 1,
                'code' => $projectCode,
                'name' => $projectName,
                'description' => 'Spawned from Quotation ' . $quote['quotation_number'],
                'budget' => $quote['total_amount'],
                'currency' => $quote['currency'],
            ]);
            $projectId = (int)$pdo->lastInsertId();

            // 2. Create Contract linked to Quotation and Project
            $contractNumber = 'CTR-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $contractStmt = $pdo->prepare("
                INSERT INTO `contracts` (
                    `contract_number`, `quotation_id`, `company_id`, `project_id`,
                    `title`, `start_date`, `end_date`, `total_value`, `currency`,
                    `billing_frequency`, `payment_terms`, `scope_of_work`, `status`, `signed_at`
                ) VALUES (
                    :contract_number, :quotation_id, :company_id, :project_id,
                    :title, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), :total_value, :currency,
                    :billing_frequency, :payment_terms, :scope, 'active', NOW()
                )
            ");
            $contractStmt->execute([
                'contract_number' => $contractNumber,
                'quotation_id' => $quoteId,
                'company_id' => $quote['company_id'],
                'project_id' => $projectId,
                'title' => $contractData['title'] ?? ('Contract for ' . $projectName),
                'total_value' => $quote['total_amount'],
                'currency' => $quote['currency'],
                'billing_frequency' => $contractData['billing_frequency'] ?? 'one_time',
                'payment_terms' => $contractData['payment_terms'] ?? '50% upfront, 50% on completion',
                'scope' => 'Deliverables specified in Quotation ' . $quote['quotation_number'],
            ]);
            $contractId = (int)$pdo->lastInsertId();

            // 3. Update Quotation Status to 'accepted'
            $pdo->prepare("UPDATE `quotations` SET `status` = 'accepted', `updated_at` = NOW() WHERE `id` = :id")
                ->execute(['id' => $quoteId]);

            // 4. If opportunity exists, mark opportunity as 'won'
            if (!empty($quote['opportunity_id'])) {
                $pdo->prepare("UPDATE `opportunities` SET `stage` = 'won', `probability` = 100, `updated_at` = NOW() WHERE `id` = :id")
                    ->execute(['id' => $quote['opportunity_id']]);
            }

            // 5. Update Company status to 'active_client'
            $pdo->prepare("UPDATE `companies` SET `status` = 'active_client', `updated_at` = NOW() WHERE `id` = :id")
                ->execute(['id' => $quote['company_id']]);

            AuditLogger::log('quotation.convert_to_contract', 'contract', $contractId, null, [
                'quotation_id' => $quoteId,
                'contract_number' => $contractNumber,
                'project_id' => $projectId,
                'total_value' => $quote['total_amount'],
            ]);

            return [
                'quotation_id' => $quoteId,
                'contract_id' => $contractId,
                'contract_number' => $contractNumber,
                'project_id' => $projectId,
                'project_code' => $projectCode,
            ];
        }, $connection);
    }
}
