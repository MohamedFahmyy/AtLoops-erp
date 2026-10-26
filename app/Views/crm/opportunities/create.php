<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-funnel-fill text-primary me-2 ms-2"></i><?= is_rtl() ? 'إنشاء فرصة بيعية جديدة' : 'Create Sales Opportunity' ?></span>
                <a href="<?= url('crm/opportunities') ?>" class="btn btn-sm btn-outline-secondary"><?= e(__('common.back')) ?></a>
            </div>

            <div class="card-body p-4">
                <form action="<?= url('crm/opportunities') ?>" method="POST" novalidate>
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'عنوان الصفقة / الفرصة *' : 'Opportunity Title *' ?></label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. ERP System Implementation">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'المرحلة الأولية *' : 'Initial Stage *' ?></label>
                            <select name="stage" class="form-select" required>
                                <option value="new">New (جديدة)</option>
                                <option value="qualified" selected>Qualified (مؤهلة)</option>
                                <option value="contacted">Contacted (تم التواصل)</option>
                                <option value="meeting">Meeting (اجتماع)</option>
                                <option value="proposal">Proposal (تقديم عرض)</option>
                                <option value="negotiation">Negotiation (تفاوض)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'الشركة / العميل *' : 'Company / Client *' ?></label>
                            <select name="company_id" class="form-select" required>
                                <option value=""><?= e(__('common.select')) ?></option>
                                <?php foreach ($companies as $comp): ?>
                                    <option value="<?= $comp['id'] ?>"><?= e($comp['name']) ?> (<?= e($comp['country']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'القيمة التقديرية *' : 'Estimated Value *' ?></label>
                            <input type="number" step="0.01" name="estimated_value" class="form-control" required placeholder="50000.00">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold"><?= e(__('common.currency')) ?></label>
                            <select name="currency" class="form-select">
                                <option value="EGP" selected>EGP (جنيه)</option>
                                <option value="SAR">SAR (ريال)</option>
                                <option value="USD">USD ($)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'احتمالية الفوز بالصفقة (%)' : 'Win Probability (%)' ?></label>
                            <input type="number" name="probability" class="form-control" min="0" max="100" value="30">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'تاريخ الإغلاق المتوقع' : 'Expected Close Date' ?></label>
                            <input type="date" name="expected_close_date" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= url('crm/opportunities') ?>" class="btn btn-secondary"><?= e(__('common.cancel')) ?></a>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="bi bi-check2-circle"></i> <?= e(__('common.save')) ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
