<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use App\Core\Database;
use App\Services\AuditLogger;
use App\Services\RecurringBillingService;

$app = new Application(dirname(__DIR__));

$target = 'mysql';
$task = 'all';
$force = false;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--database=')) {
        $target = substr($arg, strlen('--database='));
    }
    if (str_starts_with($arg, '--task=')) {
        $task = strtolower(substr($arg, strlen('--task=')));
    }
    if ($arg === '--force') {
        $force = true;
    }
}

$validTasks = ['all', 'recurring', 'overdue_invoices', 'overdue_tasks'];
if (!in_array($task, $validTasks, true)) {
    echo "[ERROR] Invalid task: '{$task}'. Available tasks: " . implode(', ', $validTasks) . "\n";
    exit(1);
}

// 1. Process Concurrency Lock (Mutex via flock scoped by task)
$lockPath = dirname(__DIR__) . "/storage/cache/cron_{$task}.lock";
$lockHandle = fopen($lockPath, 'c+');

if (!$lockHandle || (!flock($lockHandle, LOCK_EX | LOCK_NB) && !$force)) {
    echo "[" . date('Y-m-d H:i:s') . "] [CRON SKIPPED] Another instance for task '{$task}' is currently active. Concurrency lock held.\n";
    exit(0);
}

echo "=== AT Loops OS Master Cron Job Runner ===\n";
echo "Execution time: " . date('Y-m-d H:i:s T') . "\n";
echo "Target Database: {$target}\n";
echo "Selected Task:   {$task}\n\n";

$logFile = dirname(__DIR__) . '/storage/logs/cron.log';
$logEntry = function (string $msg) use ($logFile): void {
    $line = "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n";
    echo $msg . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
};

$summary = [
    'recurring_invoices' => 0,
    'overdue_invoices' => 0,
    'overdue_tasks' => 0,
    'errors' => [],
];

try {
    $pdo = Database::pdo($target);

    // Task 1: Process Recurring Service Retainers & Draft Invoices
    if ($task === 'all' || $task === 'recurring') {
        try {
            $logEntry(">> [Task: Recurring Retainers] Processing recurring contract retainers...");
            $recResult = RecurringBillingService::processRecurringContracts(null, $target);
            $createdCount = count($recResult['invoices_created'] ?? []);
            $skippedCount = count($recResult['skipped_replays'] ?? []);
            $summary['recurring_invoices'] = $createdCount;
            $logEntry("   [SUCCESS] Generated {$createdCount} invoice drafts; skipped {$skippedCount} existing period retainers.");
        } catch (\Throwable $e) {
            $summary['errors']['recurring_billing'] = $e->getMessage();
            $logEntry("   [FAILED] Error: " . $e->getMessage());
        }
    }

    // Task 2: Scan and Flag Overdue Invoices
    if ($task === 'all' || $task === 'overdue_invoices') {
        try {
            $logEntry(">> [Task: Overdue Invoices] Checking overdue invoices...");
            $overdueStmt = $pdo->query("
                SELECT id, invoice_number, total_amount, due_date
                FROM `invoices`
                WHERE `status` IN ('unpaid', 'partially_paid')
                  AND `due_date` < CURDATE()
            ");
            $overdueInvoices = $overdueStmt->fetchAll(PDO::FETCH_ASSOC);

            $updStmt = $pdo->prepare("UPDATE `invoices` SET `status` = 'overdue' WHERE id = :id");
            $flagged = 0;
            foreach ($overdueInvoices as $inv) {
                $updStmt->execute(['id' => $inv['id']]);
                $flagged++;
            }
            $summary['overdue_invoices'] = $flagged;
            $logEntry("   [SUCCESS] Identified and updated {$flagged} overdue invoices.");
        } catch (\Throwable $e) {
            $summary['errors']['overdue_invoices'] = $e->getMessage();
            $logEntry("   [FAILED] Error: " . $e->getMessage());
        }
    }

    // Task 3: Identify Overdue Project Tasks
    if ($task === 'all' || $task === 'overdue_tasks') {
        try {
            $logEntry(">> [Task: Overdue Tasks] Checking overdue project tasks...");
            $taskStmt = $pdo->query("
                SELECT id, title, assigned_to
                FROM `tasks`
                WHERE `status` NOT IN ('done')
                  AND `due_date` < CURDATE()
            ");
            $overdueTasks = $taskStmt->fetchAll(PDO::FETCH_ASSOC);
            $summary['overdue_tasks'] = count($overdueTasks);
            $logEntry("   [SUCCESS] Found " . count($overdueTasks) . " overdue project tasks.");
        } catch (\Throwable $e) {
            $summary['errors']['overdue_tasks'] = $e->getMessage();
            $logEntry("   [FAILED] Error: " . $e->getMessage());
        }
    }

    $logEntry("=== Cron execution ({$task}) completed successfully ===");

} catch (\Throwable $e) {
    $logEntry("[CRON FATAL] Database connection or system fatal failure: " . $e->getMessage());
} finally {
    // Release concurrency lock
    if (is_resource($lockHandle)) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}
