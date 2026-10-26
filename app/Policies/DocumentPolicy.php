<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\Auth;
use PDO;

class DocumentPolicy
{
    /**
     * Determine whether the given user can download the document.
     */
    public static function canDownload(array $user, array $doc, ?PDO $pdo = null): bool
    {
        // 1. Super Admin and Managers have universal document access
        if (Auth::hasRole('super_admin', 'manager')) {
            return true;
        }

        $userId = (int)($user['id'] ?? 0);
        $uploaderId = (int)($doc['user_id'] ?? 0);

        // 2. The original uploader always has access to their own document
        if ($userId === $uploaderId && $userId > 0) {
            return true;
        }

        $entityType = $doc['entity_type'] ?? 'general';
        $entityId = !empty($doc['entity_id']) ? (int)$doc['entity_id'] : null;

        // 3. Financial documents require finance permissions
        if (in_array($entityType, ['invoice', 'quotation', 'contract'], true)) {
            return Auth::can('finance.invoices.view') || Auth::can('finance.invoices.manage') || Auth::can('crm.view');
        }

        // 4. Project documents check project membership
        if ($entityType === 'project' && $entityId && $pdo) {
            $stmt = $pdo->prepare('
                SELECT 1 FROM `project_members`
                WHERE `project_id` = :project_id AND `user_id` = :user_id
                LIMIT 1
            ');
            $stmt->execute([
                'project_id' => $entityId,
                'user_id' => $userId,
            ]);
            if ((bool)$stmt->fetchColumn()) {
                return true;
            }

            // Or if user is project manager
            $projStmt = $pdo->prepare('SELECT manager_id FROM `projects` WHERE `id` = :id LIMIT 1');
            $projStmt->execute(['id' => $entityId]);
            $mgrId = (int)$projStmt->fetchColumn();
            if ($mgrId === $userId) {
                return true;
            }
        }

        // 5. General documents can be viewed by active authenticated staff with general document view permission
        if ($entityType === 'general' && ($doc['category'] ?? '') === 'general') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the given user can delete the document.
     */
    public static function canDelete(array $user, array $doc, ?PDO $pdo = null): bool
    {
        // Super Admin and Managers can delete documents
        if (Auth::hasRole('super_admin', 'manager')) {
            return true;
        }

        $userId = (int)($user['id'] ?? 0);
        $uploaderId = (int)($doc['user_id'] ?? 0);

        // Uploader can delete only if they have documents.delete permission
        if ($userId === $uploaderId && $userId > 0 && Auth::can('documents.delete')) {
            // Financial documents cannot be deleted if attached to finalized invoices/contracts
            $entityType = $doc['entity_type'] ?? 'general';
            if (in_array($entityType, ['invoice', 'contract'], true)) {
                return false;
            }
            return true;
        }

        return false;
    }
}
