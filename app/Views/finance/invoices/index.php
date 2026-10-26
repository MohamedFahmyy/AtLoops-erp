<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><?= e(__('common.invoices')) ?></h3>
        <p class="text-muted small mb-0"><?= is_rtl() ? 'إصدار الفواتير الضريبية، متابعة التحصيلات، والمبالغ المستحقة' : 'Manage invoices, payment allocations, outstanding balances, and collections' ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('finance/invoices/create') ?>" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-plus-circle"></i> <?= is_rtl() ? 'إصدار فاتورة جديدة' : 'Create Invoice' ?>
        </a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= is_rtl() ? 'رقم الفاتورة' : 'Invoice #' ?></th>
                    <th><?= is_rtl() ? 'العميل والمشروع' : 'Client & Project' ?></th>
                    <th><?= is_rtl() ? 'تاريخ الإصدار' : 'Issue Date' ?></th>
                    <th><?= is_rtl() ? 'تاريخ الاستحقاق' : 'Due Date' ?></th>
                    <th><?= is_rtl() ? 'الإجمالي' : 'Total' ?></th>
                    <th><?= is_rtl() ? 'المحصل' : 'Paid' ?></th>
                    <th><?= is_rtl() ? 'المتبقي' : 'Outstanding' ?></th>
                    <th><?= e(__('common.status')) ?></th>
                    <th class="text-end"><?= e(__('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt-cutoff fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <?= e(__('common.no_records')) ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td><code><?= e($inv['invoice_number']) ?></code></td>
                            <td>
                                <a href="<?= url('finance/invoices/' . $inv['id']) ?>" class="fw-bold text-decoration-none">
                                    <?= e($inv['company_name']) ?>
                                </a>
                                <?php if (!empty($inv['project_name'])): ?>
                                    <small class="d-block text-muted"><?= e($inv['project_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($inv['issue_date']) ?></td>
                            <td><?= e($inv['due_date']) ?></td>
                            <td><b><?= format_money($inv['total_amount'], $inv['currency']) ?></b></td>
                            <td class="text-success"><?= format_money($inv['paid_amount'], $inv['currency']) ?></td>
                            <td class="fw-bold <?= $inv['outstanding_amount'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                <?= format_money($inv['outstanding_amount'], $inv['currency']) ?>
                            </td>
                            <td>
                                <?php if ($inv['status'] === 'paid'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><?= is_rtl() ? 'مسددة بالكامل' : 'Paid' ?></span>
                                <?php elseif ($inv['status'] === 'partially_paid'): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?= is_rtl() ? 'مسددة جزئياً' : 'Partially Paid' ?></span>
                                <?php elseif ($inv['status'] === 'overdue'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= is_rtl() ? 'متأخرة' : 'Overdue' ?></span>
                                <?php elseif ($inv['status'] === 'void'): ?>
                                    <span class="badge bg-secondary text-white"><?= is_rtl() ? 'ملغاة (Void)' : 'Void' ?></span>
                                <?php else: ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle"><?= is_rtl() ? 'غير مسددة' : 'Unpaid' ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('finance/invoices/' . $inv['id']) ?>" class="btn btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= url('finance/invoices/' . $inv['id'] . '/pdf') ?>" target="_blank" class="btn btn-outline-secondary" title="Download PDF">
                                        <i class="bi bi-file-earmark-pdf"></i>
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
