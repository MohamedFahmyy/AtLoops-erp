<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$app = new App\Core\Application(dirname(__DIR__));

$target = 'mysql';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--database=')) {
        $target = substr($arg, strlen('--database='));
    }
}

$config = config("database.connections.{$target}");
if (!$config) {
    echo "Error: Unknown database target '{$target}'\n";
    exit(1);
}

$dbName = $config['database'];
$host = $config['host'];
$port = $config['port'];
$user = $config['username'];
$pass = $config['password'];

echo "=== AT Loops OS Migration Runner ===\n";
echo "Target: {$target} ({$host}:{$port}/{$dbName})\n";

try {
    // 1. Ensure database exists
    $rootPdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[OK] Database '{$dbName}' is ready.\n";

    // 2. Connect to the specific database
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // 3. Ensure migrations table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT UNSIGNED NOT NULL,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 4. Get already executed migrations
    $stmt = $pdo->query("SELECT `migration` FROM `migrations`");
    $executed = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    // 5. Get current max batch
    $batchStmt = $pdo->query("SELECT COALESCE(MAX(`batch`), 0) FROM `migrations`");
    $batch = (int)$batchStmt->fetchColumn() + 1;

    // 6. Find migration files
    $files = glob(__DIR__ . '/migrations/*.sql');
    sort($files);

    $ranAny = false;
    foreach ($files as $file) {
        $migrationName = basename($file);
        if (in_array($migrationName, $executed, true)) {
            continue;
        }

        echo "Migrating: {$migrationName}... ";
        $sql = file_get_contents($file);

        // Execute SQL script
        $pdo->beginTransaction();
        try {
            $pdo->exec($sql);
            $recordStmt = $pdo->prepare("INSERT INTO `migrations` (`migration`, `batch`) VALUES (:migration, :batch)");
            $recordStmt->execute(['migration' => $migrationName, 'batch' => $batch]);
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
            echo "[DONE]\n";
            $ranAny = true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "[FAILED]\n";
            echo "Error executing {$migrationName}: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    if (!$ranAny) {
        echo "Nothing to migrate. Database is up to date.\n";
    }

    echo "=== Migration finished successfully ===\n";
} catch (\Throwable $e) {
    echo "Fatal migration error: " . $e->getMessage() . "\n";
    exit(1);
}
