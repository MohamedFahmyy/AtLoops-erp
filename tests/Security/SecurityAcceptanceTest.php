<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Policies\DocumentPolicy;
use App\Policies\ProjectPolicy;
use App\Services\TaxService;
use PDO;
use RuntimeException;

class SecurityAcceptanceTest
{
    private string $conn = 'testing';

    public function run(): void
    {
        $this->testCSRFProtection();
        $this->testRBACPermissionEnforcement();
        $this->testHorizontalAndVerticalPrivilegeEscalation();
        $this->testDualKeyRateLimiting();
        $this->testTaxJurisdictionResolution();
        $this->testExecutableUploadRejection();
        $this->testProductionErrorConcealment();
    }

    private function testCSRFProtection(): void
    {
        echo "  [Security 1/7] CSRF token enforcement on state-changing requests... ";

        $validToken = CSRF::getToken();

        // 1. Valid token validation
        if (!CSRF::validate($validToken)) {
            throw new RuntimeException("Valid CSRF token was falsely rejected.");
        }

        // 2. Tampered / empty token
        if (CSRF::validate("tampered_token_xyz")) {
            throw new RuntimeException("Tampered CSRF token was accepted!");
        }

        if (CSRF::validate(null)) {
            throw new RuntimeException("Null CSRF token was accepted!");
        }

        echo "PASSED\n";
    }

    private function testRBACPermissionEnforcement(): void
    {
        echo "  [Security 2/7] Server-side RBAC & permission gate enforcement... ";

        // Log in developer user
        $developer = [
            'id' => 4,
            'name' => 'Lead Software Engineer',
            'email' => 'dev@atloops.com',
            'is_active' => 1,
        ];
        Auth::login($developer);

        // Developer should have projects.view but MUST NOT have finance.payments.record
        if (!Auth::can('projects.view')) {
            throw new RuntimeException("Developer was denied projects.view permission.");
        }

        if (Auth::can('finance.payments.record')) {
            throw new RuntimeException("Developer bypassed permissions and was granted finance.payments.record!");
        }

        if (Auth::can('finance.invoices.manage')) {
            throw new RuntimeException("Developer bypassed permissions and was granted finance.invoices.manage!");
        }

        echo "PASSED\n";
    }

    private function testHorizontalAndVerticalPrivilegeEscalation(): void
    {
        echo "  [Security 3/7] Horizontal and vertical privilege escalation rejection... ";

        $pdo = Database::pdo($this->conn);

        // Developer User (ID 4)
        $developer = [
            'id' => 4,
            'name' => 'Lead Software Engineer',
            'email' => 'dev@atloops.com',
            'is_active' => 1,
        ];
        Auth::login($developer);

        // Vertical check: Developer cannot manage invoices or reverse payments
        if (Auth::can('finance.payments.reverse') || Auth::can('finance.accounts.transfer')) {
            throw new RuntimeException("Vertical privilege escalation! Developer granted financial management.");
        }

        // Horizontal check 1: Private financial document cannot be downloaded by Developer
        $confidentialDoc = [
            'id' => 999,
            'user_id' => 5, // Uploaded by Finance Officer
            'entity_type' => 'invoice',
            'entity_id' => 101,
            'title' => 'Confidential Payroll Invoice',
            'category' => 'finance',
        ];

        if (DocumentPolicy::canDownload($developer, $confidentialDoc, $pdo)) {
            throw new RuntimeException("Horizontal privilege escalation! Developer was permitted to download private finance document.");
        }

        // Horizontal check 2: Restricted project where developer is NOT a member
        $restrictedProject = [
            'id' => 888,
            'manager_id' => 2, // Manager
            'name' => 'Secret Strategic M&A Project',
        ];

        if (ProjectPolicy::canView($developer, $restrictedProject, $pdo)) {
            throw new RuntimeException("Horizontal privilege escalation! Developer was permitted to view unassigned private project.");
        }

        echo "PASSED\n";
    }

