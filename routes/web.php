<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */
$router = app()->getRouter();

use App\Controllers\AuthController;
use App\Controllers\CashAccountController;
use App\Controllers\CompanyController;
use App\Controllers\DashboardController;
use App\Controllers\DocumentController;
use App\Controllers\ExpenseController;
use App\Controllers\InvoiceController;
use App\Controllers\OpportunityController;
use App\Controllers\PaymentController;
use App\Controllers\ProjectController;
use App\Controllers\RecurringContractController;
use App\Controllers\ReportController;
use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\CSRFMiddleware;
use App\Core\Middleware\GuestMiddleware;

// 1. Language switcher (Public)
$router->get('/lang', [AuthController::class, 'switchLocale']);

// 2. Guest Authentication Routes
$router->group(['middleware' => [GuestMiddleware::class]], function (\App\Core\Router $r) {
    $r->get('/login', [AuthController::class, 'showLogin']);
    $r->post('/login', [AuthController::class, 'login'], [CSRFMiddleware::class]);
});

// 3. Authenticated Application Routes
$router->group(['middleware' => [AuthMiddleware::class]], function (\App\Core\Router $r) {
    $r->post('/logout', [AuthController::class, 'logout'], [CSRFMiddleware::class]);

    // Dashboard
    $r->get('/', [DashboardController::class, 'index']);
    $r->get('/dashboard', [DashboardController::class, 'index']);

    // CRM - Companies
    $r->get('/crm/companies', [CompanyController::class, 'index']);
    $r->get('/crm/companies/create', [CompanyController::class, 'create']);
    $r->post('/crm/companies', [CompanyController::class, 'store'], [CSRFMiddleware::class]);
    $r->get('/crm/companies-export', [CompanyController::class, 'exportCsv']);
    $r->get('/crm/companies/{id:\d+}', [CompanyController::class, 'show']);

    // CRM - Opportunities
    $r->get('/crm/opportunities', [OpportunityController::class, 'index']);
    $r->get('/crm/opportunities/create', [OpportunityController::class, 'create']);
    $r->post('/crm/opportunities', [OpportunityController::class, 'store'], [CSRFMiddleware::class]);
    $r->post('/crm/opportunities/{id:\d+}/stage', [OpportunityController::class, 'updateStage'], [CSRFMiddleware::class]);
    $r->post('/crm/opportunities/{id:\d+}/convert-quote', [OpportunityController::class, 'convertToQuotation'], [CSRFMiddleware::class]);

    // Projects
    $r->get('/projects', [ProjectController::class, 'index']);
    $r->get('/projects/{id:\d+}', [ProjectController::class, 'show']);
    $r->get('/projects/{id:\d+}/kanban', [ProjectController::class, 'kanban']);
    $r->post('/projects/tasks/{taskId:\d+}/status', [ProjectController::class, 'updateTaskStatus'], [CSRFMiddleware::class]);
    $r->post('/projects/{id:\d+}/tasks', [ProjectController::class, 'storeTask'], [CSRFMiddleware::class]);
    $r->post('/projects/{id:\d+}/time', [ProjectController::class, 'logTime'], [CSRFMiddleware::class]);

    // Finance - Invoices
    $r->get('/finance/invoices', [InvoiceController::class, 'index']);
    $r->get('/finance/invoices/create', [InvoiceController::class, 'create']);
    $r->post('/finance/invoices', [InvoiceController::class, 'store'], [CSRFMiddleware::class]);
    $r->get('/finance/invoices/{id:\d+}', [InvoiceController::class, 'show']);
    $r->get('/finance/invoices/{id:\d+}/pdf', [InvoiceController::class, 'downloadPdf']);
    $r->post('/finance/invoices/{id:\d+}/void', [InvoiceController::class, 'voidInvoice'], [CSRFMiddleware::class]);

    // Finance - Payments
    $r->post('/finance/payments', [PaymentController::class, 'store'], [CSRFMiddleware::class]);
    $r->post('/finance/payments/{id:\d+}/reverse', [PaymentController::class, 'reverse'], [CSRFMiddleware::class]);

    // Finance - Cash Accounts
    $r->get('/finance/accounts', [CashAccountController::class, 'index']);
    $r->get('/finance/accounts/{id:\d+}', [CashAccountController::class, 'show']);
    $r->post('/finance/accounts/transfer', [CashAccountController::class, 'transfer'], [CSRFMiddleware::class]);

    // Finance - Expenses
    $r->get('/finance/expenses', [ExpenseController::class, 'index']);
    $r->post('/finance/expenses', [ExpenseController::class, 'store'], [CSRFMiddleware::class]);

    // Recurring Retainers
    $r->get('/recurring', [RecurringContractController::class, 'index']);
    $r->post('/recurring/process', [RecurringContractController::class, 'process'], [CSRFMiddleware::class]);

    // Document Vault
    $r->get('/documents', [DocumentController::class, 'index']);
    $r->post('/documents/upload', [DocumentController::class, 'upload'], [CSRFMiddleware::class]);
    $r->get('/documents/{id:\d+}/download', [DocumentController::class, 'download']);

    // Reports
    $r->get('/reports', [ReportController::class, 'index']);
    $r->get('/reports/collections-csv', [ReportController::class, 'exportCollectionsCsv']);
});
