<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/seeders/DatabaseSeeder.php';

$app = new App\Core\Application(dirname(__DIR__));

$target = 'mysql';
$withDemo = null;
$seedUsers = null;
$rolesOnly = false;
$allowDemoUsers = false;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--database=')) {
        $target = substr($arg, strlen('--database='));
    }
    if ($arg === '--no-demo') {
        $withDemo = false;
    }
    if ($arg === '--roles-only') {
        $rolesOnly = true;
    }
    if ($arg === '--allow-demo-users') {
        $allowDemoUsers = true;
    }
}

$isProduction = config('app.env') === 'production' || ($target === 'mysql' && !str_contains($target, 'test'));

// Production Safeguard: Never seed demo data or default accounts on production without explicit overrides
if ($isProduction && !$allowDemoUsers && !$rolesOnly) {
    echo "========================================================================\n";
    echo " [SECURITY SAFEGUARD TRIGGERED]\n";
    echo " Target appears to be PRODUCTION ({$target}, env=" . config('app.env') . ").\n";
    echo " Automatic seeding of default demo users with known passwords is BLOCKED.\n";
    echo "\n";
    echo " Safe Options:\n";
    echo " 1. Seed only system roles & permissions: php database/seed.php --roles-only\n";
    echo " 2. Provision secure administrator account: php scripts/create_admin.php\n";
    echo " 3. If running in local/test sandbox: supply flag --allow-demo-users\n";
    echo "========================================================================\n";
    exit(1);
}

// Defaults for testing/local vs production
if ($rolesOnly) {
    $withDemo = false;
    $seedUsers = false;
} elseif ($isProduction) {
    $withDemo = false;
    $seedUsers = false;
} else {
    $withDemo = ($withDemo === null) ? true : $withDemo;
    $seedUsers = true;
}

echo "=== AT Loops OS Database Seeder ===\n";
echo "Target connection: {$target}\n";
echo "Roles & Settings only: " . ($rolesOnly ? 'YES' : 'NO') . "\n";
echo "Demo users included:   " . ($seedUsers ? 'YES' : 'NO') . "\n";
echo "Demo data included:    " . ($withDemo ? 'YES' : 'NO') . "\n";

$seeder = new Database\Seeders\DatabaseSeeder($target);
$seeder->run($withDemo, $seedUsers, $rolesOnly);

echo "=== Seeding finished successfully ===\n";