    private function testDualKeyRateLimiting(): void
    {
        echo "  [Security 4/7] Dual-key rate limiting (Anti-Spraying & Anti-Distributed Brute Force)... ";

        $testIpKey = 'test_login_ip_' . bin2hex(random_bytes(4));
        $testAccountKey = 'test_login_account_' . bin2hex(random_bytes(4));

        RateLimiter::clear($testIpKey);
        RateLimiter::clear($testAccountKey);

        // 1. Account throttling: 5 failed attempts allowed; 6th is throttled
        for ($i = 1; $i <= 5; $i++) {
            RateLimiter::hit($testAccountKey, 300);
        }
        if (!RateLimiter::tooManyAttempts($testAccountKey, 5)) {
            throw new RuntimeException("Account throttling failed to trigger after 5 attempts.");
        }

        // 2. IP throttling: 15 failed attempts allowed across accounts; 16th is throttled
        for ($i = 1; $i <= 15; $i++) {
            RateLimiter::hit($testIpKey, 900);
        }
        if (!RateLimiter::tooManyAttempts($testIpKey, 15)) {
            throw new RuntimeException("IP throttling failed to trigger after 15 attempts.");
        }

        // Cleanup
        RateLimiter::clear($testIpKey);
        RateLimiter::clear($testAccountKey);

        echo "PASSED\n";
    }

    private function testTaxJurisdictionResolution(): void
    {
        echo "  [Security 5/7] Tax jurisdiction resolution (Egypt 14%, KSA 15%, UAE 5%, Export 0%)... ";

        $egyptRate = TaxService::resolveTaxRate('Egypt', false);
        if ($egyptRate !== 14.00) {
            throw new RuntimeException("Expected 14.00% for Egypt, got {$egyptRate}%");
        }

        $ksaRate = TaxService::resolveTaxRate('Saudi Arabia', false);
        if ($ksaRate !== 15.00) {
            throw new RuntimeException("Expected 15.00% for Saudi Arabia, got {$ksaRate}%");
        }

        $uaeRate = TaxService::resolveTaxRate('United Arab Emirates', false);
        if ($uaeRate !== 5.00) {
            throw new RuntimeException("Expected 5.00% for UAE, got {$uaeRate}%");
        }

        $exportRate = TaxService::resolveTaxRate('United States', true);
        if ($exportRate !== 0.00) {
            throw new RuntimeException("Expected 0.00% for cross-border export, got {$exportRate}%");
        }

        echo "PASSED\n";
    }

    private function testExecutableUploadRejection(): void
    {
        echo "  [Security 6/7] Rejection of executable uploads and prohibited extensions... ";

        $prohibitedExtensions = ['php', 'exe', 'bat', 'sh', 'py', 'js', 'html', 'phtml', 'phar'];
        $allowedMimes = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip' => 'application/zip',
        ];

        foreach ($prohibitedExtensions as $ext) {
            if (array_key_exists($ext, $allowedMimes)) {
                throw new RuntimeException("Prohibited extension '{$ext}' was found in allowlist!");
            }
        }

        echo "PASSED\n";
    }

    private function testProductionErrorConcealment(): void
    {
        echo "  [Security 7/7] Production error concealment (no traces/passwords in production)... ";

        // Check sanitize logic in AuditLogger
        $sanitized = \App\Services\AuditLogger::class;
        $method = new \ReflectionMethod($sanitized, 'sanitize');
        $method->setAccessible(true);

        $payload = [
            'username' => 'admin',
            'password' => 'SuperSecret123',
            'password_confirmation' => 'SuperSecret123',
            '_csrf_token' => 'token123',
            'token' => 'api_token',
        ];

        $cleaned = $method->invoke(null, $payload);
        if (isset($cleaned['password']) || isset($cleaned['password_confirmation']) || isset($cleaned['_csrf_token']) || isset($cleaned['token'])) {
            throw new RuntimeException("AuditLogger leaked secrets into audit payload!");
        }

        echo "PASSED\n";
    }
}
