<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold mb-0"><?= e($project['name']) ?></h3>
            <span class="badge bg-secondary"><?= e(__('common.kanban')) ?></span>
        </div>
        <p class="text-muted small mb-0 mt-1"><?= is_rtl() ? 'لوحة كانبان التفاعلية لإدارة تدفق العمل وسحب المهام' : 'Interactive task workflow board with server-side validation' ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('projects/' . $project['id']) ?>" class="btn btn-outline-secondary btn-sm"><?= e(__('common.back')) ?></a>
    </div>
</div>

<div class="row g-3 flex-nowrap overflow-auto pb-4" style="min-height: 540px;">
    <?php foreach ($statuses as $statusKey => $statusLabel): ?>
        <?php $list = $tasksByStatus[$statusKey] ?? []; ?>
        <div class="col-12 col-md-6 col-xl-2" style="min-width: 250px; max-width: 280px;">
            <div class="card h-100 bg-light-subtle border">
                <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-6 text-uppercase"><?= $statusLabel ?></span>
                    <span class="badge bg-primary"><?= count($list) ?></span>
                </div>
                <div class="card-body p-2 d-flex flex-column gap-2">
                    <?php if (empty($list)): ?>
                        <div class="text-center py-4 text-muted small opacity-50"><?= is_rtl() ? 'فارغة' : 'Empty' ?></div>
                    <?php else: ?>
                        <?php foreach ($list as $task): ?>
                            <div class="card shadow-sm border mb-1">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <span class="badge bg-<?= $task['priority'] === 'urgent' ? 'danger' : ($task['priority'] === 'high' ? 'warning' : 'secondary') ?>" style="font-size: 10px;">
                                            <?= strtoupper($task['priority']) ?>
                                        </span>
                                        <small class="text-muted" style="font-size: 10px;"><?= e($task['due_date'] ?? '') ?></small>
                                    </div>
                                    <h6 class="fw-bold mb-1 small text-dark"><?= e($task['title']) ?></h6>
                                    <div class="d-flex justify-content-between align-items-center mt-2 pt-1 border-top">
                                        <small class="text-muted"><i class="bi bi-person"></i> <?= e($task['assigned_to_name'] ?? 'None') ?></small>

                                        <!-- Quick Status Changer -->
                                        <select class="form-select form-select-sm task-status-selector" data-task-id="<?= $task['id'] ?>" style="font-size: 10px; width: auto; padding: 2px 4px;">
                                            <?php foreach ($statuses as $sk => $sl): ?>
                                                <option value="<?= $sk ?>" <?= $sk === $statusKey ? 'selected' : '' ?>><?= $sl ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.task-status-selector').forEach(sel => {
        sel.addEventListener('change', async function () {
            const taskId = this.getAttribute('data-task-id');
            const newStatus = this.value;

            try {
                const response = await fetch(`<?= url('projects/tasks/') ?>${taskId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: `status=${encodeURIComponent(newStatus)}&_csrf_token=${encodeURIComponent(getCsrfToken())}`
                });

                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || 'Failed to update task status');
                }
            } catch (err) {
                alert('Connection error');
            }
        });
    });
});
</script>
