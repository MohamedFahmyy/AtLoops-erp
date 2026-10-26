<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\RecurringBillingService;
use PDO;

class RecurringContractController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('finance.invoices.view');

        $pdo = Database::pdo();
        $stmt = $pdo->query("
            SELECT rc.*, c.name AS company_name
            FROM `recurring_contracts` rc
            JOIN `companies` c ON c.id = rc.company_id
            ORDER BY rc.next_billing_date ASC
        ");
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('recurring/index', [
            'pageTitle' => __('common.recurring'),
            'contracts' => $contracts,
        ]);
    }

    public function process(): void
    {
        $this->requirePermission('finance.invoices.manage');

        $result = RecurringBillingService::processRecurringContracts();
        $count = $result['total_processed'] ?? 0;

        $this->redirectBack([
            'success' => "Processed recurring contracts successfully. Generated {$count} new draft invoices."
        ]);
    }
}
