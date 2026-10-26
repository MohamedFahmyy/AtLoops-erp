<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold mb-0"><?= e($account['name']) ?></h3>
            <span class="badge bg-secondary"><?= strtoupper($account['currency']) ?></span>
            <span class="badge bg-info text-dark"><?= strtoupper($account['account_type']) ?></span>
        </div>
        <p class="text-muted small mb-0 mt-1"><?= e($account['bank_name'] ?? 'Physical Cash') ?> &bull; Account No: <?= e($account['account_number'] ?? '—') ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('finance/accounts') ?>" class="btn btn-outline-secondary btn-sm"><?= e(__('common.back')) ?></a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-4">
        <div class="card p-3 bg-light border">
            <small class="text-muted d-block"><?= is_rtl() ? 'رصيد أول المدة (Opening Balance)' : 'Opening Balance' ?></small>
            <h4 class="fw-bold text-dark mb-0"><?= format_money($account['opening_balance'], $account['currency']) ?></h4>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card p-3 bg-primary bg-opacity-10 border-primary border-opacity-25">
            <small class="text-primary d-block fw-bold"><?= is_rtl() ? 'الرصيد الفعلي الحالي' : 'Current Reconciled Balance' ?></small>
            <h4 class="fw-bold text-primary mb-0"><?= format_money($account['current_balance'], $account['currency']) ?></h4>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-clock-history text-primary me-2 ms-2"></i><?= is_rtl() ? 'كشف الحساب وسجل حركات الخزينة / البنك' : 'Detailed Transaction Ledger' ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th><?= is_rtl() ? 'التاريخ والوقت' : 'Date & Time' ?></th>
                    <th><?= is_rtl() ? 'نوع الحركة' : 'Type' ?></th>
                    <th><?= is_rtl() ? 'المرجع والبيان' : 'Reference & Description' ?></th>
                    <th class="text-end"><?= is_rtl() ? 'المبلغ' : 'Amount' ?></th>
                    <th class="text-end"><?= is_rtl() ? 'الرصيد بعد الحركة' : 'Balance After' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted"><?= e(__('common.no_records')) ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td><?= $tx['id'] ?></td>
                            <td><?= e($tx['transaction_date']) ?></td>
                            <td>
                                <?php if ($tx['type'] === 'deposit' || $tx['type'] === 'transfer_in'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-arrow-down-left"></i> <?= strtoupper($tx['type']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                        <i class="bi bi-arrow-up-right"></i> <?= strtoupper($tx['type']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark"><?= e($tx['description']) ?></span>
                                <?php if (!empty($tx['transfer_link_id'])): ?>
                                    <small class="d-block text-muted">Transfer Ref: <code><?= e($tx['transfer_link_id']) ?></code></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold <?= in_array($tx['type'], ['deposit', 'transfer_in'], true) ? 'text-success' : 'text-danger' ?>">
                                <?= in_array($tx['type'], ['deposit', 'transfer_in'], true) ? '+' : '-' ?><?= format_money($tx['amount'], $tx['currency']) ?>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                <?= format_money($tx['balance_after'], $tx['currency']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
