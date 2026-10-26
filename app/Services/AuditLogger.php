<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use PDO;

class AuditLogger
{
    /**
     * Log an auditable action into the append-only audit trail.
     * Secrets (passwords, tokens) must never be passed or stored.
     */
    public static function log(
        string $action,
        string $entityType,
        ?int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        try {
            $userId = Auth::id();
            $ip = $_SERVER['REMOTE_ADDR'] ?? (php_sapi_name() === 'cli' ? '127.0.0.1' : null);
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? (php_sapi_name() === 'cli' ? 'CLI Script' : ''), 0, 255);

            // Strip sensitive fields
            $cleanOld = self::sanitize($oldValues);
            $cleanNew = self::sanitize($newValues);

            $pdo = Database::pdo();
            $stmt = $pdo->prepare("
                INSERT INTO `audit_logs` (`user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip_address`, `user_agent`)
                VALUES (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent)
            ");

            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'old_values' => $cleanOld ? json_encode($cleanOld, JSON_UNESCAPED_UNICODE) : null,
                'new_values' => $cleanNew ? json_encode($cleanNew, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);
        } catch (\Throwable $e) {
            // Fail safely without disrupting the main business transaction
            error_log("AuditLogger failed: " . $e->getMessage());
        }
    }

    private static function sanitize(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $sensitive = ['password', 'password_confirmation', '_csrf_token', 'token', 'secret'];
        foreach ($sensitive as $key) {
            unset($data[$key]);
        }

        return $data;
    }
}
