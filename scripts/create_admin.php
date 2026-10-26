<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$app = new App\Core\Application(dirname(__DIR__));

echo "========================================================\n";
echo "   AT Loops OS — Secure Administrator Provisioning CLI\n";
echo "========================================================\n\n";

$target = 'mysql';
$name = null;
$email = null;
$password = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--database=')) {
        $target = substr($arg, strlen('--database='));
    }
    if (str_starts_with($arg, '--name=')) {
        $name = trim(substr($arg, strlen('--name=')));
    }
    if (str_starts_with($arg, '--email=')) {
        $email = strtolower(trim(substr($arg, strlen('--email='))));
    }
    if (str_starts_with($arg, '--password=')) {
        $password = substr($arg, strlen('--password='));
    }
}

// Interactive input if arguments omitted
if (!$name) {
    echo "Enter Administrator Full Name: ";
    $name = trim((string)fgets(STDIN));
}

if (!$email) {
    echo "Enter Administrator Email: ";
    $email = strtolower(trim((string)fgets(STDIN)));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "\n[ERROR] Invalid email address: '{$email}'\n";
    exit(1);
}

if (!$password) {
    // Generate secure 16-character password
    $bytes = random_bytes(10);
    $password = substr(base64_encode($bytes), 0, 14) . '!9';
    $generated = true;
} else {
    $generated = false;
}

// Password Complexity Check: min 10 chars, uppercase, lowercase, digit, symbol
if (strlen($password) < 10 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) ||
    !preg_match('/[^A-Za-z0-9]/', $password)
) {
    echo "\n[ERROR] Password does not meet security complexity requirements:\n";
    echo " - Minimum length: 10 characters\n";
    echo " - Must contain at least one uppercase letter [A-Z]\n";
    echo " - Must contain at least one lowercase letter [a-z]\n";
    echo " - Must contain at least one digit [0-9]\n";
    echo " - Must contain at least one special symbol\n";
    exit(1);
}

try {
    $pdo = App\Core\Database::pdo($target);

    // Check if user already exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $existingId = $stmt->fetchColumn();

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    if ($existingId) {
        $upd = $pdo->prepare('UPDATE users SET name = :name, password = :pwd, is_active = 1 WHERE id = :id');
        $upd->execute(['name' => $name, 'pwd' => $hashedPassword, 'id' => $existingId]);
        $adminId = (int)$existingId;
        echo "\n[INFO] Updated existing administrator account #{$adminId}.\n";
    } else {
        $ins = $pdo->prepare('
            INSERT INTO users (name, email, password, job_title, hourly_cost, is_active)
            VALUES (:name, :email, :pwd, :job, 150.00, 1)
        ');
        $ins->execute([
            'name' => $name,
            'email' => $email,
            'pwd' => $hashedPassword,
            'job' => 'System Administrator',
        ]);
        $adminId = (int)$pdo->lastInsertId();
        echo "\n[SUCCESS] Created new administrator account #{$adminId}.\n";
    }

    // Ensure super_admin role is assigned
    $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE slug = "super_admin" LIMIT 1');
    $roleStmt->execute();
    $roleId = (int)$roleStmt->fetchColumn();

    if ($roleId > 0) {
        $assignStmt = $pdo->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (:uid, :rid)');
        $assignStmt->execute(['uid' => $adminId, 'rid' => $roleId]);
        echo "[SUCCESS] Assigned 'super_admin' role to user.\n";
    }

    echo "\n=== PROVISIONING COMPLETE ===\n";
    echo "Target Database: {$target}\n";
    echo "Admin Name:      {$name}\n";
    echo "Admin Email:     {$email}\n";
    if ($generated) {
        echo "Generated Password: {$password}\n";
        echo "IMPORTANT: Store this password securely immediately. It will not be shown again.\n";
    } else {
        echo "Password:        [Supplied by operator - encrypted]\n";
    }
    echo "=============================\n";

} catch (\Throwable $e) {
    echo "\n[FATAL ERROR] Provisioning failed: " . $e->getMessage() . "\n";
    exit(1);
}
