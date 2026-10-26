<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Response;
use App\Repositories\CompanyRepository;
use App\Repositories\ProjectRepository;
use App\Services\ProjectProfitabilityService;

class ProjectController extends Controller
{
    private ProjectRepository $projectRepo;
    private CompanyRepository $companyRepo;

    public function __construct()
    {
        $this->projectRepo = new ProjectRepository();
        $this->companyRepo = new CompanyRepository();
    }

    public function index(): void
    {
        $this->requirePermission('projects.view');

        $status = $_GET['status'] ?? null;
        $projects = $this->projectRepo->getAll($status);

        $this->render('projects/index', [
            'pageTitle' => __('common.projects'),
            'projects' => $projects,
            'selectedStatus' => $status,
        ]);
    }

    public function show(string $id): void
    {
        $this->requirePermission('projects.view');

        $project = $this->projectRepo->find((int)$id);
        if (!$project) {
            $this->redirect(url('projects'), ['error' => 'Project not found.']);
        }

        // Horizontal Privilege / Resource-Level Authorization Check
        $currentUser = \App\Core\Auth::user();
        if ($currentUser && !\App\Policies\ProjectPolicy::canView($currentUser, $project, \App\Core\Database::pdo())) {
            $this->forbidden('Access denied: You are not authorized to view this restricted project.');
        }

        $tasks = $this->projectRepo->getTasks((int)$id);
        $profitability = ProjectProfitabilityService::calculateProfitability((int)$id);

        $this->render('projects/show', [
            'pageTitle' => $project['name'],
            'project' => $project,
            'tasks' => $tasks,
            'profitability' => $profitability,
        ]);
    }

    public function kanban(string $id): void
    {
        $this->requirePermission('projects.view');

        $project = $this->projectRepo->find((int)$id);
        if (!$project) {
            $this->redirect(url('projects'), ['error' => 'Project not found.']);
        }

        // Horizontal Privilege / Resource-Level Authorization Check
        $currentUser = \App\Core\Auth::user();
        if ($currentUser && !\App\Policies\ProjectPolicy::canView($currentUser, $project, \App\Core\Database::pdo())) {
            $this->forbidden('Access denied: You are not authorized to view this project.');
        }

        $tasks = $this->projectRepo->getTasks((int)$id);

        $statuses = [
            'backlog' => 'Backlog',
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'in_review' => 'In Review',
            'blocked' => 'Blocked',
            'done' => 'Done',
        ];

        $tasksByStatus = [];
        foreach ($statuses as $k => $v) {
            $tasksByStatus[$k] = [];
        }

        foreach ($tasks as $t) {
            $tasksByStatus[$t['status']][] = $t;
        }

        $this->render('projects/kanban', [
            'pageTitle' => $project['name'] . ' - ' . __('common.kanban'),
            'project' => $project,
            'tasksByStatus' => $tasksByStatus,
            'statuses' => $statuses,
        ]);
    }

    public function updateTaskStatus(string $taskId): void
    {
        $this->requirePermission('projects.manage_tasks');

        $status = $_POST['status'] ?? '';
        $validStatuses = ['backlog', 'todo', 'in_progress', 'in_review', 'blocked', 'done'];

        if (!in_array($status, $validStatuses, true)) {
            Response::json(['error' => 'Invalid task status'], 422);
        }

        $this->projectRepo->updateTaskStatus((int)$taskId, $status);
        Response::json(['success' => true, 'status' => $status]);
    }

    public function storeTask(string $projectId): void
    {
        $this->requirePermission('projects.manage_tasks');

        $data = $this->validate($_POST, [
            'title' => 'required|min:3|max:200',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:backlog,todo,in_progress,in_review,blocked,done',
        ]);

        $data['project_id'] = (int)$projectId;
        $data['created_by'] = Auth::id() ?? 1;

        $this->projectRepo->createTask($data);

        $this->redirectBack(['success' => 'Task added successfully.']);
    }

    public function logTime(string $projectId): void
    {
        $this->requirePermission('projects.log_time');

        $data = $this->validate($_POST, [
            'duration_minutes' => 'required|integer|min:5',
            'log_date' => 'required|date',
        ]);

        $user = Auth::user();
        $hourlyCost = (float)($user['hourly_cost'] ?? 100.00);

        $this->projectRepo->logTime([
            'project_id' => (int)$projectId,
            'task_id' => !empty($_POST['task_id']) ? (int)$_POST['task_id'] : null,
            'user_id' => (int)$user['id'],
            'duration_minutes' => (int)$data['duration_minutes'],
            'hourly_cost' => $hourlyCost,
            'is_billable' => !empty($_POST['is_billable']) ? 1 : 0,
            'log_date' => $data['log_date'],
            'notes' => $_POST['notes'] ?? null,
        ]);

        $this->redirectBack(['success' => 'Time logged and labor cost calculated successfully.']);
    }
}
