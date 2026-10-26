<?php

declare(strict_types=1);

namespace Tests\Financial;

use App\Services\FinancialCalculationService;
use RuntimeException;

class FinancialCalculationTest
{
    public function run(): void
    {
        $this->testMultiLineInvoiceCalculation();
        $this->testTaxBaseDiscountReconciliationRegression();
        $this->testFixedAndPercentageDiscount();
        $this->testOutstandingAndStatusCalculation();
    }

    private function testMultiLineInvoiceCalculation(): void
    {
        echo "  [Test] Multi-line invoice with discount, tax, and rounding... ";

        $items = [
            ['description' => 'Web App Backend', 'quantity' => 1.5, 'unit_price' => 10000.00], // 15000.00
            ['description' => 'Cloud Setup & Devops', 'quantity' => 2, 'unit_price' => 2500.00],  // 5000.00
        ];
        // Subtotal = 20000.00
        // Discount 10% = 2000.00
        // After discount = 18000.00
        // VAT 14% = 2520.00
        // Grand Total = 20520.00

        $calc = FinancialCalculationService::calculateDocument($items, 'percentage', 10.00, 14.00, false);

        if ($calc['subtotal'] !== 20000.00) {
            throw new RuntimeException("Expected subtotal 20000.00, got {$calc['subtotal']}");
        }
        if ($calc['discount_amount'] !== 2000.00) {
            throw new RuntimeException("Expected discount 2000.00, got {$calc['discount_amount']}");
        }
        if ($calc['tax_amount'] !== 2520.00) {
            throw new RuntimeException("Expected tax 2520.00, got {$calc['tax_amount']}");
        }
        if ($calc['total_amount'] !== 20520.00) {
            throw new RuntimeException("Expected total 20520.00, got {$calc['total_amount']}");
        }

        echo "PASSED\n";
    }

    /**
     * Authoritative Regression Test:
     * Subtotal = 7,000.00 USD, Discount = 200.00 USD, Tax Rate = 14.00%
     * Accounting & Tax Standard: Commercial discount reduces taxable base prior to VAT calculation.
     * Taxable Base = 7,000.00 - 200.00 = 6,800.00 USD
     * Tax Amount = 6,800.00 * 0.14 = 952.00 USD (NEVER 980.00 USD!)
     * Total Amount = 6,800.00 + 952.00 = 7,752.00 USD (NEVER 7,780.00 USD!)
     */
    private function testTaxBaseDiscountReconciliationRegression(): void
    {
        echo "  [Test] Reconciled Tax Base & Discount Order (7000 - 200 + 14% = 7752 USD)... ";

        $items = [
            ['description' => 'Custom Software Development (50h)', 'quantity' => 50, 'unit_price' => 100.00], // 5000.00
            ['description' => 'Architecture & Cloud Setup', 'quantity' => 1, 'unit_price' => 2000.00],        // 2000.00
        ];

        $calc = FinancialCalculationService::calculateDocument($items, 'fixed', 200.00, 14.00, false);

        if ($calc['subtotal'] !== 7000.00) {
            throw new RuntimeException("Expected subtotal 7000.00, got {$calc['subtotal']}");
        }
        if ($calc['discount_amount'] !== 200.00) {
            throw new RuntimeException("Expected discount 200.00, got {$calc['discount_amount']}");
        }
        if ($calc['taxable_amount'] !== 6800.00) {
            throw new RuntimeException("Expected taxable base 6800.00 (7000 - 200), got {$calc['taxable_amount']}");
        }
        if ($calc['tax_amount'] !== 952.00) {
            throw new RuntimeException("Expected tax 952.00 (6800 * 14%), got {$calc['tax_amount']}");
        }
        if ($calc['total_amount'] !== 7752.00) {
            throw new RuntimeException("Expected total 7752.00, got {$calc['total_amount']}");
        }

        // Anti-Regression Assertion: Guarantee that tax was NOT computed on 7,000 without deducting discount
        if ($calc['tax_amount'] === 980.00 || $calc['total_amount'] === 7780.00) {
            throw new RuntimeException("CRITICAL REGRESSION: Tax was erroneously computed on gross subtotal without deducting discount first!");
        }

        echo "PASSED\n";
    }

    private function testFixedAndPercentageDiscount(): void
    {
        echo "  [Test] Fixed discount capping and boundary conditions... ";

        $items = [
            ['description' => 'Domain registration', 'quantity' => 1, 'unit_price' => 500.00],
        ];

        // Fixed discount 600 on 500 must cap at 500
        $calc = FinancialCalculationService::calculateDocument($items, 'fixed', 600.00, 0.00);
        if ($calc['discount_amount'] !== 500.00 || $calc['total_amount'] !== 0.00) {
            throw new RuntimeException("Discount capping failed: discount={$calc['discount_amount']}, total={$calc['total_amount']}");
        }

        echo "PASSED\n";
    }

    private function testOutstandingAndStatusCalculation(): void
    {
        echo "  [Test] Outstanding balance and status transitions... ";

        $total = 10000.00;
        $paid = 4000.00;
        $outstanding = FinancialCalculationService::calculateOutstanding($total, $paid);
        if ($outstanding !== 6000.00) {
            throw new RuntimeException("Expected outstanding 6000.00, got {$outstanding}");
        }

        $futureDate = date('Y-m-d', strtotime('+7 days'));
        $pastDate = date('Y-m-d', strtotime('-5 days'));

        // Partial payment
        $status1 = FinancialCalculationService::determineInvoiceStatus($total, $paid, $futureDate);
        if ($status1 !== 'partially_paid') {
            throw new RuntimeException("Expected status partially_paid, got {$status1}");
        }

        // Full payment
        $status2 = FinancialCalculationService::determineInvoiceStatus($total, 10000.00, $pastDate);
        if ($status2 !== 'paid') {
            throw new RuntimeException("Expected status paid, got {$status2}");
        }

        // Overdue unpaid
        $status3 = FinancialCalculationService::determineInvoiceStatus($total, 0.00, $pastDate);
        if ($status3 !== 'overdue') {
            throw new RuntimeException("Expected status overdue, got {$status3}");
        }

        echo "PASSED\n";
    }
}
