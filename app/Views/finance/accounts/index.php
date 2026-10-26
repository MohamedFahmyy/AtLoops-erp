<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><?= e(__('common.cash_accounts')) ?></h3>
        <p class="text-muted small mb-0"><?= is_rtl() ? 'أرصدة الحسابات البنكية، الخزائن النقدية، ومحافظ الدفع الإلكتروني' : 'Manage corporate bank accounts, physical cash drawers, and inter-account transfers' ?></p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#transferModal">
            <i class="bi bi-arrow-left-right"></i> <?= is_rtl() ? 'تحويل مالي بين الحسابات' : 'Internal Transfer' ?>
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    <?php foreach ($accounts as $acc): ?>
        <div class="col-12 col-md-4">
            <div class="card h-100 shadow-sm border">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="avatar-lg bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 22px;">
                            <i class="bi <?= $acc['account_type'] === 'bank' ? 'bi-bank2' : ($acc['account_type'] === 'petty_cash' ? 'bi-cash-stack' : 'bi-phone') ?>"></i>
                        </div>
                        <span class="badge bg-light text-dark border"><?= strtoupper($acc['currency']) ?></span>
                    </div>

                    <h5 class="fw-bold mb-1"><?= e($acc['name']) ?></h5>
                    <p class="text-muted small mb-3"><?= e($acc['bank_name'] ?? 'Physical Cash') ?> &bull; <?= e($acc['account_number'] ?? '—') ?></p>

                    <div class="p-3 bg-light rounded mb-3 border">
                        <small class="text-muted d-block"><?= is_rtl() ? 'الرصيد الفعلي الحالي' : 'Current Authoritative Balance' ?></small>
                        <h3 class="fw-bold text-dark mb-0"><?= format_money($acc['current_balance'], $acc['currency']) ?></h3>
                    </div>

                    <div class="d-flex justify-content-between align-items-center text-muted small">
                        <span><i class="bi bi-clock-history me-1"></i> <?= $acc['tx_count'] ?> <?= is_rtl() ? 'معاملة مسجلة' : 'transactions' ?></span>
                        <a href="<?= url('finance/accounts/' . $acc['id']) ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-journal-text"></i> <?= is_rtl() ? 'كشف الحساب' : 'Ledger' ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: Internal Cash Transfer -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= url('finance/accounts/transfer') ?>" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="idempotency_key" value="TXF-<?= time() ?>-<?= bin2hex(random_bytes(2)) ?>">

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?= is_rtl() ? 'تحويل مالي بين الحسابات' : 'Internal Account Transfer' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'من الحساب (الخصم) *' : 'From Account (Debit) *' ?></label>
                    <select name="from_account_id" class="form-select" required>
                        <option value=""><?= e(__('common.select')) ?></option>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>"><?= e($acc['name']) ?> (<?= format_money($acc['current_balance'], $acc['currency']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'إلى الحساب (الإيداع) *' : 'To Account (Credit) *' ?></label>
                    <select name="to_account_id" class="form-select" required>
                        <option value=""><?= e(__('common.select')) ?></option>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>"><?= e($acc['name']) ?> (<?= format_money($acc['current_balance'], $acc['currency']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'المبلغ المراد تحويله *' : 'Transfer Amount *' ?></label>
                    <input type="number" step="0.01" name="amount" class="form-control" required min="0.01" placeholder="1000.00">
                    <small class="text-muted"><?= is_rtl() ? 'ملاحظة: التحويل يتم فقط بين الحسابات ذات العملة المتطابقة.' : 'Note: Transfer is only permitted between accounts of the same currency.' ?></small>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= is_rtl() ? 'سبب التحويل / ملاحظات' : 'Transfer Notes' ?></label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="<?= is_rtl() ? 'مثال: تغذية عهدة النثريات من الحساب البنكي' : 'e.g. Replenishing office petty cash from bank' ?>"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= e(__('common.cancel')) ?></button>
                <button type="submit" class="btn btn-primary fw-bold"><?= is_rtl() ? 'تأكيد التحويل الذري' : 'Execute Transfer' ?></button>
            </div>
        </form>
    </div>
</div>
