<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold mb-0"><?= e($project['name']) ?></h3>
            <span class="badge bg-secondary"><?= e($project['code']) ?></span>
            <span class="badge bg-primary"><?= strtoupper($project['status']) ?></span>
        </div>
        <p class="text-muted small mb-0 mt-1"><i class="bi bi-building"></i> <?= e($project['company_name']) ?> &bull; Manager: <?= e($project['manager_name']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('projects/' . $project['id'] . '/kanban') ?>" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-kanban"></i> <?= is_rtl() ? 'لوحة كانبان المهام' : 'Kanban Board' ?>
        </a>
        <button type="button" class="btn btn-success btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#logTimeModal">
            <i class="bi bi-stopwatch"></i> <?= is_rtl() ? 'تسجيل ساعات عمل' : 'Log Time' ?>
        </button>
        <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addTaskModal">
            <i class="bi bi-plus-circle"></i> <?= is_rtl() ? 'إضافة مهمة' : 'Add Task' ?>
        </button>
    </div>
</div>

<!-- Real-time Profitability Engine Metrics -->
<div class="card border-primary mb-4">
    <div class="card-header bg-primary bg-opacity-10 d-flex justify-content-between align-items-center">
        <span class="fw-bold text-primary"><i class="bi bi-calculator-fill me-2 ms-2"></i><?= is_rtl() ? 'حساب ربحية وتكاليف المشروع الفعلية (Project Profitability Engine)' : 'Real-Time Project Profitability & Cost Engine' ?></span>
        <span class="badge bg-<?= $profitability['profitability_status'] === 'healthy' ? 'success' : ($profitability['profitability_status'] === 'at_risk' ? 'warning' : 'danger') ?>">
            <?= strtoupper($profitability['profitability_status']) ?>
        </span>
    </div>
    <div class="card-body">
        <div class="row g-3 text-center">
            <div class="col-6 col-md-3 border-end">
                <small class="text-muted d-block"><?= is_rtl() ? 'الإيراد المحسوب' : 'Recognized Revenue' ?></small>
                <h4 class="fw-bold text-dark mb-0"><?= format_money($profitability['recognized_revenue'], $profitability['currency']) ?></h4>
            </div>
            <div class="col-6 col-md-3 border-end">
                <small class="text-muted d-block"><?= is_rtl() ? 'تكلفة العمالة (ساعات العمل)' : 'Labor Cost (' . $profitability['total_hours'] . ' hrs)' ?></small>
                <h4 class="fw-bold text-danger mb-0"><?= format_money($profitability['labor_cost'], $profitability['currency']) ?></h4>
            </div>
            <div class="col-6 col-md-3 border-end">
                <small class="text-muted d-block"><?= is_rtl() ? 'مصروفات مباشرة مسددة' : 'Direct Expenses' ?></small>
                <h4 class="fw-bold text-danger mb-0"><?= format_money($profitability['direct_expenses'], $profitability['currency']) ?></h4>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block"><?= is_rtl() ? 'مجمل الربح وهامش الربحية' : 'Gross Margin & Margin %' ?></small>
                <h4 class="fw-bold text-success mb-0"><?= format_money($profitability['gross_margin_amount'], $profitability['currency']) ?></h4>
                <span class="badge bg-success" style="font-size: 11px;"><?= $profitability['gross_margin_percent'] ?>%</span>
            </div>
        </div>
    </div>
</div>

<!-- Tasks Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-check2-square text-primary me-2 ms-2"></i><?= is_rtl() ? 'قائمة مهام المشروع' : 'Project Tasks' ?></span>
        <span class="badge bg-secondary"><?= count($tasks) ?> <?= is_rtl() ? 'مهمة' : 'tasks' ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th><?= is_rtl() ? 'عنوان المهمة' : 'Task Title' ?></th>
                    <th><?= is_rtl() ? 'المسؤول' : 'Assignee' ?></th>
                    <th><?= is_rtl() ? 'الأولوية' : 'Priority' ?></th>
                    <th><?= e(__('common.status')) ?></th>
                    <th><?= is_rtl() ? 'تاريخ الاستحقاق' : 'Due Date' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted"><?= e(__('common.no_records')) ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasks as $t): ?>
                        <tr>
                            <td><?= $t['id'] ?></td>
                            <td>
                                <span class="fw-semibold text-dark"><?= e($t['title']) ?></span>
                                <?php if (!empty($t['description'])): ?>
                                    <small class="d-block text-muted"><?= e(substr($t['description'], 0, 70)) ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($t['assigned_to_name'] ?? 'Unassigned') ?></td>
                            <td>
                                <span class="badge bg-<?= $t['priority'] === 'urgent' ? 'danger' : ($t['priority'] === 'high' ? 'warning' : 'secondary') ?>">
                                    <?= strtoupper($t['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $t['status'] === 'done' ? 'success' : 'primary' ?>">
                                    <?= strtoupper($t['status']) ?>
                                </span>
                            </td>
                            <td><?= e($t['due_date'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Log Time -->
<div class="modal fade" id="logTimeModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= url('projects/' . $project['id'] . '/time') ?>" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?= is_rtl() ? 'تسجيل ساعات عمل للمطور' : 'Log Work Time' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?= is_rtl() ? 'المهمة المرتبطة' : 'Task' ?></label>
                    <select name="task_id" class="form-select">
                        <option value=""><?= is_rtl() ? 'عام على المشروع' : 'General Project Work' ?></option>
                        <?php foreach ($tasks as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= e($t['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= is_rtl() ? 'المدة بالدقائق *' : 'Duration in Minutes *' ?></label>
                    <input type="number" name="duration_minutes" class="form-control" required placeholder="120" min="5" value="60">
                    <small class="text-muted"><?= is_rtl() ? 'مثال: 60 دقيقة = ساعة واحدة، 120 دقيقة = ساعتان' : 'e.g. 60 = 1 hr, 120 = 2 hrs' ?></small>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= is_rtl() ? 'تاريخ العمل *' : 'Date *' ?></label>
                    <input type="date" name="log_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= e(__('common.notes')) ?></label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="<?= is_rtl() ? 'ما الذي تم إنجازه؟' : 'What was delivered?' ?>"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= e(__('common.cancel')) ?></button>
                <button type="submit" class="btn btn-success fw-bold"><?= is_rtl() ? 'حفظ وحساب التكلفة' : 'Log Time & Compute Cost' ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Task -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= url('projects/' . $project['id'] . '/tasks') ?>" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?= is_rtl() ? 'إضافة مهمة جديدة' : 'Add New Task' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'عنوان المهمة *' : 'Task Title *' ?></label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Implement payment webhook">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold"><?= is_rtl() ? 'الأولوية *' : 'Priority *' ?></label>
                        <select name="priority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold"><?= is_rtl() ? 'الحالة الأولية *' : 'Status *' ?></label>
                        <select name="status" class="form-select">
                            <option value="todo" selected>To Do</option>
                            <option value="in_progress">In Progress</option>
                            <option value="in_review">In Review</option>
                            <option value="backlog">Backlog</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'تاريخ الاستحقاق' : 'Due Date' ?></label>
                    <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= e(__('common.description')) ?></label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= e(__('common.cancel')) ?></button>
                <button type="submit" class="btn btn-primary fw-bold"><?= e(__('common.save')) ?></button>
            </div>
        </form>
    </div>
</div>
