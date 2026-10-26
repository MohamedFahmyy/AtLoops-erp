<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><?= e(__('common.recurring')) ?></h3>
        <p class="text-muted small mb-0"><?= is_rtl() ? 'عقود الصيانة المتكررة، الاستضافات، وعقود الدعم الفني الشهرية (MRR)' : 'Manage recurring maintenance retainers, cloud hosting, and monthly support contracts' ?></p>
    </div>
    <div class="d-flex gap-2">
        <form action="<?= url('recurring/process') ?>" method="POST" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-gear-wide-connected"></i> <?= is_rtl() ? 'توليد مسودات فواتير الاستحقاق يدوياً' : 'Generate Due Invoice Drafts' ?>
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th><?= is_rtl() ? 'الخدمة والعميل' : 'Service & Client' ?></th>
                    <th><?= is_rtl() ? 'نوع الخدمة' : 'Service Type' ?></th>
                    <th><?= is_rtl() ? 'الاشتراك الشهري' : 'Fee' ?></th>
                    <th><?= is_rtl() ? 'الدورة' : 'Billing Frequency' ?></th>
                    <th><?= is_rtl() ? 'موعد الفاتورة القادمة' : 'Next Billing Date' ?></th>
                    <th><?= e(__('common.status')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contracts)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted"><?= e(__('common.no_records')) ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contracts as $rc): ?>
                        <tr>
                            <td><?= $rc['id'] ?></td>
                            <td>
                                <span class="fw-bold text-dark"><?= e($rc['title']) ?></span>
                                <small class="d-block text-muted"><i class="bi bi-building"></i> <?= e($rc['company_name']) ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($rc['service_type']) ?></span></td>
                            <td class="fw-bold text-primary"><?= format_money($rc['monthly_fee'], $rc['currency']) ?></td>
                            <td><?= strtoupper($rc['billing_frequency']) ?></td>
                            <td>
                                <b><?= e($rc['next_billing_date']) ?></b>
                                <?php if (strtotime($rc['next_billing_date']) <= strtotime(date('Y-m-d'))): ?>
                                    <span class="badge bg-warning text-dark ms-1 me-1"><?= is_rtl() ? 'مستحقة الفاتورة' : 'Due' ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle"><?= strtoupper($rc['status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
