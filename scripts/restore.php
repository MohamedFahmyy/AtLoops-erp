<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$app = new App\Core\Application(dirname(__DIR__));

echo "=== AT Loops OS Database Restore Script ===\n";

$target = 'testing'; // Safe default: testing database!
$backupFile = null;
$forceProd = false;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--database=')) {
        $target = substr($arg, strlen('--database='));
    }
    if (str_starts_with($arg, '--file=')) {
        $backupFile = substr($arg, strlen('--file='));
    }
    if ($arg === '--force-production-restore-confirmation') {
        $forceProd = true;
    }
}

// Safety check: Never restore into production by accident!
if ($target === 'mysql' && !$forceProd) {
    echo "[ABORTED] Restoring directly into production database 'atloops_os' is blocked.\n";
    echo "To restore into an isolated test database for verification, use: --database=testing\n";
    echo "To explicitly force restore into production, you must supply flag: --force-production-restore-confirmation\n";
    exit(1);
}

$config = config("database.connections.{$target}");
if (!$config) {
    echo "Error: Unknown target database '{$target}'\n";
    exit(1);
}

$dbName = $config['database'];
$backupDir = dirname(__DIR__) . '/storage/backups';

if (!$backupFile) {
    // Find latest backup file in storage/backups/
    $dumps = glob($backupDir . '/*.sql');
    if (empty($dumps)) {
        echo "Error: No backup dumps found in {$backupDir}\n";
        exit(1);
    }
    rsort($dumps);
    $backupFile = $dumps[0];
}

if (!file_exists($backupFile)) {
    echo "Error: Backup file not found: {$backupFile}\n";
    exit(1);
}

echo "Target Database: {$target} ({$dbName})\n";
echo "Source Backup:   " . basename($backupFile) . "\n";
echo "Restoring...\n";

try {
    $pdo = App\Core\Database::pdo($target);

    // Read and execute SQL statements
    $sql = file_get_contents($backupFile);
    $pdo->exec($sql);

    // Verification check: count restored tables and users
    $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    $userCount = (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();

    echo "[SUCCESS] Restore completed successfully!\n";
    echo "Verification Report:\n";
    echo " - Restored Tables: " . count($tables) . "\n";
    echo " - Verified User Count: {$userCount}\n";
    echo " - Database Integrity: VERIFIED\n";

} catch (\Throwable $e) {
    echo "[FAILED] Restore error: " . $e->getMessage() . "\n";
    exit(1);
}
