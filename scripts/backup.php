<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$app = new App\Core\Application(dirname(__DIR__));

echo "=== AT Loops OS Database Backup Script ===\n";

$target = 'mysql';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--database=')) {
        $target = substr($arg, strlen('--database='));
    }
}

$config = config("database.connections.{$target}");
if (!$config) {
    echo "Error: Unknown target database '{$target}'\n";
    exit(1);
}

$dbName = $config['database'];
$backupDir = dirname(__DIR__) . '/storage/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$filename = 'backup_' . $dbName . '_' . date('Ymd_His') . '.sql';
$filePath = $backupDir . '/' . $filename;

echo "Backing up database '{$dbName}' to: {$filename}...\n";

try {
    $pdo = App\Core\Database::pdo($target);

    // Get all tables
    $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

    $fp = fopen($filePath, 'w');
    if (!$fp) {
        throw new RuntimeException("Cannot open backup file for writing: {$filePath}");
    }

    fwrite($fp, "-- AT Loops OS Database Backup\n");
    fwrite($fp, "-- Database: {$dbName}\n");
    fwrite($fp, "-- Generated at: " . date('Y-m-d H:i:s') . "\n");
    fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n\n");

    $totalRows = 0;
    foreach ($tables as $table) {
        // Table schema
        $createRow = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n");
        fwrite($fp, $createRow[1] . ";\n\n");

        // Table rows
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $totalRows += count($rows);
            $columns = array_keys($rows[0]);
            $colList = implode('`, `', $columns);

            foreach ($rows as $row) {
                $values = array_map(function ($val) use ($pdo) {
                    if ($val === null) {
                        return 'NULL';
                    }
                    return $pdo->quote((string)$val);
                }, array_values($row));

                $valList = implode(', ', $values);
                fwrite($fp, "INSERT INTO `{$table}` (`{$colList}`) VALUES ({$valList});\n");
            }
            fwrite($fp, "\n");
        }
    }

    fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fp);

    echo "[SUCCESS] Backup complete! Exported " . count($tables) . " tables, {$totalRows} rows.\n";
    echo "Saved to: {$filePath}\n";

    // Enforce retention policy
    $retentionDays = (int)($_ENV['BACKUP_RETENTION_DAYS'] ?? 30);
    $threshold = time() - ($retentionDays * 86400);
    $existingDumps = glob($backupDir . '/*.sql');
    $pruned = 0;

    foreach ($existingDumps as $dump) {
        if (filemtime($dump) < $threshold) {
            @unlink($dump);
            $pruned++;
        }
    }
    if ($pruned > 0) {
        echo "Pruned {$pruned} old backup dumps past {$retentionDays} days retention.\n";
    }

} catch (\Throwable $e) {
    echo "[FAILED] Backup error: " . $e->getMessage() . "\n";
    exit(1);
}
