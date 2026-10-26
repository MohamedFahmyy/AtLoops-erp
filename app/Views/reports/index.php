<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><?= e(__('common.reports')) ?></h3>
        <p class="text-muted small mb-0"><?= is_rtl() ? 'تقارير الأداء المالي، سجل التحصيلات المعتمدة، والمستحقات الآجلة' : 'Comprehensive financial operating reports, verified collections, and aged receivables' ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url("reports/collections-csv?currency={$selectedCurrency}&start_date={$startDate}&end_date={$endDate}") ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-download"></i> <?= is_rtl() ? 'تصدير التحصيلات CSV' : 'Export Collections CSV' ?>
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form action="<?= url('reports') ?>" method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1"><?= e(__('common.currency')) ?></label>
                <select name="currency" class="form-select form-select-sm">
                    <option value="EGP" <?= $selectedCurrency === 'EGP' ? 'selected' : '' ?>>EGP (جنيه)</option>
                    <option value="SAR" <?= $selectedCurrency === 'SAR' ? 'selected' : '' ?>>SAR (ريال)</option>
                    <option value="USD" <?= $selectedCurrency === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1"><?= is_rtl() ? 'من تاريخ' : 'Start Date' ?></label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1"><?= is_rtl() ? 'إلى تاريخ' : 'End Date' ?></label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel"></i> <?= e(__('common.filter')) ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Metrics in Selected Period -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card p-3 border-success border-opacity-25 bg-success bg-opacity-10">
            <small class="text-success fw-bold text-uppercase"><?= is_rtl() ? 'التحصيلات في الفترة' : 'Period Collections' ?></small>
            <h3 class="fw-bold text-success mb-0"><?= format_money($totalCollected, $selectedCurrency) ?></h3>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card p-3 border-danger border-opacity-25 bg-danger bg-opacity-10">
            <small class="text-danger fw-bold text-uppercase"><?= is_rtl() ? 'المصروفات في الفترة' : 'Period Expenses' ?></small>
            <h3 class="fw-bold text-danger mb-0"><?= format_money($totalExpenses, $selectedCurrency) ?></h3>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card p-3 border-primary border-opacity-25 bg-primary bg-opacity-10">
            <small class="text-primary fw-bold text-uppercase"><?= is_rtl() ? 'صافي السيولة النقدية' : 'Net Cash Flow' ?></small>
            <h3 class="fw-bold <?= $netCashFlow >= 0 ? 'text-primary' : 'text-danger' ?> mb-0"><?= format_money($netCashFlow, $selectedCurrency) ?></h3>
        </div>
    </div>
</div>

<!-- Aged Receivables Table -->
<div class="card mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-hourglass-split text-warning me-2 ms-2"></i><?= is_rtl() ? 'تقرير أعمار الديون والمستحقات (Aged Receivables)' : 'Aged Receivables Schedule' ?></span>
        <span class="badge bg-danger fs-6"><?= format_money($totalReceivables, $selectedCurrency) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= is_rtl() ? 'رقم الفاتورة' : 'Invoice #' ?></th>
                    <th><?= is_rtl() ? 'العميل' : 'Client' ?></th>
                    <th><?= is_rtl() ? 'تاريخ الاستحقاق' : 'Due Date' ?></th>
                    <th><?= is_rtl() ? 'أيام التأخير' : 'Days Overdue' ?></th>
                    <th><?= is_rtl() ? 'إجمالي الفاتورة' : 'Total' ?></th>
                    <th><?= is_rtl() ? 'المسدد' : 'Paid' ?></th>
                    <th class="text-end"><?= is_rtl() ? 'المستحق' : 'Outstanding' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($receivables)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted"><?= is_rtl() ? 'لا توجد فواتير مستحقة متأخرة.' : 'No outstanding receivables found.' ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($receivables as $r): ?>
                        <tr>
                            <td><code><?= e($r['invoice_number']) ?></code></td>
                            <td><b><?= e($r['company_name']) ?></b></td>
                            <td><?= e($r['due_date']) ?></td>
                            <td>
                                <?php if ($r['days_overdue'] > 0): ?>
                                    <span class="badge bg-danger"><?= $r['days_overdue'] ?> <?= is_rtl() ? 'يوم تأخير' : 'days late' ?></span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark"><?= is_rtl() ? 'لم يحن' : 'Not due yet' ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= format_money($r['total_amount'], $r['currency']) ?></td>
                            <td class="text-success"><?= format_money($r['paid_amount'], $r['currency']) ?></td>
                            <td class="text-end fw-bold text-danger"><?= format_money($r['outstanding_amount'], $r['currency']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
