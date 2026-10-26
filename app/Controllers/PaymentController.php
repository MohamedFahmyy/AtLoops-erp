<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CashAccountRepository;
use App\Repositories\InvoiceRepository;
use App\Services\PaymentAllocationService;

class PaymentController extends Controller
{
    private InvoiceRepository $invoiceRepo;
    private CashAccountRepository $accountRepo;

    public function __construct()
    {
        $this->invoiceRepo = new InvoiceRepository();
        $this->accountRepo = new CashAccountRepository();
    }

    public function store(): void
    {
        $this->requirePermission('finance.payments.record');

        $data = $this->validate($_POST, [
            'invoice_id' => 'required|integer',
            'cash_account_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:bank_transfer,cash,check,online',
        ]);

        $idempotencyKey = !empty($_POST['idempotency_key']) ? trim((string)$_POST['idempotency_key']) : null;
        $data['idempotency_key'] = $idempotencyKey;
        $data['reference_number'] = $_POST['reference_number'] ?? null;
        $data['notes'] = $_POST['notes'] ?? null;

        try {
            $result = PaymentAllocationService::recordPayment($data);

            if ($result['is_replay'] ?? false) {
                $this->redirectBack(['info' => $result['message']]);
            } else {
                $this->redirectBack(['success' => 'Payment recorded and allocated successfully!']);
            }
        } catch (\Throwable $e) {
            $this->redirectBack(['error' => $e->getMessage()]);
        }
    }

    public function reverse(string $id): void
    {
        $this->requirePermission('finance.payments.reverse');

        $reason = trim((string)($_POST['reason'] ?? 'Payment error reversal'));
        if (empty($reason)) {
            $this->redirectBack(['error' => 'A clear reversal reason must be documented.']);
        }

        try {
            PaymentAllocationService::reversePayment((int)$id, $reason);
            $this->redirectBack(['success' => 'Payment reversed and balance restored successfully.']);
        } catch (\Throwable $e) {
            $this->redirectBack(['error' => $e->getMessage()]);
        }
    }
}
