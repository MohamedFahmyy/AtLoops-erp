<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt-cutoff text-primary me-2 ms-2"></i><?= is_rtl() ? 'إصدار فاتورة جديدة' : 'Create New Invoice' ?></span>
                <a href="<?= url('finance/invoices') ?>" class="btn btn-sm btn-outline-secondary"><?= e(__('common.back')) ?></a>
            </div>

            <div class="card-body p-4">
                <form action="<?= url('finance/invoices') ?>" method="POST" id="invoiceForm" novalidate>
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'العميل *' : 'Client *' ?></label>
                            <select name="company_id" class="form-select" id="companySelect" required>
                                <option value=""><?= e(__('common.select')) ?></option>
                                <?php foreach ($companies as $comp): ?>
                                    <option value="<?= $comp['id'] ?>" data-country="<?= strtolower(e($comp['country'] ?? 'egypt')) ?>">
                                        <?= e($comp['name']) ?> (<?= e($comp['country']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'تاريخ الإصدار *' : 'Issue Date *' ?></label>
                            <input type="date" name="issue_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'تاريخ الاستحقاق *' : 'Due Date *' ?></label>
                            <input type="date" name="due_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><?= e(__('common.currency')) ?></label>
                            <select name="currency" class="form-select" id="currencySelect">
                                <option value="EGP" selected>EGP (جنيه مصري)</option>
                                <option value="SAR">SAR (ريال سعودي)</option>
                                <option value="USD">USD (دولار أمريكي)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'نسبة ضريبة القيمة المضافة (%)' : 'VAT Rate (%)' ?></label>
                            <input type="number" step="0.01" name="tax_rate" class="form-control" value="14.00" id="taxRateInput">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'قيمة الخصم الثابت' : 'Fixed Discount' ?></label>
                            <input type="number" step="0.01" name="discount_value" class="form-control" value="0.00" id="discountInput">
                        </div>
                    </div>

                    <!-- Items Table -->
                    <h5 class="fw-bold text-primary mb-3"><?= is_rtl() ? 'بنود الفاتورة والخدمات' : 'Invoice Line Items' ?></h5>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="55%"><?= is_rtl() ? 'بيان الخدمة / التوصيف *' : 'Description *' ?></th>
                                    <th width="15%"><?= is_rtl() ? 'الكمية *' : 'Quantity *' ?></th>
                                    <th width="20%"><?= is_rtl() ? 'سعر الوحدة *' : 'Unit Price *' ?></th>
                                    <th width="10%" class="text-center">#</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr>
                                    <td>
                                        <input type="text" name="item_description[]" class="form-control form-control-sm" required placeholder="e.g. Website Development Phase 1">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="item_quantity[]" class="form-control form-control-sm item-qty" value="1.00" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="item_price[]" class="form-control form-control-sm item-price" value="0.00" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row" disabled>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-primary mb-4" id="addRowBtn">
                        <i class="bi bi-plus-circle"></i> <?= is_rtl() ? 'إضافة بند آخر' : 'Add Another Line Item' ?>
                    </button>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('common.notes')) ?></label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="<?= is_rtl() ? 'ملاحظات إضافية تظهر بالفاتورة...' : 'Notes to client...' ?>"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= is_rtl() ? 'الشروط والأحكام' : 'Terms & Conditions' ?></label>
                            <textarea name="terms" class="form-control" rows="2">Payment due within 14 days of invoice date.</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= url('finance/invoices') ?>" class="btn btn-secondary"><?= e(__('common.cancel')) ?></a>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="bi bi-check2-circle"></i> <?= is_rtl() ? 'اعتماد وإصدار الفاتورة' : 'Generate Invoice' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('itemsBody');
    const addBtn = document.getElementById('addRowBtn');

    addBtn.addEventListener('click', function () {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <input type="text" name="item_description[]" class="form-control form-control-sm" required placeholder="Service description...">
            </td>
            <td>
                <input type="number" step="0.01" name="item_quantity[]" class="form-control form-control-sm item-qty" value="1.00" required>
            </td>
            <td>
                <input type="number" step="0.01" name="item_price[]" class="form-control form-control-sm item-price" value="0.00" required>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(row);
        updateRemoveButtons();
    });

    tableBody.addEventListener('click', function (e) {
        if (e.target.closest('.remove-row')) {
            const row = e.target.closest('tr');
            if (tableBody.querySelectorAll('tr').length > 1) {
                row.remove();
                updateRemoveButtons();
            }
        }
    });

    const companySelect = document.getElementById('companySelect');
    const taxRateInput = document.getElementById('taxRateInput');
    const currencySelect = document.getElementById('currencySelect');

    if (companySelect && taxRateInput) {
        companySelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const country = (opt.getAttribute('data-country') || 'egypt').trim().toLowerCase();

            if (country === 'egypt') {
                taxRateInput.value = '14.00';
                if (currencySelect) currencySelect.value = 'EGP';
            } else if (country.includes('saudi') || country === 'ksa') {
                taxRateInput.value = '15.00';
                if (currencySelect) currencySelect.value = 'SAR';
            } else if (country.includes('emirates') || country === 'uae') {
                taxRateInput.value = '5.00';
                if (currencySelect) currencySelect.value = 'USD';
            } else {
                // Cross-border zero-rated export
                taxRateInput.value = '0.00';
                if (currencySelect) currencySelect.value = 'USD';
            }
        });
    }

    function updateRemoveButtons() {
        const buttons = tableBody.querySelectorAll('.remove-row');
        buttons.forEach(btn => btn.disabled = (buttons.length <= 1));
    }
});
</script>
