<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\Auth;
use PDO;

class ProjectPolicy
{
    /**
     * Determine whether the user can view the specified project.
     */
    public static function canView(array $user, array $project, ?PDO $pdo = null): bool
    {
        // 1. Super Admins and Managers have universal project visibility
        if (Auth::hasRole('super_admin', 'manager')) {
            return true;
        }

        $userId = (int)($user['id'] ?? 0);
        $managerId = (int)($project['manager_id'] ?? 0);

        // 2. Project Manager
        if ($userId === $managerId && $userId > 0) {
            return true;
        }

        // 3. Project Member check
        $projectId = (int)($project['id'] ?? 0);
        if ($pdo && $projectId > 0 && $userId > 0) {
            $stmt = $pdo->prepare('
                SELECT 1 FROM `project_members`
                WHERE `project_id` = :project_id AND `user_id` = :user_id
                LIMIT 1
            ');
            $stmt->execute([
                'project_id' => $projectId,
                'user_id' => $userId,
            ]);
            if ((bool)$stmt->fetchColumn()) {
                return true;
            }
        }

        // 4. Fallback to general permission if user role allows broad project viewing
        return Auth::can('projects.view') && Auth::hasRole('manager', 'super_admin');
    }

    /**
     * Determine whether the user can manage project settings/members.
     */
    public static function canManage(array $user, array $project): bool
    {
        if (Auth::hasRole('super_admin', 'manager')) {
            return true;
        }

        $userId = (int)($user['id'] ?? 0);
        $managerId = (int)($project['manager_id'] ?? 0);

        return $userId === $managerId && $userId > 0 && Auth::can('projects.edit');
    }
}
