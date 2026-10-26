<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Repositories\DashboardRepository;
use PDO;

class ReportController extends Controller
{
    private DashboardRepository $dashboardRepo;

    public function __construct()
    {
        $this->dashboardRepo = new DashboardRepository();
    }

    public function index(): void
    {
        $this->requirePermission('reports.view');

        $currency = $_GET['currency'] ?? 'EGP';
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        $pdo = Database::pdo();

        // 1. Collections in period
        $collStmt = $pdo->prepare("
            SELECT p.*, c.name AS company_name, ca.name AS account_name
            FROM `payments` p
            JOIN `companies` c ON c.id = p.company_id
            JOIN `cash_accounts` ca ON ca.id = p.cash_account_id
            WHERE p.status = 'completed'
              AND p.currency = :currency
              AND p.payment_date BETWEEN :start AND :end
            ORDER BY p.payment_date DESC
        ");
        $collStmt->execute(['currency' => $currency, 'start' => $startDate, 'end' => $endDate]);
        $payments = $collStmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Expenses in period
        $expStmt = $pdo->prepare("
            SELECT e.*, ec.name AS category_name, ca.name AS account_name
            FROM `expenses` e
            JOIN `expense_categories` ec ON ec.id = e.category_id
            JOIN `cash_accounts` ca ON ca.id = e.cash_account_id
            WHERE e.status = 'paid'
              AND e.currency = :currency
              AND e.expense_date BETWEEN :start AND :end
            ORDER BY e.expense_date DESC
        ");
        $expStmt->execute(['currency' => $currency, 'start' => $startDate, 'end' => $endDate]);
        $expenses = $expStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Aged Receivables
        $recStmt = $pdo->prepare("
            SELECT i.*, c.name AS company_name,
                   (i.total_amount - i.paid_amount) AS outstanding_amount,
                   DATEDIFF(CURDATE(), i.due_date) AS days_overdue
            FROM `invoices` i
            JOIN `companies` c ON c.id = i.company_id
            WHERE i.status NOT IN ('draft', 'void', 'paid')
              AND i.currency = :currency
            ORDER BY i.due_date ASC
        ");
        $recStmt->execute(['currency' => $currency]);
        $receivables = $recStmt->fetchAll(PDO::FETCH_ASSOC);

        // Summary calculations
        $totalCollected = array_sum(array_column($payments, 'amount'));
        $totalExpenses = array_sum(array_column($expenses, 'amount'));
        $netCashFlow = round($totalCollected - $totalExpenses, 2);
        $totalReceivables = array_sum(array_column($receivables, 'outstanding_amount'));

        $this->render('reports/index', [
            'pageTitle' => __('common.reports'),
            'selectedCurrency' => $currency,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'payments' => $payments,
            'expenses' => $expenses,
            'receivables' => $receivables,
            'totalCollected' => $totalCollected,
            'totalExpenses' => $totalExpenses,
            'netCashFlow' => $netCashFlow,
            'totalReceivables' => $totalReceivables,
        ]);
    }

    public function exportCollectionsCsv(): void
    {
        $this->requirePermission('reports.view');

        $currency = $_GET['currency'] ?? 'EGP';
        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            SELECT p.payment_number, p.payment_date, c.name AS company_name,
                   p.amount, p.currency, p.payment_method, p.reference_number
            FROM `payments` p
            JOIN `companies` c ON c.id = p.company_id
            WHERE p.status = 'completed'
              AND p.currency = :currency
              AND p.payment_date BETWEEN :start AND :end
            ORDER BY p.payment_date DESC
        ");
        $stmt->execute(['currency' => $currency, 'start' => $startDate, 'end' => $endDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="collections_' . date('Ymd') . '.csv"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Payment #', 'Date', 'Client', 'Amount', 'Currency', 'Method', 'Reference']);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['payment_number'],
                $r['payment_date'],
                $r['company_name'],
                $r['amount'],
                $r['currency'],
                $r['payment_method'],
                $r['reference_number'],
            ]);
        }
        fclose($out);
        exit;
    }
}
