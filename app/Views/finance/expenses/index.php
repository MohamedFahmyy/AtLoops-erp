<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><?= e(__('common.expenses')) ?></h3>
        <p class="text-muted small mb-0"><?= is_rtl() ? 'تسجيل ومراقبة المصروفات التشغيلية، الخوادم، المرتبات وتكاليف المشاريع' : 'Record and audit operational expenses, cloud servers, software licenses, and project costs' ?></p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#recordExpenseModal">
            <i class="bi bi-plus-circle"></i> <?= is_rtl() ? 'تسجيل مصروف جديد' : 'Record Expense' ?>
        </button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= is_rtl() ? 'رقم الإذن' : 'Expense #' ?></th>
                    <th><?= is_rtl() ? 'التاريخ' : 'Date' ?></th>
                    <th><?= is_rtl() ? 'البند / التصنيف' : 'Category' ?></th>
                    <th><?= is_rtl() ? 'البيان والتفاصيل' : 'Description' ?></th>
                    <th><?= is_rtl() ? 'الحساب المسدد منه' : 'Account' ?></th>
                    <th><?= is_rtl() ? 'المشروع المرتبط' : 'Project' ?></th>
                    <th class="text-end"><?= is_rtl() ? 'المبلغ' : 'Amount' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenses)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-wallet2 fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <?= e(__('common.no_records')) ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($expenses as $e): ?>
                        <tr>
                            <td><code><?= e($e['expense_number']) ?></code></td>
                            <td><?= e($e['expense_date']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($e['category_name']) ?></span></td>
                            <td>
                                <span class="fw-semibold text-dark"><?= e($e['description']) ?></span>
                                <?php if (!empty($e['vendor_name'])): ?>
                                    <small class="d-block text-muted">Vendor: <?= e($e['vendor_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($e['account_name']) ?></td>
                            <td><?= !empty($e['project_name']) ? e($e['project_name']) : '<span class="text-muted">—</span>' ?></td>
                            <td class="text-end fw-bold text-danger"><?= format_money($e['amount'], $e['currency']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Record Expense -->
<div class="modal fade" id="recordExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= url('finance/expenses') ?>" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?= is_rtl() ? 'تسجيل إذن صرف / مصروف جديد' : 'Record New Operating Expense' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'تصنيف المصروف *' : 'Expense Category *' ?></label>
                    <select name="category_id" class="form-select" required>
                        <option value=""><?= e(__('common.select')) ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'الخزينة / الحساب المسدد منه *' : 'Paying Cash/Bank Account *' ?></label>
                    <select name="cash_account_id" class="form-select" required>
                        <option value=""><?= e(__('common.select')) ?></option>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>"><?= e($acc['name']) ?> (<?= format_money($acc['current_balance'], $acc['currency']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold"><?= is_rtl() ? 'قيمة المصروف *' : 'Amount *' ?></label>
                        <input type="number" step="0.01" name="amount" class="form-control" required min="0.01" placeholder="500.00">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold"><?= is_rtl() ? 'تاريخ الصرف *' : 'Expense Date *' ?></label>
                        <input type="date" name="expense_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'المشروع المرتبط (إن وجد)' : 'Linked Project (Optional)' ?></label>
                    <select name="project_id" class="form-select">
                        <option value=""><?= is_rtl() ? 'مصروف عام بالشركة' : 'General Overhead (None)' ?></option>
                        <?php foreach ($projects as $proj): ?>
                            <option value="<?= $proj['id'] ?>"><?= e($proj['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'بيان وتفاصيل المصروف *' : 'Description *' ?></label>
                    <textarea name="description" class="form-control" required rows="2" placeholder="e.g. AWS server renewal for clinic backend"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= e(__('common.cancel')) ?></button>
                <button type="submit" class="btn btn-primary fw-bold"><?= is_rtl() ? 'اعتماد وصرف' : 'Confirm & Deduct Balance' ?></button>
            </div>
        </form>
    </div>
</div>
