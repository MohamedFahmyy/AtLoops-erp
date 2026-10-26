<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CashAccountRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\ProjectRepository;

class ExpenseController extends Controller
{
    private ExpenseRepository $expenseRepo;
    private CashAccountRepository $accountRepo;
    private ProjectRepository $projectRepo;

    public function __construct()
    {
        $this->expenseRepo = new ExpenseRepository();
        $this->accountRepo = new CashAccountRepository();
        $this->projectRepo = new ProjectRepository();
    }

    public function index(): void
    {
        $this->requirePermission('finance.expenses.view');

        $currency = $_GET['currency'] ?? null;
        $expenses = $this->expenseRepo->getAll($currency);
        $categories = $this->expenseRepo->getCategories();
        $vendors = $this->expenseRepo->getVendors();
        $accounts = $this->accountRepo->getAll();
        $projects = $this->projectRepo->getAll('active');

        $this->render('finance/expenses/index', [
            'pageTitle' => __('common.expenses'),
            'expenses' => $expenses,
            'categories' => $categories,
            'vendors' => $vendors,
            'accounts' => $accounts,
            'projects' => $projects,
            'selectedCurrency' => $currency,
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('finance.expenses.record');

        $data = $this->validate($_POST, [
            'category_id' => 'required|integer',
            'cash_account_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'description' => 'required|min:3|max:255',
        ]);

        try {
            $this->expenseRepo->create($data);
            $this->redirectBack(['success' => 'Expense recorded and deducted from cash account.']);
        } catch (\Throwable $e) {
            $this->redirectBack(['error' => $e->getMessage()]);
        }
    }
}
