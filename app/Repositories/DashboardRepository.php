<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class DashboardRepository
{
    private PDO $pdo;

    public function __construct(?string $connection = null)
    {
        $this->pdo = Database::pdo($connection);
    }

    /**
     * Get authoritative financial KPIs for the executive dashboard.
     */
    public function getFinancialKPIs(string $currency = 'EGP'): array
    {
        // 1. Total Collections (All time and this month)
        $collStmt = $this->pdo->prepare("
            SELECT
                COALESCE(SUM(amount), 0) AS total_collected,
                COALESCE(SUM(CASE WHEN MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE()) THEN amount ELSE 0 END), 0) AS month_collected
            FROM `payments`
            WHERE `status` = 'completed' AND `currency` = :currency
        ");
        $collStmt->execute(['currency' => $currency]);
        $collData = $collStmt->fetch(PDO::FETCH_ASSOC);

        // 2. Outstanding and Overdue Invoices
        $invStmt = $this->pdo->prepare("
            SELECT
                COALESCE(SUM(total_amount - paid_amount), 0) AS total_outstanding,
                COALESCE(SUM(CASE WHEN due_date < CURDATE() AND status != 'paid' THEN (total_amount - paid_amount) ELSE 0 END), 0) AS total_overdue
            FROM `invoices`
            WHERE `status` NOT IN ('draft', 'void', 'paid') AND `currency` = :currency
        ");
        $invStmt->execute(['currency' => $currency]);
        $invData = $invStmt->fetch(PDO::FETCH_ASSOC);

        // 3. Operating Expenses (All time and this month)
        $expStmt = $this->pdo->prepare("
            SELECT
                COALESCE(SUM(amount), 0) AS total_expenses,
                COALESCE(SUM(CASE WHEN MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE()) THEN amount ELSE 0 END), 0) AS month_expenses
            FROM `expenses`
            WHERE `status` = 'paid' AND `currency` = :currency
        ");
        $expStmt->execute(['currency' => $currency]);
        $expData = $expStmt->fetch(PDO::FETCH_ASSOC);

        // 4. Current Cash Balance across accounts of this currency
        $cashStmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(current_balance), 0) AS total_cash_balance
            FROM `cash_accounts`
            WHERE `is_active` = 1 AND `currency` = :currency
        ");
        $cashStmt->execute(['currency' => $currency]);
        $totalCash = (float)$cashStmt->fetchColumn();

        // 5. Recurring Monthly Revenue (MRR)
        $recStmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(monthly_fee), 0) AS mrr
            FROM `recurring_contracts`
            WHERE `status` = 'active' AND `currency` = :currency
        ");
        $recStmt->execute(['currency' => $currency]);
        $mrr = (float)$recStmt->fetchColumn();

        $monthCollected = (float)($collData['month_collected'] ?? 0);
        $monthExpenses = (float)($expData['month_expenses'] ?? 0);
        $netCashFlowMonth = round($monthCollected - $monthExpenses, 2);

        return [
            'currency' => $currency,
            'total_collected' => (float)($collData['total_collected'] ?? 0),
            'month_collected' => $monthCollected,
            'total_outstanding' => (float)($invData['total_outstanding'] ?? 0),
            'total_overdue' => (float)($invData['total_overdue'] ?? 0),
            'total_expenses' => (float)($expData['total_expenses'] ?? 0),
            'month_expenses' => $monthExpenses,
            'net_cash_flow_month' => $netCashFlowMonth,
            'total_cash_balance' => $totalCash,
            'mrr' => $mrr,
        ];
    }

    /**
     * Get sales pipeline KPIs and metrics.
     */
    public function getSalesKPIs(string $currency = 'EGP'): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) AS total_opportunities,
                COALESCE(SUM(estimated_value), 0) AS pipeline_value,
                COALESCE(SUM(estimated_value * (probability / 100.0)), 0) AS weighted_pipeline_value,
                COALESCE(SUM(CASE WHEN stage = 'won' THEN 1 ELSE 0 END), 0) AS won_deals,
                COALESCE(SUM(CASE WHEN stage = 'lost' THEN 1 ELSE 0 END), 0) AS lost_deals,
                COALESCE(SUM(CASE WHEN stage NOT IN ('won', 'lost') THEN 1 ELSE 0 END), 0) AS active_opportunities
            FROM `opportunities`
            WHERE `currency` = :currency
        ");
        $stmt->execute(['currency' => $currency]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalLeads = (int)$this->pdo->query("SELECT COUNT(*) FROM `leads`")->fetchColumn();
        $qualifiedLeads = (int)$this->pdo->query("SELECT COUNT(*) FROM `leads` WHERE `status` = 'qualified'")->fetchColumn();

        $won = (int)($data['won_deals'] ?? 0);
        $totalOpps = (int)($data['total_opportunities'] ?? 0);
        $conversionRate = $totalOpps > 0 ? round(($won / $totalOpps) * 100, 1) : 0.0;

        return [
            'currency' => $currency,
            'total_leads' => $totalLeads,
            'qualified_leads' => $qualifiedLeads,
            'active_opportunities' => (int)($data['active_opportunities'] ?? 0),
            'pipeline_value' => (float)($data['pipeline_value'] ?? 0),
            'weighted_pipeline_value' => round((float)($data['weighted_pipeline_value'] ?? 0), 2),
            'won_deals' => $won,
            'lost_deals' => (int)($data['lost_deals'] ?? 0),
            'conversion_rate' => $conversionRate,
        ];
    }

    /**
     * Get operational Project KPIs.
     */
    public function getProjectKPIs(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                COUNT(*) AS total_projects,
                COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) AS active_projects,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_projects,
                COALESCE(SUM(CASE WHEN status = 'active' AND target_end_date < CURDATE() THEN 1 ELSE 0 END), 0) AS overdue_projects
            FROM `projects`
        ");
        $projData = $stmt->fetch(PDO::FETCH_ASSOC);

        $taskStmt = $this->pdo->query("
            SELECT
                COUNT(*) AS total_tasks,
                COALESCE(SUM(CASE WHEN status != 'done' AND due_date < CURDATE() THEN 1 ELSE 0 END), 0) AS overdue_tasks,
                COALESCE(SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END), 0) AS in_progress_tasks
            FROM `tasks`
        ");
        $taskData = $taskStmt->fetch(PDO::FETCH_ASSOC);

        return [
            'active_projects' => (int)($projData['active_projects'] ?? 0),
            'completed_projects' => (int)($projData['completed_projects'] ?? 0),
            'overdue_projects' => (int)($projData['overdue_projects'] ?? 0),
            'total_tasks' => (int)($taskData['total_tasks'] ?? 0),
            'overdue_tasks' => (int)($taskData['overdue_tasks'] ?? 0),
            'in_progress_tasks' => (int)($taskData['in_progress_tasks'] ?? 0),
        ];
    }

    /**
     * Monthly Collections vs Expenses chart data for the last 6 months.
     */
    public function getMonthlyCashFlowChartData(string $currency = 'EGP'): array
    {
        $months = [];
        $collections = [];
        $expenses = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthStart = date('Y-m-01', strtotime("-{$i} months"));
            $monthEnd = date('Y-m-t', strtotime("-{$i} months"));
            $label = date('M Y', strtotime("-{$i} months"));

            // Collections
            $collStmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM `payments`
                WHERE `status` = 'completed'
                  AND `currency` = :currency
                  AND `payment_date` BETWEEN :start AND :end
            ");
            $collStmt->execute(['currency' => $currency, 'start' => $monthStart, 'end' => $monthEnd]);
            $colVal = (float)$collStmt->fetchColumn();

            // Expenses
            $expStmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM `expenses`
                WHERE `status` = 'paid'
                  AND `currency` = :currency
                  AND `expense_date` BETWEEN :start AND :end
            ");
            $expStmt->execute(['currency' => $currency, 'start' => $monthStart, 'end' => $monthEnd]);
            $expVal = (float)$expStmt->fetchColumn();

            $months[] = $label;
            $collections[] = $colVal;
            $expenses[] = $expVal;
        }

        return [
            'labels' => $months,
            'collections' => $collections,
            'expenses' => $expenses,
        ];
    }

    /**
     * Opportunity breakdown by stage for the sales pipeline chart.
     */
    public function getPipelineStageChartData(string $currency = 'EGP'): array
    {
        $stmt = $this->pdo->prepare("
            SELECT `stage`, COUNT(*) AS total_count, COALESCE(SUM(estimated_value), 0) AS total_val
            FROM `opportunities`
            WHERE `currency` = :currency
            GROUP BY `stage`
            ORDER BY FIELD(`stage`, 'new', 'qualified', 'contacted', 'meeting', 'proposal', 'negotiation', 'won', 'lost')
        ");
        $stmt->execute(['currency' => $currency]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recent invoices for the executive activity feed.
     */
    public function getRecentInvoices(string $currency = 'EGP', int $limit = 5): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                i.id,
                i.invoice_number,
                i.total_amount,
                i.paid_amount,
                i.status,
                i.due_date,
                i.issue_date,
                COALESCE(c.name, '—') AS company_name
            FROM `invoices` i
            LEFT JOIN `companies` c ON i.company_id = c.id
            WHERE i.currency = :currency
            ORDER BY i.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':currency', $currency, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recent payments received for the executive cash feed.
     */
    public function getRecentPayments(string $currency = 'EGP', int $limit = 5): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                p.id,
                p.payment_number,
                p.amount,
                p.currency,
                p.payment_method,
                p.payment_date,
                p.status,
                COALESCE(c.name, '—') AS company_name,
                COALESCE(ca.name, '—') AS account_name
            FROM `payments` p
            LEFT JOIN `companies` c ON p.company_id = c.id
            LEFT JOIN `cash_accounts` ca ON p.cash_account_id = ca.id
            WHERE p.currency = :currency
            ORDER BY p.payment_date DESC, p.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':currency', $currency, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Top high-value open opportunities in the sales pipeline.
     */
    public function getTopOpportunities(string $currency = 'EGP', int $limit = 5): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                o.id,
                o.title,
                o.estimated_value,
                o.stage,
                o.probability,
                o.expected_close_date,
                COALESCE(c.name, '—') AS company_name
            FROM `opportunities` o
            LEFT JOIN `companies` c ON o.company_id = c.id
            WHERE o.currency = :currency AND o.stage NOT IN ('won', 'lost')
            ORDER BY o.estimated_value DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':currency', $currency, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
