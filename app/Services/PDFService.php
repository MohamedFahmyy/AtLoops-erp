<?php

declare(strict_types=1);

namespace App\Services;

use TCPDF;

class PDFService
{
    /**
     * Generate an Invoice PDF.
     *
     * @param array $invoice
     * @param array $items
     * @param array $company
     * @param string $outputMode 'I' = Inline, 'D' = Download, 'F' = Save file, 'S' = String
     * @return string
     */
    public static function generateInvoicePDF(
        array $invoice,
        array $items,
        array $company,
        string $outputMode = 'I',
        ?string $filePath = null
    ): string {
        $isRtl = is_rtl();

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('AT Loops OS');
        $pdf->SetAuthor('AT Loops');
        $pdf->SetTitle('Invoice ' . ($invoice['invoice_number'] ?? ''));

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        if ($isRtl) {
            $pdf->setRTL(true);
            $pdf->SetFont('aealarabiya', '', 10);
        } else {
            $pdf->setRTL(false);
            $pdf->SetFont('helvetica', '', 10);
        }

        $pdf->AddPage();

        $currency = $invoice['currency'] ?? 'EGP';
        $subtotal = number_format((float)($invoice['subtotal'] ?? 0), 2);
        $discount = number_format((float)($invoice['discount_amount'] ?? 0), 2);
        $tax = number_format((float)($invoice['tax_amount'] ?? 0), 2);
        $total = number_format((float)($invoice['total_amount'] ?? 0), 2);
        $paid = number_format((float)($invoice['paid_amount'] ?? 0), 2);
        $outstanding = number_format(max(0, (float)$invoice['total_amount'] - (float)$invoice['paid_amount']), 2);

        $logoPath = dirname(__DIR__, 2) . '/public/assets/images/logo.png';
        $logoHtml = file_exists($logoPath)
            ? '<img src="' . $logoPath . '" height="42" /><br>'
            : '';

        $html = '
        <table width="100%" cellpadding="4" style="border-bottom: 2px solid #0d6efd;">
            <tr>
                <td width="60%">
                    ' . $logoHtml . '
                    <h1 style="color: #0d6efd; margin: 0; font-size: 20px;">' . ($isRtl ? 'شركة إيه تي لوبس' : 'At Loops') . '</h1>
                    <p style="color: #555; margin: 2px 0;">' . ($isRtl ? 'حلول البرمجيات والتحول الرقمي' : 'For Software & Digital Transformation') . '<br>
                    Website: https://atloops.com | Email: billing@atloops.com<br>
                    Tax ID: EG-TAX-987654321
                    </p>
                </td>
                <td width="40%" align="' . ($isRtl ? 'left' : 'right') . '">
                    <h2 style="color: #333; margin: 0; font-size: 18px;">' . ($isRtl ? 'فاتورة ضريبية' : 'TAX INVOICE') . '</h2>
                    <p style="margin: 2px 0; font-size: 11px;">
                        <b>' . ($isRtl ? 'رقم الفاتورة:' : 'Invoice No:') . '</b> ' . e($invoice['invoice_number'] ?? '') . '<br>
                        <b>' . ($isRtl ? 'تاريخ الإصدار:' : 'Date:') . '</b> ' . e($invoice['issue_date'] ?? '') . '<br>
                        <b>' . ($isRtl ? 'تاريخ الاستحقاق:' : 'Due Date:') . '</b> ' . e($invoice['due_date'] ?? '') . '<br>
                        <b>' . ($isRtl ? 'الحالة:' : 'Status:') . '</b> <span style="color: #0d6efd;">' . strtoupper($invoice['status'] ?? 'DRAFT') . '</span>
                    </p>
                </td>
            </tr>
        </table>

        <br><br>

        <table width="100%" cellpadding="6" style="background-color: #f8f9fa; border: 1px solid #dee2e6;">
            <tr>
                <td>
                    <b>' . ($isRtl ? 'بيانات العميل / الفاتورة إلى:' : 'Billed To:') . '</b><br>
                    <span style="font-size: 13px; font-weight: bold;">' . e($company['name'] ?? 'Client') . '</span><br>
                    ' . ($company['address'] ? e($company['address']) . '<br>' : '') . '
                    ' . ($company['phone'] ? 'Phone: ' . e($company['phone']) . ' | ' : '') . '
                    ' . ($company['tax_number'] ? 'Tax ID: ' . e($company['tax_number']) : '') . '
                </td>
            </tr>
        </table>

        <br><br>

        <table width="100%" cellpadding="6" cellspacing="0" style="border: 1px solid #dee2e6;">
            <thead>
                <tr style="background-color: #0d6efd; color: #ffffff; font-weight: bold;">
                    <th width="8%" align="center">#</th>
                    <th width="52%">' . ($isRtl ? 'بيان الخدمة / التوصيف' : 'Item Description') . '</th>
                    <th width="12%" align="center">' . ($isRtl ? 'الكمية' : 'Qty') . '</th>
                    <th width="14%" align="' . ($isRtl ? 'left' : 'right') . '">' . ($isRtl ? 'سعر الوحدة' : 'Unit Price') . '</th>
                    <th width="14%" align="' . ($isRtl ? 'left' : 'right') . '">' . ($isRtl ? 'الإجمالي' : 'Total') . '</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($items as $idx => $it) {
            $bgColor = ($idx % 2 === 0) ? '#ffffff' : '#fcfcfc';
            $html .= '
                <tr style="background-color: ' . $bgColor . '; border-bottom: 1px solid #dee2e6;">
                    <td align="center">' . ($idx + 1) . '</td>
                    <td>' . nl2br(e($it['description'])) . '</td>
                    <td align="center">' . number_format((float)$it['quantity'], 2) . '</td>
                    <td align="' . ($isRtl ? 'left' : 'right') . '">' . number_format((float)$it['unit_price'], 2) . '</td>
                    <td align="' . ($isRtl ? 'left' : 'right') . '">' . number_format((float)$it['line_total'], 2) . '</td>
                </tr>';
        }

        $html .= '
            </tbody>
        </table>

        <br><br>

        <table width="100%" cellpadding="4">
            <tr>
                <td width="55%" style="vertical-align: top;">
                    <div style="border: 1px solid #dee2e6; padding: 10px; background-color: #f8f9fa;">
                        <b>' . ($isRtl ? 'بيانات التحويل البنكي:' : 'Payment Instructions:') . '</b><br>
                        Bank: Commercial International Bank (CIB)<br>
                        Account Name: AT Loops for IT<br>
                        IBAN: EG45001000456789012345678901<br>
                        Swift Code: CIBEEGCX
                    </div>
                </td>
                <td width="45%" align="' . ($isRtl ? 'left' : 'right') . '">
                    <table width="100%" cellpadding="3" style="border: 1px solid #dee2e6;">
                        <tr>
                            <td><b>' . ($isRtl ? 'المجموع الفرعي:' : 'Subtotal:') . '</b></td>
                            <td align="' . ($isRtl ? 'left' : 'right') . '">' . $subtotal . ' ' . $currency . '</td>
                        </tr>
                        <tr>
                            <td>' . ($isRtl ? 'الخصم:' : 'Discount:') . '</td>
                            <td align="' . ($isRtl ? 'left' : 'right') . '">-' . $discount . ' ' . $currency . '</td>
                        </tr>
                        <tr>
                            <td>' . ($isRtl ? 'ضريبة القيمة المضافة (14%):' : 'VAT (14%):') . '</td>
                            <td align="' . ($isRtl ? 'left' : 'right') . '">' . $tax . ' ' . $currency . '</td>
                        </tr>
                        <tr style="background-color: #e9ecef; font-weight: bold; font-size: 12px;">
                            <td>' . ($isRtl ? 'الإجمالي النهائي:' : 'Grand Total:') . '</td>
                            <td align="' . ($isRtl ? 'left' : 'right') . '">' . $total . ' ' . $currency . '</td>
                        </tr>
                        <tr>
                            <td>' . ($isRtl ? 'المبلغ المحصل:' : 'Paid Amount:') . '</td>
                            <td align="' . ($isRtl ? 'left' : 'right') . '" style="color: green;">' . $paid . ' ' . $currency . '</td>
                        </tr>
                        <tr style="font-weight: bold;">
                            <td>' . ($isRtl ? 'المبلغ المستحق:' : 'Balance Due:') . '</td>
                            <td align="' . ($isRtl ? 'left' : 'right') . '" style="color: #dc3545;">' . $outstanding . ' ' . $currency . '</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        if ($outputMode === 'F' && $filePath) {
            $pdf->Output($filePath, 'F');
            return $filePath;
        }

        return $pdf->Output('invoice_' . ($invoice['invoice_number'] ?? 'draft') . '.pdf', $outputMode);
    }
}
