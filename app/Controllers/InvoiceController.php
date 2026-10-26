<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CompanyRepository;
use App\Repositories\InvoiceRepository;
use App\Services\PDFService;

class InvoiceController extends Controller
{
    private InvoiceRepository $invoiceRepo;
    private CompanyRepository $companyRepo;

    public function __construct()
    {
        $this->invoiceRepo = new InvoiceRepository();
        $this->companyRepo = new CompanyRepository();
    }

    public function index(): void
    {
        $this->requirePermission('finance.invoices.view');

        $status = $_GET['status'] ?? null;
        $currency = $_GET['currency'] ?? null;
        $invoices = $this->invoiceRepo->getAll($status, $currency);

        $this->render('finance/invoices/index', [
            'pageTitle' => __('common.invoices'),
            'invoices' => $invoices,
            'selectedStatus' => $status,
            'selectedCurrency' => $currency,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('finance.invoices.manage');

        $companies = $this->companyRepo->getAll();

        $this->render('finance/invoices/create', [
            'pageTitle' => __('common.add') . ' ' . __('common.invoices'),
            'companies' => $companies,
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('finance.invoices.manage');

        $data = $this->validate($_POST, [
            'company_id' => 'required|integer',
            'issue_date' => 'required|date',
            'due_date' => 'required|date',
            'currency' => 'required|in:EGP,SAR,USD',
        ]);

        $descriptions = $_POST['item_description'] ?? [];
        $quantities = $_POST['item_quantity'] ?? [];
        $unitPrices = $_POST['item_price'] ?? [];

        $items = [];
        foreach ($descriptions as $idx => $desc) {
            if (!empty(trim((string)$desc))) {
                $items[] = [
                    'description' => trim((string)$desc),
                    'quantity' => (float)($quantities[$idx] ?? 1),
                    'unit_price' => (float)($unitPrices[$idx] ?? 0),
                ];
            }
        }

        if (empty($items)) {
            $this->redirectBack(['error' => 'Please provide at least one invoice item with description and price.']);
        }

        $invoiceId = $this->invoiceRepo->create($data, $items);

        $this->redirect(url("finance/invoices/{$invoiceId}"), [
            'success' => 'Invoice generated successfully.'
        ]);
    }

    public function show(string $id): void
    {
        $this->requirePermission('finance.invoices.view');

        $invoice = $this->invoiceRepo->findWithItemsAndPayments((int)$id);
        if (!$invoice) {
            $this->redirect(url('finance/invoices'), ['error' => 'Invoice not found.']);
        }

        $this->render('finance/invoices/show', [
            'pageTitle' => 'Invoice ' . $invoice['invoice_number'],
            'invoice' => $invoice,
        ]);
    }

    public function downloadPdf(string $id): void
    {
        $this->requirePermission('finance.invoices.view');

        $invoice = $this->invoiceRepo->findWithItemsAndPayments((int)$id);
        if (!$invoice) {
            $this->redirect(url('finance/invoices'), ['error' => 'Invoice not found.']);
        }

        $company = $this->companyRepo->find((int)$invoice['company_id']) ?? [];

        PDFService::generateInvoicePDF($invoice, $invoice['items'] ?? [], $company, 'I');
    }

    public function voidInvoice(string $id): void
    {
        $this->requirePermission('finance.invoices.manage');

        $reason = trim((string)($_POST['reason'] ?? 'Voided by finance officer'));
        $success = $this->invoiceRepo->voidInvoice((int)$id, $reason);

        if ($success) {
            $this->redirectBack(['success' => 'Invoice voided successfully.']);
        } else {
            $this->redirectBack(['error' => 'Cannot void an invoice that already has collected payments.']);
        }
    }
}
