<?php
$subtotal = number_format((float)$invoice['subtotal'], 2);
$discount = number_format((float)$invoice['discount_amount'], 2);
$tax = number_format((float)$invoice['tax_amount'], 2);
$total = number_format((float)$invoice['total_amount'], 2);
$paid = number_format((float)$invoice['paid_amount'], 2);
$outstanding = number_format(max(0, (float)$invoice['total_amount'] - (float)$invoice['paid_amount']), 2);
$currency = $invoice['currency'];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold mb-0">Invoice <?= e($invoice['invoice_number']) ?></h3>
            <?php if ($invoice['status'] === 'paid'): ?>
                <span class="badge bg-success"><?= is_rtl() ? 'مسددة بالكامل' : 'PAID' ?></span>
            <?php elseif ($invoice['status'] === 'partially_paid'): ?>
                <span class="badge bg-warning text-dark"><?= is_rtl() ? 'مسددة جزئياً' : 'PARTIALLY PAID' ?></span>
            <?php elseif ($invoice['status'] === 'overdue'): ?>
                <span class="badge bg-danger"><?= is_rtl() ? 'متأخرة' : 'OVERDUE' ?></span>
            <?php elseif ($invoice['status'] === 'void'): ?>
                <span class="badge bg-secondary"><?= is_rtl() ? 'ملغاة' : 'VOID' ?></span>
            <?php else: ?>
                <span class="badge bg-info text-dark"><?= is_rtl() ? 'غير مسددة' : 'UNPAID' ?></span>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-0 mt-1"><?= e($invoice['company_name']) ?> &bull; Issue Date: <?= e($invoice['issue_date']) ?> &bull; Due Date: <?= e($invoice['due_date']) ?></p>
    </div>

    <div class="d-flex gap-2">
        <a href="<?= url('finance/invoices') ?>" class="btn btn-outline-secondary btn-sm"><?= e(__('common.back')) ?></a>
        <a href="<?= url('finance/invoices/' . $invoice['id'] . '/pdf') ?>" target="_blank" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-file-earmark-pdf"></i> <?= e(__('common.download_pdf')) ?>
        </a>

        <?php if ($invoice['status'] !== 'paid' && $invoice['status'] !== 'void'): ?>
            <button type="button" class="btn btn-success btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                <i class="bi bi-cash-coin"></i> <?= is_rtl() ? 'تسجيل دفعة / تحصيل' : 'Record Payment' ?>
            </button>
        <?php endif; ?>

        <?php if ($invoice['paid_amount'] == 0 && $invoice['status'] !== 'void'): ?>
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#voidInvoiceModal">
                <i class="bi bi-x-circle"></i> <?= is_rtl() ? 'إلغاء الفاتورة' : 'Void Invoice' ?>
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Invoice Details Card -->
    <div class="col-12 col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <i class="bi bi-list-check text-primary me-2 ms-2"></i><?= is_rtl() ? 'بنود الفاتورة' : 'Line Items' ?>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="8%" class="text-center">#</th>
                            <th><?= is_rtl() ? 'بيان الخدمة' : 'Description' ?></th>
                            <th width="12%" class="text-center"><?= is_rtl() ? 'الكمية' : 'Qty' ?></th>
                            <th width="18%" class="text-end"><?= is_rtl() ? 'سعر الوحدة' : 'Unit Price' ?></th>
                            <th width="18%" class="text-end"><?= is_rtl() ? 'الإجمالي' : 'Total' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($invoice['items'] ?? []) as $idx => $it): ?>
                            <tr>
                                <td class="text-center"><?= $idx + 1 ?></td>
                                <td><?= nl2br(e($it['description'])) ?></td>
                                <td class="text-center"><?= number_format((float)$it['quantity'], 2) ?></td>
                                <td class="text-end"><?= number_format((float)$it['unit_price'], 2) ?></td>
                                <td class="text-end fw-bold"><?= number_format((float)$it['line_total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payments Collected for this Invoice -->
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cash-stack text-success me-2 ms-2"></i><?= is_rtl() ? 'سجل الدفعات المحصلة لهذه الفاتورة' : 'Payment Collections History' ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= is_rtl() ? 'رقم الدفعة' : 'Payment #' ?></th>
                            <th><?= is_rtl() ? 'تاريخ التحصيل' : 'Date' ?></th>
                            <th><?= is_rtl() ? 'الحساب المودع فيه' : 'Account' ?></th>
                            <th><?= is_rtl() ? 'المبلغ المحصل' : 'Amount' ?></th>
                            <th><?= is_rtl() ? 'طريقة الدفع' : 'Method' ?></th>
                            <th><?= is_rtl() ? 'الحالة' : 'Status' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoice['payments'])): ?>
                            <tr>
                                <td colspan="6" class="text-center py-3 text-muted">
                                    <?= is_rtl() ? 'لم يتم تحصيل أي مبالغ حتى الآن.' : 'No payments collected yet.' ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($invoice['payments'] as $p): ?>
                                <tr>
                                    <td><code><?= e($p['payment_number']) ?></code></td>
                                    <td><?= e($p['payment_date']) ?></td>
                                    <td><?= e($p['account_name']) ?></td>
                                    <td class="fw-bold text-success"><?= format_money($p['allocated_amount'], $p['currency']) ?></td>
                                    <td><?= e($p['payment_method']) ?></td>
                                    <td>
                                        <?php if ($p['status'] === 'completed'): ?>
                                            <span class="badge bg-success"><?= is_rtl() ? 'معتمدة' : 'Completed' ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?= is_rtl() ? 'تم التراجع عنها' : 'Reversed' ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Summary Box -->
    <div class="col-12 col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <i class="bi bi-wallet2 text-primary me-2 ms-2"></i><?= is_rtl() ? 'المطالبة المالية والملخص' : 'Financial Breakdown' ?>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><?= is_rtl() ? 'المجموع الفرعي:' : 'Subtotal:' ?></span>
                        <span class="fw-semibold"><?= $subtotal ?> <?= $currency ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><?= is_rtl() ? 'الخصم:' : 'Discount:' ?></span>
                        <span class="text-danger">-<?= $discount ?> <?= $currency ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><?= is_rtl() ? 'ضريبة القيمة المضافة (' . (float)$invoice['tax_rate'] . '%):' : 'VAT (' . (float)$invoice['tax_rate'] . '%):' ?></span>
                        <span class="fw-semibold"><?= $tax ?> <?= $currency ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom fs-5 fw-bold bg-light px-2 my-2 rounded">
                        <span><?= is_rtl() ? 'الإجمالي الكلي:' : 'Grand Total:' ?></span>
                        <span class="text-primary"><?= $total ?> <?= $currency ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><?= is_rtl() ? 'المبلغ المحصل:' : 'Collected:' ?></span>
                        <span class="text-success fw-bold"><?= $paid ?> <?= $currency ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2 fs-5 fw-bold text-danger">
                        <span><?= is_rtl() ? 'المبلغ المتبقي:' : 'Balance Due:' ?></span>
                        <span><?= $outstanding ?> <?= $currency ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Record Payment -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= url('finance/payments') ?>" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
            <!-- Automatic idempotency key to prevent double submits -->
            <input type="hidden" name="idempotency_key" value="PAY-<?= $invoice['id'] ?>-<?= time() ?>-<?= bin2hex(random_bytes(2)) ?>">

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?= is_rtl() ? 'تسجيل دفعة / تحصيل بنكي' : 'Record Payment Collection' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small">
                    <?= is_rtl() ? 'المبلغ المتبقي المستحق على الفاتورة:' : 'Outstanding balance on this invoice:' ?>
                    <b><?= format_money((float)$invoice['total_amount'] - (float)$invoice['paid_amount'], $invoice['currency']) ?></b>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'الحساب البنكي / الخزينة المستلمة *' : 'Deposit Cash/Bank Account *' ?></label>
                    <select name="cash_account_id" class="form-select" required>
                        <?php
                            $pdo = \App\Core\Database::pdo();
                            $accounts = $pdo->prepare("SELECT * FROM cash_accounts WHERE is_active = 1 AND currency = :curr");
                            $accounts->execute(['curr' => $invoice['currency']]);
                            $accs = $accounts->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php foreach ($accs as $acc): ?>
                            <option value="<?= $acc['id'] ?>"><?= e($acc['name']) ?> (<?= e($acc['currency']) ?> - Balance: <?= number_format((float)$acc['current_balance'], 2) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'المبلغ المحصل *' : 'Amount Collected *' ?></label>
                    <input type="number" step="0.01" name="amount" class="form-control" required max="<?= max(0, (float)$invoice['total_amount'] - (float)$invoice['paid_amount']) ?>" value="<?= max(0, (float)$invoice['total_amount'] - (float)$invoice['paid_amount']) ?>">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold"><?= is_rtl() ? 'تاريخ التحصيل *' : 'Payment Date *' ?></label>
                        <input type="date" name="payment_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold"><?= is_rtl() ? 'طريقة الدفع *' : 'Payment Method *' ?></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="bank_transfer" selected><?= is_rtl() ? 'تحويل بنكي' : 'Bank Transfer' ?></option>
                            <option value="cash"><?= is_rtl() ? 'نقدي (خزينة)' : 'Cash' ?></option>
                            <option value="check"><?= is_rtl() ? 'شيك بنكي' : 'Check' ?></option>
                            <option value="online"><?= is_rtl() ? 'دفع إلكتروني' : 'Online' ?></option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'رقم الإشعار البنكي / المرجع' : 'Bank Reference Number' ?></label>
                    <input type="text" name="reference_number" class="form-control" placeholder="e.g. CIB-TX-123456">
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= e(__('common.notes')) ?></label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= e(__('common.cancel')) ?></button>
                <button type="submit" class="btn btn-success fw-bold"><?= is_rtl() ? 'تأكيد التحصيل وتحديث الحساب' : 'Confirm Payment & Update Balance' ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Void Invoice -->
<div class="modal fade" id="voidInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= url('finance/invoices/' . $invoice['id'] . '/void') ?>" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger"><?= is_rtl() ? 'إلغاء الفاتورة' : 'Void Invoice' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small"><?= is_rtl() ? 'تحذير: سيتم وضع الفاتورة في حالة ملغاة ولن يمكن تعديلها بعد ذلك.' : 'Warning: Voiding an invoice marks it permanently cancelled.' ?></p>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'سبب الإلغاء *' : 'Reason for Voiding *' ?></label>
                    <textarea name="reason" class="form-control" required rows="3" placeholder="<?= is_rtl() ? 'اذكر سبب الإلغاء للتوثيق...' : 'Explain why this invoice is being voided...' ?>"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= e(__('common.cancel')) ?></button>
                <button type="submit" class="btn btn-danger fw-bold"><?= is_rtl() ? 'تأكيد الإلغاء' : 'Confirm Void' ?></button>
            </div>
        </form>
    </div>
</div>
