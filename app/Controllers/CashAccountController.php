<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CashAccountRepository;
use App\Services\CashAccountTransferService;

class CashAccountController extends Controller
{
    private CashAccountRepository $accountRepo;

    public function __construct()
    {
        $this->accountRepo = new CashAccountRepository();
    }

    public function index(): void
    {
        $this->requirePermission('finance.accounts.view');

        $accounts = $this->accountRepo->getAll();

        $this->render('finance/accounts/index', [
            'pageTitle' => __('common.cash_accounts'),
            'accounts' => $accounts,
        ]);
    }

    public function show(string $id): void
    {
        $this->requirePermission('finance.accounts.view');

        $account = $this->accountRepo->find((int)$id);
        if (!$account) {
            $this->redirect(url('finance/accounts'), ['error' => 'Cash account not found.']);
        }

        $transactions = $this->accountRepo->getTransactions((int)$id);
        $accounts = $this->accountRepo->getAll();

        $this->render('finance/accounts/show', [
            'pageTitle' => $account['name'],
            'account' => $account,
            'transactions' => $transactions,
            'allAccounts' => $accounts,
        ]);
    }

    public function transfer(): void
    {
        $this->requirePermission('finance.accounts.transfer');

        $data = $this->validate($_POST, [
            'from_account_id' => 'required|integer',
            'to_account_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $idempotencyKey = !empty($_POST['idempotency_key']) ? trim((string)$_POST['idempotency_key']) : null;

        try {
            $result = CashAccountTransferService::transfer(
                (int)$data['from_account_id'],
                (int)$data['to_account_id'],
                $data['amount'],
                $_POST['notes'] ?? null,
                $idempotencyKey
            );

            if ($result['is_replay'] ?? false) {
                $this->redirectBack(['info' => $result['message']]);
            } else {
                $this->redirectBack(['success' => 'Cash transfer executed successfully!']);
            }
        } catch (\Throwable $e) {
            $this->redirectBack(['error' => $e->getMessage()]);
        }
    }
}
