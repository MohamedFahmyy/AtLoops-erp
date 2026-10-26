<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$app = new App\Core\Application(dirname(__DIR__));

echo "========================================================\n";
echo "   AT LOOPS OS — AUTOMATED ACCEPTANCE TEST SUITE\n";
echo "   Testing against isolated database: atloops_os_test\n";
echo "========================================================\n\n";

$suites = [
    'Financial Acceptance Tests (Math & Calculations)' => new Tests\Financial\FinancialCalculationTest(),
    'Financial Acceptance Tests (Payment Allocations & Reversals)' => new Tests\Financial\PaymentAllocationTest(),
    'Financial Acceptance Tests (Cash Accounts & Atomic Transfers)' => new Tests\Financial\CashTransferTest(),
    'Security Acceptance Tests (CSRF, RBAC, Uploads, Secrets)' => new Tests\Security\SecurityAcceptanceTest(),
    'Operational Acceptance Tests (Idempotency & Conversions)' => new Tests\Operational\OperationalAcceptanceTest(),
    'Final Go-Live Readiness Gate (All 8 Dimensions)' => new Tests\GoLive\GoLiveVerificationTest(),
];

$passedCount = 0;
$failedCount = 0;
$errors = [];

foreach ($suites as $suiteName => $testCase) {
    echo ">> Running {$suiteName}:\n";
    try {
        $testCase->run();
        $passedCount++;
        echo "   [SUITE PASSED]\n\n";
    } catch (\Throwable $e) {
        $failedCount++;
        $errors[$suiteName] = $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
        echo "   [SUITE FAILED]: " . $e->getMessage() . "\n\n";
    }
}

echo "========================================================\n";
echo "TEST RESULTS SUMMARY:\n";
echo "Total Suites:  " . count($suites) . "\n";
echo "Suites Passed: {$passedCount}\n";
echo "Suites Failed: {$failedCount}\n";
echo "========================================================\n";

if ($failedCount > 0) {
    echo "Failures detail:\n";
    foreach ($errors as $suite => $msg) {
        echo " - [{$suite}]: {$msg}\n";
    }
    exit(1);
}

echo "ALL MANDATORY ACCEPTANCE TESTS PASSED SUCCESSFULLY!\n";
exit(0);
