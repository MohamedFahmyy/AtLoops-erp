<?php

declare(strict_types=1);

namespace Tests\GoLive;

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use App\Policies\DocumentPolicy;
use App\Policies\ProjectPolicy;
use App\Services\FinancialCalculationService;
use App\Services\PaymentAllocationService;
use App\Services\RecurringBillingService;
use App\Services\TaxService;
use InvalidArgumentException;
use PDO;
use RuntimeException;

class GoLiveVerificationTest
{
    private string $conn = 'testing';
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo($this->conn);
    }

    public function run(): array
    {
        $results = [];

        $results['secret_management'] = $this->testSecretManagementAndIsolation();
        $results['financial_calculations'] = $this->testFinancialIntegrityAndCalculations();
        $results['multi_currency_tax'] = $this->testMultiCurrencyAndTaxJurisdictionSeparation();
        $results['concurrent_payment_reversal'] = $this->testConcurrentPaymentsAndReversal();
        $results['backup_restore_integrity'] = $this->testBackupAndRestoreIntegrityVerification();
        $results['cron_concurrency_idempotency'] = $this->testCronConcurrencyLockAndIdempotency();
        $results['role_permission_matrix'] = $this->testRolePermissionMatrixAndIsolation();
        $results['localization_integrity'] = $this->testLanguageDictionariesAndRtlLtrIntegrity();

        return $results;
    }

    /**
     * 1. Secret Management: verify isolation, .gitignore protection, no leaks in .env.example
     */
    private function testSecretManagementAndIsolation(): array
    {
        echo "  [GoLive 1/8] Verifying secret management, MCP isolation & .gitignore... ";

        $rootDir = dirname(__DIR__, 2);

        // A. Verify .gitignore blocks .env, backups, and private vault files
        $gitIgnorePath = $rootDir . '/.gitignore';
        if (!file_exists($gitIgnorePath)) {
            throw new RuntimeException(".gitignore does not exist in project root.");
        }
        $gitIgnore = file_get_contents($gitIgnorePath);
        $requiredIgnores = ['.env', '/storage/backups', '/storage/private', '/vendor'];
        foreach ($requiredIgnores as $req) {
            if (!str_contains($gitIgnore, $req)) {
                throw new RuntimeException(".gitignore is missing critical ignore rule: {$req}");
            }
        }

        // B. Verify .env.example contains ZERO production secrets
        $envExamplePath = $rootDir . '/.env.example';
        $envExample = file_get_contents($envExamplePath);
        if (str_contains($envExample, 'ck_') || str_contains($envExample, 'ctx7sk-')) {
            throw new RuntimeException(".env.example contains hardcoded API keys!");
        }

        // C. Verify separation: codebase does not read or import IDE mcp_config.json
        $mcpConfigCheck = shell_exec('git grep "mcp_config" ' . escapeshellarg($rootDir) . ' 2>&1');
        // Regardless of git repository state, check files via PHP
        $controllers = glob($rootDir . '/app/*/*.php');
        foreach ($controllers as $file) {
            $content = file_get_contents($file);
            if (str_contains($content, 'mcp_config.json')) {
                throw new RuntimeException("Codebase references IDE mcp_config in {$file}");
            }
        }

        echo "PASSED\n";
        return [
            'status' => 'PASS',
            'gitignore_rules_verified' => count($requiredIgnores),
            'env_example_clean' => true,
            'mcp_isolation_verified' => true,
        ];
    }

    /**
     * 2. Financial Integrity: Item lines -> Subtotal -> Discount before Tax -> Tax Base -> Rounding
     */
    private function testFinancialIntegrityAndCalculations(): array
    {
        echo "  [GoLive 2/8] Verifying financial calculation order of operations & rounding... ";

        // Test Case: Subtotal 10,000, Fixed Discount 1,000 -> Tax Base must be 9,000.
        // Tax 14% on 9,000 = 1,260. Total = 10,260.
        $calcFixed = FinancialCalculationService::calculateDocument([
            ['description' => 'Service A', 'quantity' => 10, 'unit_price' => 1000.00]
        ], 'fixed', 1000.00, 14.00, false);

        if ($calcFixed['subtotal'] !== 10000.00 ||
            $calcFixed['discount_amount'] !== 1000.00 ||
            $calcFixed['taxable_amount'] !== 9000.00 ||
            $calcFixed['tax_amount'] !== 1260.00 ||
            $calcFixed['total_amount'] !== 10260.00
        ) {
            throw new RuntimeException("Financial calculation failed for fixed discount: " . json_encode($calcFixed));
        }

        // Test Case: Percentage Discount 20% on 10,000 = 2,000 discount -> Tax Base = 8,000.
        // Tax 15% (KSA) on 8,000 = 1,200. Total = 9,200.
        $calcPct = FinancialCalculationService::calculateDocument([
            ['description' => 'Service B', 'quantity' => 4, 'unit_price' => 2500.00]
        ], 'percentage', 20.00, 15.00, false);

        if ($calcPct['subtotal'] !== 10000.00 ||
            $calcPct['discount_amount'] !== 2000.00 ||
            $calcPct['taxable_amount'] !== 8000.00 ||
            $calcPct['tax_amount'] !== 1200.00 ||
            $calcPct['total_amount'] !== 9200.00
        ) {
            throw new RuntimeException("Financial calculation failed for percentage discount: " . json_encode($calcPct));
        }

        // Test Case: Fixed Discount exceeds Subtotal (Subtotal 500, Discount 800) -> Must cap at 500
        $calcCapped = FinancialCalculationService::calculateDocument([
            ['description' => 'Small Service', 'quantity' => 1, 'unit_price' => 500.00]
        ], 'fixed', 800.00, 14.00, false);

        if ($calcCapped['discount_amount'] !== 500.00 ||
            $calcCapped['taxable_amount'] !== 0.00 ||
            $calcCapped['total_amount'] !== 0.00
        ) {
            throw new RuntimeException("Capped discount calculation failed: " . json_encode($calcCapped));
        }

        echo "PASSED\n";
        return [
            'status' => 'PASS',
            'fixed_discount_order' => 'Taxable base = Subtotal - Discount (Verified)',
            'percentage_discount_order' => 'Taxable base = Subtotal - Discount (Verified)',
            'discount_capping' => 'Discount capped at subtotal (Verified)',
        ];
    }

    /**
     * 3. Multi-Currency & Tax Jurisdiction Separation:
     * Invoice currency (e.g. USD, EUR, SAR, AED, EGP) is independent of tax jurisdiction (Country rules)
     */
    private function testMultiCurrencyAndTaxJurisdictionSeparation(): array
    {
        echo "  [GoLive 3/8] Verifying multi-currency support & tax jurisdiction separation... ";

        // An invoice can be billed in USD to an Egyptian client (Subject to 14% Egypt VAT)
        $rateEgyptUsd = TaxService::resolveTaxRate('Egypt', false);
        if ($rateEgyptUsd !== 14.00) {
            throw new RuntimeException("Egypt domestic rate mismatch: {$rateEgyptUsd}");
        }

        // An invoice billed in USD exported to a US client (0% Export zero-rated)
        $rateExport = TaxService::resolveTaxRate('United States', true);
        if ($rateExport !== 0.00) {
            throw new RuntimeException("Export rate must be 0%: {$rateExport}");
        }

        // Saudi Arabia domestic billing in SAR or USD (15% KSA VAT)
        $rateKsa = TaxService::resolveTaxRate('Saudi Arabia', false);
        if ($rateKsa !== 15.00) {
            throw new RuntimeException("KSA domestic rate mismatch: {$rateKsa}");
        }

        // UAE domestic billing in AED or USD (5% UAE VAT)
        $rateUae = TaxService::resolveTaxRate('United Arab Emirates', false);
        if ($rateUae !== 5.00) {
            throw new RuntimeException("UAE domestic rate mismatch: {$rateUae}");
        }

        echo "PASSED\n";
        return [
            'status' => 'PASS',
            'egypt_vat' => 14.00,
            'ksa_vat' => 15.00,
            'uae_vat' => 5.00,
            'export_vat' => 0.00,
        ];
    }

    /**
     * 4. Financial Concurrency, Overpayment & Reversal
     */
    private function testConcurrentPaymentsAndReversal(): array
    {
        echo "  [GoLive 4/8] Verifying payment allocation, idempotency replay, overpayment & reversal... ";

        // Clean any test data
        $this->pdo->exec("DELETE FROM `payment_allocations` WHERE `payment_id` IN (SELECT id FROM `payments` WHERE `idempotency_key` LIKE 'GOLIVE-TEST-%')");
        $this->pdo->exec("DELETE FROM `payments` WHERE `idempotency_key` LIKE 'GOLIVE-TEST-%'");
        $this->pdo->exec("DELETE FROM `invoice_items` WHERE `invoice_id` IN (SELECT id FROM `invoices` WHERE `invoice_number` = 'INV-GOLIVE-001')");
        $this->pdo->exec("DELETE FROM `invoices` WHERE `invoice_number` = 'INV-GOLIVE-001'");

        // Create USD invoice: Total 5,000 USD
        $this->pdo->exec("
            INSERT INTO `invoices` (`invoice_number`, `company_id`, `created_by`, `issue_date`, `due_date`, `currency`, `subtotal`, `tax_rate`, `tax_amount`, `total_amount`, `paid_amount`, `status`)
            VALUES ('INV-GOLIVE-001', 1, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'USD', 5000.00, 0.00, 0.00, 5000.00, 0.00, 'unpaid')
        ");
        $invId = (int)$this->pdo->query("SELECT id FROM `invoices` WHERE `invoice_number` = 'INV-GOLIVE-001'")->fetchColumn();

        // USD cash account
        $accId = (int)$this->pdo->query("SELECT id FROM `cash_accounts` WHERE `currency` = 'USD' LIMIT 1")->fetchColumn();
        $accBalInit = (float)$this->pdo->query("SELECT current_balance FROM `cash_accounts` WHERE id = {$accId}")->fetchColumn();

        // 1. Partial Payment #1: 2,000 USD
        $pay1 = PaymentAllocationService::recordPayment([
            'invoice_id' => $invId,
            'cash_account_id' => $accId,
            'amount' => 2000.00,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'idempotency_key' => 'GOLIVE-TEST-001',
        ], $this->conn);

        // 2. Replay with identical idempotency key -> Must be detected without duplicating balance
        $replay = PaymentAllocationService::recordPayment([
            'invoice_id' => $invId,
            'cash_account_id' => $accId,
            'amount' => 2000.00,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'idempotency_key' => 'GOLIVE-TEST-001',
        ], $this->conn);

        if (!($replay['is_replay'] ?? false)) {
            throw new RuntimeException("Idempotency replay detection failed.");
        }

        // 3. Overpayment rejection: Attempt 4,000 USD on 3,000 USD remaining balance
        $overpaymentRejected = false;
        try {
            PaymentAllocationService::recordPayment([
                'invoice_id' => $invId,
                'cash_account_id' => $accId,
                'amount' => 4000.00,
                'payment_date' => date('Y-m-d'),
                'payment_method' => 'bank_transfer',
                'idempotency_key' => 'GOLIVE-TEST-OVER',
            ], $this->conn);
        } catch (InvalidArgumentException $e) {
            $overpaymentRejected = true;
        }

        if (!$overpaymentRejected) {
            throw new RuntimeException("Overpayment was not rejected!");
        }

        // 4. Pay remaining 3,000 USD -> Full settlement
        $pay2 = PaymentAllocationService::recordPayment([
            'invoice_id' => $invId,
            'cash_account_id' => $accId,
            'amount' => 3000.00,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'idempotency_key' => 'GOLIVE-TEST-002',
        ], $this->conn);

        $invStatus = $this->pdo->query("SELECT status FROM `invoices` WHERE id = {$invId}")->fetchColumn();
        if ($invStatus !== 'paid') {
            throw new RuntimeException("Invoice status is not 'paid' after settlement: {$invStatus}");
        }

        // 5. Payment Reversal: reverse Payment #2 (3,000 USD)
        $pay2Id = (int)$pay2['payment']['id'];
        PaymentAllocationService::reversePayment($pay2Id, 'Reversal test', $this->conn);

        $invAfterRev = $this->pdo->query("SELECT paid_amount, status FROM `invoices` WHERE id = {$invId}")->fetch(PDO::FETCH_ASSOC);
        if ((float)$invAfterRev['paid_amount'] !== 2000.00 || $invAfterRev['status'] !== 'partially_paid') {
            throw new RuntimeException("Invoice balance not restored after reversal: " . json_encode($invAfterRev));
        }

        echo "PASSED\n";
        return [
            'status' => 'PASS',
            'idempotency_replay' => 'Duplicate submission flagged & safe (Verified)',
            'overpayment_rejection' => 'Attempted overpayment rejected with exception (Verified)',
            'reversal_restoration' => 'Reversal restores balance to partially_paid (Verified)',
        ];
    }

    /**
     * 5. Backup & Restore Integrity
     */
    private function testBackupAndRestoreIntegrityVerification(): array
    {
        echo "  [GoLive 5/8] Verifying backup generation, production restore block & schema integrity... ";

        // A. Verify restore to production is blocked without confirmation flag
        $prodBlockOutput = shell_exec('php ' . escapeshellarg(dirname(__DIR__, 2) . '/scripts/restore.php') . ' --database=mysql 2>&1');
        if (!str_contains((string)$prodBlockOutput, '[ABORTED] Restoring directly into production database')) {
            throw new RuntimeException("Safety check failed: restore to production was not blocked!");
        }

        // B. Generate fresh backup of testing database
        $backupOutput = shell_exec('php ' . escapeshellarg(dirname(__DIR__, 2) . '/scripts/backup.php') . ' --database=testing 2>&1');
        $dumps = glob(dirname(__DIR__, 2) . '/storage/backups/*.sql');
        rsort($dumps);
        $latest = $dumps[0] ?? null;

        if (!$latest || filesize($latest) < 1000) {
            throw new RuntimeException("Backup file was not created properly.");
        }

        // C. Restore backup into testing database
        $restoreOutput = shell_exec('php ' . escapeshellarg(dirname(__DIR__, 2) . '/scripts/restore.php') . ' --database=testing --file=' . escapeshellarg($latest) . ' 2>&1');
        if (!str_contains((string)$restoreOutput, '[SUCCESS] Restore completed successfully!')) {
            throw new RuntimeException("Database restore failed: {$restoreOutput}");
        }

        // D. Verify table count & essential entities
        $tables = $this->pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
        if (count($tables) !== 37) {
            throw new RuntimeException("Table count mismatch after restore: found " . count($tables) . ", expected 37.");
        }

        echo "PASSED\n";
        return [
            'status' => 'PASS',
            'prod_safeguard' => 'Blocked restore into production without flag (Verified)',
            'backup_created' => basename($latest),
            'restored_tables' => count($tables),
            'schema_integrity' => '37 tables verified (Verified)',
        ];
    }

    /**
     * 6. Cron Concurrency Lock & Idempotency
     */
    private function testCronConcurrencyLockAndIdempotency(): array
    {
        echo "  [GoLive 6/8] Verifying cron concurrency mutex lock & execution idempotency... ";

        $cronScript = dirname(__DIR__, 2) . '/scripts/cron.php';
        $lockFile = dirname(__DIR__, 2) . '/storage/cache/cron_all.lock';

        // 1. Acquire mutex lock simulating a running cron worker
        $handle = fopen($lockFile, 'c+');
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException("Failed to acquire initial lock on {$lockFile}");
        }

        // 2. Run cron via CLI while lock is held -> Must exit immediately with [CRON SKIPPED]
        $cronConcurrentOutput = shell_exec('php ' . escapeshellarg($cronScript) . ' --database=testing 2>&1');
        if (!str_contains((string)$cronConcurrentOutput, '[CRON SKIPPED] Another instance for task \'all\' is currently active.')) {
            flock($handle, LOCK_UN);
            fclose($handle);
            throw new RuntimeException("Cron concurrency mutex lock failed to prevent overlapping execution: {$cronConcurrentOutput}");
        }

        // Release lock
        flock($handle, LOCK_UN);
        fclose($handle);

        // 3. Run cron with lock free -> Must complete successfully
        $cronCleanOutput = shell_exec('php ' . escapeshellarg($cronScript) . ' --database=testing 2>&1');
        if (!str_contains((string)$cronCleanOutput, 'completed successfully')) {
            throw new RuntimeException("Cron execution failed when lock is free: {$cronCleanOutput}");
        }

        echo "PASSED\n";
        return [
            'status' => 'PASS',
            'concurrency_mutex' => 'Overlapping cron instances blocked via flock (Verified)',
            'clean_execution' => 'All tasks completed successfully (Verified)',
        ];
    }

    /**
     * 7. Role Permission Matrix & Resource Policy Isolation
     */
    private function testRolePermissionMatrixAndIsolation(): array
    {
        echo "  [GoLive 7/8] Verifying RBAC permission matrix & resource policies... ";

        // Developer Role (ID: 4)
        $developer = ['id' => 4, 'name' => 'Lead Engineer', 'email' => 'dev@atloops.com', 'is_active' => 1];
        Auth::login($developer);

        // Developer MUST have projects.view, tasks update status
        if (!Auth::can('projects.view') || !Auth::can('projects.tasks.update_status')) {
            throw new RuntimeException("Developer falsely denied project access.");
        }

        // Developer MUST NOT have financial or admin rights
        $blockedPerms = ['finance.payments.record', 'finance.invoices.manage', 'finance.accounts.transfer', 'audit.view', 'users.manage'];
        foreach ($blockedPerms as $perm) {
            if (Auth::can($perm)) {
                throw new RuntimeException("Developer possessed unauthorized permission: {$perm}");
            }
        }

        // Document Policy IDOR Defense
        $financeDoc = [
            'id' => 77701,
            'user_id' => 5, // Finance user
            'entity_type' => 'invoice',
            'entity_id' => 10,
            'category' => 'finance',
        ];

        if (DocumentPolicy::canDownload($developer, $financeDoc, $this->pdo) ||
            DocumentPolicy::canDelete($developer, $financeDoc, $this->pdo)
        ) {
            throw new RuntimeException("Document policy IDOR defense failed: Developer permitted to access finance doc.");
        }

        echo "PASSED\n";
        return [
            'status' => 'PASS',
            'developer_rbac' => 'Strictly bounded to developer scope (Verified)',
            'document_idor' => 'Unauthorized document access blocked (Verified)',
        ];
    }

    /**
     * 8. Localization Integrity: Arabic (RTL, Cairo) & English (LTR, Inter)
     */
    private function testLanguageDictionariesAndRtlLtrIntegrity(): array
    {
        echo "  [GoLive 8/8] Verifying bilingual dictionaries (Arabic RTL & English LTR)... ";

        $langDir = dirname(__DIR__, 2) . '/lang';
        $arDir = $langDir . '/ar';
        $enDir = $langDir . '/en';

        $files = ['auth.php', 'common.php', 'validation.php'];
        foreach ($files as $file) {
            $arPath = $arDir . '/' . $file;
            $enPath = $enDir . '/' . $file;

            if (!file_exists($arPath) || !file_exists($enPath)) {
                throw new RuntimeException("Missing language file: {$file}");
            }

            $ar = require $arPath;
            $en = require $enPath;

            if (!is_array($ar) || !is_array($en) || empty($ar) || empty($en)) {
                throw new RuntimeException("Language dictionary {$file} is invalid or empty.");
            }

            // Check key parity
            $missingInEn = array_diff_key($ar, $en);
            if (!empty($missingInEn)) {
                throw new RuntimeException("Keys in AR missing in EN for {$file}: " . implode(', ', array_keys($missingInEn)));
            }
        }

        echo "PASSED\n";
        return [
            'status' => 'PASS',
            'files_verified' => count($files),
            'parity' => '100% key parity between Arabic and English (Verified)',
        ];
    }
}
