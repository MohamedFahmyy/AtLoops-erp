<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-building-add text-primary me-2 ms-2"></i><?= is_rtl() ? 'إضافة شركة أو عميل جديد' : 'Create New Company / Client' ?></span>
                <a href="<?= url('crm/companies') ?>" class="btn btn-sm btn-outline-secondary"><?= e(__('common.back')) ?></a>
            </div>

            <div class="card-body p-4">
                <form action="<?= url('crm/companies') ?>" method="POST" novalidate>
                    <?= csrf_field() ?>

                    <h5 class="fw-bold text-primary mb-3"><?= is_rtl() ? '1. بيانات الشركة الأساسية' : '1. Corporate Details' ?></h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'اسم الشركة *' : 'Company Name *' ?></label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Al-Amal Group">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'مجال العمل / الصناعة' : 'Industry' ?></label>
                            <input type="text" name="industry" class="form-control" placeholder="e.g. Healthcare, Fintech">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'الدولة *' : 'Country *' ?></label>
                            <select name="country" class="form-select" required>
                                <option value="Egypt" selected>Egypt (مصر)</option>
                                <option value="Saudi Arabia">Saudi Arabia (المملكة العربية السعودية)</option>
                                <option value="UAE">United Arab Emirates (الإمارات)</option>
                                <option value="Iraq">Iraq (العراق)</option>
                                <option value="Kuwait">Kuwait (الكويت)</option>
                                <option value="Qatar">Qatar (قطر)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'المدينة' : 'City' ?></label>
                            <input type="text" name="city" class="form-control" placeholder="e.g. Cairo, Riyadh">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><?= e(__('common.status')) ?></label>
                            <select name="status" class="form-select">
                                <option value="lead"><?= is_rtl() ? 'مبدئي (Lead)' : 'Lead' ?></option>
                                <option value="prospect"><?= is_rtl() ? 'محتمل (Prospect)' : 'Prospect' ?></option>
                                <option value="active_client"><?= is_rtl() ? 'عميل نشط (Active Client)' : 'Active Client' ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'البريد الإلكتروني للشركة' : 'Corporate Email' ?></label>
                            <input type="email" name="email" class="form-control" placeholder="info@company.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'الهاتف' : 'Phone' ?></label>
                            <input type="text" name="phone" class="form-control" placeholder="+20 100 ...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'الموقع الإلكتروني' : 'Website' ?></label>
                            <input type="url" name="website" class="form-control" placeholder="https://...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'الرقم الضريبي' : 'Tax Number' ?></label>
                            <input type="text" name="tax_number" class="form-control" placeholder="EG-...">
                        </div>
                    </div>

                    <h5 class="fw-bold text-primary mb-3"><?= is_rtl() ? '2. جهة الاتصال الرئيسية (اختياري)' : '2. Primary Contact Person' ?></h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'الاسم الأول' : 'First Name' ?></label>
                            <input type="text" name="contact_first_name" class="form-control" placeholder="e.g. Ahmed">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'الاسم الأخير' : 'Last Name' ?></label>
                            <input type="text" name="contact_last_name" class="form-control" placeholder="e.g. Hassan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'المسمى الوظيفي' : 'Job Title' ?></label>
                            <input type="text" name="contact_job_title" class="form-control" placeholder="e.g. CTO / IT Manager">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= is_rtl() ? 'البريد المباشر' : 'Direct Email' ?></label>
                            <input type="email" name="contact_email" class="form-control" placeholder="ahmed@company.com">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= url('crm/companies') ?>" class="btn btn-secondary"><?= e(__('common.cancel')) ?></a>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="bi bi-check2-circle"></i> <?= e(__('common.save')) ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
