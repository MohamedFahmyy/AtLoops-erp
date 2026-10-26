<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class ProjectRepository
{
    private PDO $pdo;

    public function __construct(?string $connection = null)
    {
        $this->pdo = Database::pdo($connection);
    }

    public function getAll(?string $status = null): array
    {
        $sql = "
            SELECT p.*,
                c.name AS company_name,
                u.name AS manager_name,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) AS total_tasks,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'done') AS completed_tasks,
                (SELECT COALESCE(SUM(duration_minutes), 0) FROM time_entries WHERE project_id = p.id) AS total_minutes_logged
            FROM `projects` p
            JOIN `companies` c ON c.id = p.company_id
            JOIN `users` u ON u.id = p.manager_id
            WHERE 1=1
        ";
        $params = [];

        if ($status) {
            $sql .= " AND p.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY p.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*,
                c.name AS company_name,
                c.email AS company_email,
                c.phone AS company_phone,
                u.name AS manager_name
            FROM `projects` p
            JOIN `companies` c ON c.id = p.company_id
            JOIN `users` u ON u.id = p.manager_id
            WHERE p.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getTasks(int $projectId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT t.*,
                u.name AS assigned_to_name,
                creator.name AS creator_name,
                m.title AS milestone_title
            FROM `tasks` t
            LEFT JOIN `users` u ON u.id = t.assigned_to
            JOIN `users` creator ON creator.id = t.created_by
            LEFT JOIN `milestones` m ON m.id = t.milestone_id
            WHERE t.project_id = :id
            ORDER BY t.priority DESC, t.id ASC
        ");
        $stmt->execute(['id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createTask(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO `tasks` (
                `project_id`, `milestone_id`, `assigned_to`, `created_by`,
                `title`, `description`, `priority`, `status`, `estimated_hours`, `due_date`
            ) VALUES (
                :project_id, :milestone_id, :assigned_to, :created_by,
                :title, :description, :priority, :status, :estimated_hours, :due_date
            )
        ");

        $stmt->execute([
            'project_id' => (int)$data['project_id'],
            'milestone_id' => !empty($data['milestone_id']) ? (int)$data['milestone_id'] : null,
            'assigned_to' => !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null,
            'created_by' => (int)($data['created_by'] ?? 1),
            'title' => trim((string)$data['title']),
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'status' => $data['status'] ?? 'todo',
            'estimated_hours' => (float)($data['estimated_hours'] ?? 0.00),
            'due_date' => !empty($data['due_date']) ? $data['due_date'] : null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateTaskStatus(int $taskId, string $status): bool
    {
        $completedAt = ($status === 'done') ? date('Y-m-d H:i:s') : null;
        $stmt = $this->pdo->prepare("
            UPDATE `tasks`
            SET `status` = :status, `completed_at` = :completed_at, `updated_at` = NOW()
            WHERE `id` = :id
        ");
        return $stmt->execute([
            'status' => $status,
            'completed_at' => $completedAt,
            'id' => $taskId,
        ]);
    }

    public function logTime(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO `time_entries` (
                `task_id`, `project_id`, `user_id`, `duration_minutes`,
                `hourly_cost`, `total_cost`, `is_billable`, `log_date`, `notes`
            ) VALUES (
                :task_id, :project_id, :user_id, :duration_minutes,
                :hourly_cost, :total_cost, :is_billable, :log_date, :notes
            )
        ");

        $durationMinutes = (int)($data['duration_minutes'] ?? 0);
        $hourlyCost = (float)($data['hourly_cost'] ?? 0.00);
        $totalCost = round(($durationMinutes / 60) * $hourlyCost, 2);

        $stmt->execute([
            'task_id' => !empty($data['task_id']) ? (int)$data['task_id'] : null,
            'project_id' => (int)$data['project_id'],
            'user_id' => (int)$data['user_id'],
            'duration_minutes' => $durationMinutes,
            'hourly_cost' => $hourlyCost,
            'total_cost' => $totalCost,
            'is_billable' => !empty($data['is_billable']) ? 1 : 0,
            'log_date' => $data['log_date'] ?? date('Y-m-d'),
            'notes' => $data['notes'] ?? null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
