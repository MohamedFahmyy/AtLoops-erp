<?php

declare(strict_types=1);

namespace Tests\Operational;

use App\Core\Database;
use App\Core\I18n;
use App\Services\ConversionService;
use App\Services\RecurringBillingService;
use PDO;
use RuntimeException;

class OperationalAcceptanceTest
{
    private string $conn = 'testing';

    public function run(): void
    {
        $this->testConversionPipelineIdempotency();
        $this->testRecurringDraftGenerationIdempotency();
        $this->testLocalizationIntegrity();
    }

    private function testConversionPipelineIdempotency(): void
    {
        echo "  [Operational 1/3] Conversion pipeline idempotency (no duplicate conversions)... ";

        $pdo = Database::pdo($this->conn);

        // 1. Create a fresh Lead
        $pdo->exec("
            INSERT INTO `leads` (`title`, `estimated_value`, `currency`, `status`)
            VALUES ('TEST-LEAD-CONVERT', 50000.00, 'EGP', 'new')
        ");
        $leadId = (int)$pdo->lastInsertId();

        // 2. Convert Lead to Opportunity
        $oppRes = ConversionService::convertLeadToOpportunity($leadId, [], $this->conn);
        $oppId = (int)$oppRes['opportunity_id'];

        // 3. Retry converting same Lead -> Must be rejected!
        $retryLeadBlocked = false;
        try {
            ConversionService::convertLeadToOpportunity($leadId, [], $this->conn);
        } catch (\Throwable $e) {
            $retryLeadBlocked = true;
        }
        if (!$retryLeadBlocked) {
            throw new RuntimeException("Duplicate lead conversion was not blocked!");
        }

        // 4. Convert Opportunity to Quotation
        $quoteRes = ConversionService::convertOpportunityToQuotation($oppId, [], $this->conn);
        $quoteId = (int)$quoteRes['quotation_id'];

        // 5. Retry converting same Opportunity -> Must be rejected!
        $retryOppBlocked = false;
        try {
            ConversionService::convertOpportunityToQuotation($oppId, [], $this->conn);
        } catch (\Throwable $e) {
            $retryOppBlocked = true;
        }
        if (!$retryOppBlocked) {
            throw new RuntimeException("Duplicate opportunity conversion to quotation was not blocked!");
        }

        // 6. Convert Quotation to Contract and Project
        $contractRes = ConversionService::convertQuotationToContractAndProject($quoteId, [], $this->conn);
        $contractId = (int)$contractRes['contract_id'];

        // 7. Retry converting same Quotation -> Must be rejected!
        $retryQuoteBlocked = false;
        try {
            ConversionService::convertQuotationToContractAndProject($quoteId, [], $this->conn);
        } catch (\Throwable $e) {
            $retryQuoteBlocked = true;
        }
        if (!$retryQuoteBlocked) {
            throw new RuntimeException("Duplicate quotation conversion to contract was not blocked!");
        }

        echo "PASSED\n";
    }

    private function testRecurringDraftGenerationIdempotency(): void
    {
        echo "  [Operational 2/3] Recurring invoice draft generation idempotency... ";

        $pdo = Database::pdo($this->conn);

        // Clean up any existing test recurring contracts and their draft invoices to guarantee deterministic test isolation
        $oldRcs = $pdo->query("SELECT id FROM `recurring_contracts` WHERE `title` = 'TEST-RECURRING-SUPPORT'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($oldRcs as $oldRcId) {
            $pdo->exec("DELETE FROM `invoice_items` WHERE `invoice_id` IN (SELECT id FROM `invoices` WHERE `notes` LIKE '%Recurring Contract #{$oldRcId}%')");
            $pdo->exec("DELETE FROM `invoices` WHERE `notes` LIKE '%Recurring Contract #{$oldRcId}%'");
            $pdo->exec("DELETE FROM `recurring_contracts` WHERE `id` = {$oldRcId}");
        }

        // Insert recurring contract due today
        $pdo->exec("
            INSERT INTO `recurring_contracts` (
                `company_id`, `service_type`, `title`, `monthly_fee`, `currency`,
                `billing_frequency`, `start_date`, `next_billing_date`, `auto_renew`, `status`
            ) VALUES (
                1, 'maintenance', 'TEST-RECURRING-SUPPORT', 2500.00, 'EGP',
                'monthly', CURDATE(), CURDATE(), 1, 'active'
            )
        ");
        $rcId = (int)$pdo->lastInsertId();

        // 1. First run generates 1 draft invoice
        $run1 = RecurringBillingService::processRecurringContracts(date('Y-m-d'), $this->conn);
        $count1 = $run1['total_processed'] ?? 0;

        if ($count1 === 0) {
            throw new RuntimeException("Recurring billing failed to process due contract!");
        }

        // 2. Re-running on same date must NOT generate duplicate draft invoices for same contract period
        $run2 = RecurringBillingService::processRecurringContracts(date('Y-m-d'), $this->conn);
        $count2 = $run2['total_processed'] ?? 0;

        if ($count2 !== 0) {
            throw new RuntimeException("Re-running recurring billing created duplicate drafts!");
        }

        echo "PASSED\n";
    }

    private function testLocalizationIntegrity(): void
    {
        echo "  [Operational 3/3] Arabic (RTL, Cairo) and English (LTR, Inter) dictionary integrity... ";

        // Test Arabic lookup
        I18n::setLocale('ar');
        $arTitle = __('common.dashboard');
        if ($arTitle !== 'لوحة القيادة التنفيذية') {
            throw new RuntimeException("Arabic translation mismatch: got '{$arTitle}'");
        }
        if (!I18n::isRtl()) {
            throw new RuntimeException("Arabic locale must report is_rtl() = true");
        }

        // Test English lookup
        I18n::setLocale('en');
        $enTitle = __('common.dashboard');
        if ($enTitle !== 'Executive Dashboard') {
            throw new RuntimeException("English translation mismatch: got '{$enTitle}'");
        }
        if (I18n::isRtl()) {
            throw new RuntimeException("English locale must report is_rtl() = false");
        }

        // Restore default Arabic
        I18n::setLocale('ar');

        echo "PASSED\n";
    }
}
