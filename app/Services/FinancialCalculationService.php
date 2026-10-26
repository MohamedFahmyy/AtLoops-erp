<?php

declare(strict_types=1);

namespace App\Services;

class FinancialCalculationService
{
    /**
     * Authoritative calculation for an invoice or quotation document.
     *
     * @param array<array{description: string, quantity: float|int|string, unit_price: float|int|string}> $items
     * @param string $discountType 'fixed' or 'percentage'
     * @param float|int|string $discountValue
     * @param float|int|string $taxRate percentage (e.g. 14 for 14%)
     * @param bool $pricesIncludeTax default false
     * @return array{
     *   items: array<array{description: string, quantity: float, unit_price: float, line_total: float}>,
     *   subtotal: float,
     *   discount_type: string,
     *   discount_value: float,
     *   discount_amount: float,
     *   taxable_amount: float,
     *   tax_rate: float,
     *   tax_amount: float,
     *   total_amount: float
     * }
     */
    public static function calculateDocument(
        array $items,
        string $discountType = 'fixed',
        mixed $discountValue = 0.00,
        mixed $taxRate = 0.00,
        bool $pricesIncludeTax = false
    ): array {
        $subtotal = 0.00;
        $calculatedItems = [];

        foreach ($items as $item) {
            $qty = max(0.00, round((float)($item['quantity'] ?? 1), 2));
            $unitPrice = max(0.00, round((float)($item['unit_price'] ?? 0), 2));
            $lineTotal = round($qty * $unitPrice, 2);

            $calculatedItems[] = [
                'description' => trim((string)($item['description'] ?? '')),
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];

            $subtotal += $lineTotal;
        }

        $subtotal = round($subtotal, 2);
        $discountVal = max(0.00, (float)$discountValue);
        $discountAmount = 0.00;

        if ($discountType === 'percentage') {
            $cappedPercent = min(100.00, $discountVal);
            $discountAmount = round(($subtotal * ($cappedPercent / 100.00)), 2);
        } else {
            // Fixed discount cannot exceed subtotal
            $discountAmount = min($subtotal, round($discountVal, 2));
        }

        $afterDiscount = max(0.00, round($subtotal - $discountAmount, 2));
        $taxRateNum = max(0.00, (float)$taxRate);
        $taxAmount = 0.00;
        $totalAmount = 0.00;

        if ($pricesIncludeTax && $taxRateNum > 0) {
            // Back-calculate tax from total: total = afterDiscount; tax = total - (total / (1 + rate/100))
            $totalAmount = $afterDiscount;
            $taxAmount = round($totalAmount - ($totalAmount / (1 + ($taxRateNum / 100.00))), 2);
            $taxableAmount = round($totalAmount - $taxAmount, 2);
        } else {
            $taxableAmount = $afterDiscount;
            $taxAmount = round($taxableAmount * ($taxRateNum / 100.00), 2);
            $totalAmount = round($taxableAmount + $taxAmount, 2);
        }

        return [
            'items' => $calculatedItems,
            'subtotal' => $subtotal,
            'discount_type' => $discountType,
            'discount_value' => $discountVal,
            'discount_amount' => $discountAmount,
            'taxable_amount' => $taxableAmount,
            'tax_rate' => $taxRateNum,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Calculate outstanding balance for an invoice given its total and valid payments.
     */
    public static function calculateOutstanding(float $totalAmount, float $paidAmount): float
    {
        return max(0.00, round($totalAmount - $paidAmount, 2));
    }

    /**
     * Determine invoice status from due date, total, and valid collected payments.
     */
    public static function determineInvoiceStatus(
        float $totalAmount,
        float $paidAmount,
        string $dueDate,
        string $currentStatus = 'draft'
    ): string {
        if ($currentStatus === 'void') {
            return 'void';
        }

        $outstanding = self::calculateOutstanding($totalAmount, $paidAmount);

        if ($outstanding <= 0.00 && $totalAmount > 0.00) {
            return 'paid';
        }

        if ($paidAmount > 0.00 && $outstanding > 0.00) {
            return 'partially_paid';
        }

        // Unpaid: check if overdue
        if (strtotime($dueDate) < strtotime(date('Y-m-d'))) {
            return 'overdue';
        }

        return 'unpaid';
    }
}
