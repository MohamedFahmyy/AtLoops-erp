<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class ProjectProfitabilityService
{
    /**
     * Compute real-time cost, revenue, margin, and profitability metrics for a project.
     *
     * @param int $projectId
     * @param string|null $connection
     * @return array
     */
    public static function calculateProfitability(int $projectId, ?string $connection = null): array
    {
        $pdo = Database::pdo($connection);

        // Fetch project metadata
        $projStmt = $pdo->prepare("SELECT * FROM `projects` WHERE `id` = :id LIMIT 1");
        $projStmt->execute(['id' => $projectId]);
        $project = $projStmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) {
            return [];
        }

        $currency = $project['currency'];
        $budget = (float)$project['budget_amount'];

        // 1. Labor Cost & Hours from Time Entries
        $timeStmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(duration_minutes), 0) AS total_minutes,
                COALESCE(SUM(total_cost), 0) AS total_labor_cost
            FROM `time_entries`
            WHERE `project_id` = :id
        ");
        $timeStmt->execute(['id' => $projectId]);
        $timeData = $timeStmt->fetch(PDO::FETCH_ASSOC);

        $totalMinutes = (int)($timeData['total_minutes'] ?? 0);
        $totalHours = round($totalMinutes / 60, 2);
        $laborCost = round((float)($timeData['total_labor_cost'] ?? 0), 2);

        // 2. Direct Project Expenses (paid only)
        $expStmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) AS total_expenses
            FROM `expenses`
            WHERE `project_id` = :id AND `status` = 'paid'
        ");
        $expStmt->execute(['id' => $projectId]);
        $directExpenses = round((float)$expStmt->fetchColumn(), 2);

        // 3. Invoiced and Collected Revenue
        $invStmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(total_amount), 0) AS total_invoiced,
                COALESCE(SUM(paid_amount), 0) AS total_collected
            FROM `invoices`
            WHERE `project_id` = :id AND `status` != 'void'
        ");
        $invStmt->execute(['id' => $projectId]);
        $invData = $invStmt->fetch(PDO::FETCH_ASSOC);

        $invoicedRevenue = round((float)($invData['total_invoiced'] ?? 0), 2);
        $collectedRevenue = round((float)($invData['total_collected'] ?? 0), 2);

        // Fallback to budget if no invoice issued yet for projection
        $recognizedRevenue = $invoicedRevenue > 0 ? $invoicedRevenue : $budget;

        // 4. Totals and Margins
        $totalCost = round($laborCost + $directExpenses, 2);
        $grossMarginAmount = round($recognizedRevenue - $totalCost, 2);
        $grossMarginPercent = $recognizedRevenue > 0
            ? round(($grossMarginAmount / $recognizedRevenue) * 100, 2)
            : 0.00;

        $budgetUtilization = $budget > 0
            ? round(($totalCost / $budget) * 100, 2)
            : 0.00;

        // Status Determination
        $status = 'healthy';
        if ($grossMarginAmount < 0) {
            $status = 'unprofitable';
        } elseif ($grossMarginPercent < 20 || $budgetUtilization > 90) {
            $status = 'at_risk';
        }

        return [
            'project_id' => $projectId,
            'project_name' => $project['name'],
            'project_code' => $project['code'],
            'currency' => $currency,
            'budget' => $budget,
            'total_hours' => $totalHours,
            'labor_cost' => $laborCost,
            'direct_expenses' => $directExpenses,
            'total_cost' => $totalCost,
            'invoiced_revenue' => $invoicedRevenue,
            'collected_revenue' => $collectedRevenue,
            'recognized_revenue' => $recognizedRevenue,
            'gross_margin_amount' => $grossMarginAmount,
            'gross_margin_percent' => $grossMarginPercent,
            'budget_utilization_percent' => $budgetUtilization,
            'profitability_status' => $status,
        ];
    }
}
