<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><?= e(__('common.projects')) ?></h3>
        <p class="text-muted small mb-0"><?= is_rtl() ? 'متابعة تنفيذ المشاريع التقنية، ميزانياتها وتكاليف العمل وساعات المطورين' : 'Track technical project execution, budgets, delivery milestones, and developer hours' ?></p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= is_rtl() ? 'كود المشروع' : 'Project Code' ?></th>
                    <th><?= is_rtl() ? 'اسم المشروع والعميل' : 'Project & Client' ?></th>
                    <th><?= is_rtl() ? 'مدير المشروع' : 'Manager' ?></th>
                    <th><?= is_rtl() ? 'الميزانية' : 'Budget' ?></th>
                    <th><?= is_rtl() ? 'المهام والتقدم' : 'Tasks Progress' ?></th>
                    <th><?= is_rtl() ? 'الساعات المسجلة' : 'Logged Hours' ?></th>
                    <th><?= e(__('common.status')) ?></th>
                    <th class="text-end"><?= e(__('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-kanban fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <?= e(__('common.no_records')) ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($projects as $p): ?>
                        <?php
                            $tasksTotal = (int)($p['total_tasks'] ?? 0);
                            $tasksDone = (int)($p['completed_tasks'] ?? 0);
                            $progressPct = $tasksTotal > 0 ? round(($tasksDone / $tasksTotal) * 100) : 0;
                            $hours = round(($p['total_minutes_logged'] ?? 0) / 60, 1);
                        ?>
                        <tr>
                            <td><code><?= e($p['code']) ?></code></td>
                            <td>
                                <a href="<?= url('projects/' . $p['id']) ?>" class="fw-bold text-decoration-none">
                                    <?= e($p['name']) ?>
                                </a>
                                <span class="d-block text-muted small"><i class="bi bi-building"></i> <?= e($p['company_name']) ?></span>
                            </td>
                            <td><?= e($p['manager_name']) ?></td>
                            <td><span class="fw-bold"><?= format_money($p['budget_amount'], $p['currency']) ?></span></td>
                            <td style="min-width: 140px;">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span><?= $tasksDone ?> / <?= $tasksTotal ?></span>
                                    <span><?= $progressPct ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: <?= $progressPct ?>%"></div>
                                </div>
                            </td>
                            <td><b><?= $hours ?></b> hrs</td>
                            <td>
                                <?php if ($p['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><?= is_rtl() ? 'نشط' : 'Active' ?></span>
                                <?php elseif ($p['status'] === 'planning'): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle"><?= is_rtl() ? 'تخطيط' : 'Planning' ?></span>
                                <?php elseif ($p['status'] === 'completed'): ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><?= is_rtl() ? 'مكتمل' : 'Completed' ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?= e($p['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('projects/' . $p['id']) ?>" class="btn btn-outline-primary" title="Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= url('projects/' . $p['id'] . '/kanban') ?>" class="btn btn-outline-secondary" title="Kanban Board">
                                        <i class="bi bi-kanban"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
