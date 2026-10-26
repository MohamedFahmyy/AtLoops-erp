<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold mb-0"><?= e($company['name']) ?></h3>
            <?php if ($company['status'] === 'active_client'): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle"><?= is_rtl() ? 'عميل نشط' : 'Active Client' ?></span>
            <?php elseif ($company['status'] === 'prospect'): ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= is_rtl() ? 'محتمل' : 'Prospect' ?></span>
            <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><?= is_rtl() ? 'مبدئي' : 'Lead' ?></span>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-0 mt-1"><?= e($company['industry'] ?? 'General IT') ?> &bull; <?= e($company['country']) ?><?= !empty($company['city']) ? ', ' . e($company['city']) : '' ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('crm/companies') ?>" class="btn btn-outline-secondary btn-sm"><?= e(__('common.back')) ?></a>
    </div>
</div>

<div class="row g-4">
    <!-- Company Information Card -->
    <div class="col-12 col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle text-primary me-2 ms-2"></i><?= is_rtl() ? 'بيانات التواصل والملف' : 'Contact & Tax Profile' ?>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-3">
                        <small class="text-muted d-block"><?= is_rtl() ? 'البريد الإلكتروني' : 'Email' ?></small>
                        <span class="fw-semibold"><?= e($company['email'] ?? '—') ?></span>
                    </li>
                    <li class="mb-3">
                        <small class="text-muted d-block"><?= is_rtl() ? 'الهاتف' : 'Phone' ?></small>
                        <span class="fw-semibold"><?= e($company['phone'] ?? '—') ?></span>
                    </li>
                    <li class="mb-3">
                        <small class="text-muted d-block"><?= is_rtl() ? 'الموقع الإلكتروني' : 'Website' ?></small>
                        <?php if (!empty($company['website'])): ?>
                            <a href="<?= e($company['website']) ?>" target="_blank" class="fw-semibold"><?= e($company['website']) ?></a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </li>
                    <li class="mb-3">
                        <small class="text-muted d-block"><?= is_rtl() ? 'الرقم الضريبي' : 'Tax ID' ?></small>
                        <code><?= e($company['tax_number'] ?? '—') ?></code>
                    </li>
                    <li>
                        <small class="text-muted d-block"><?= is_rtl() ? 'العنوان' : 'Address' ?></small>
                        <span><?= nl2br(e($company['address'] ?? '—')) ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Contacts List Card -->
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people text-primary me-2 ms-2"></i><?= is_rtl() ? 'جهات الاتصال المسجلة' : 'Registered Contacts' ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= is_rtl() ? 'الاسم' : 'Name' ?></th>
                            <th><?= is_rtl() ? 'المسمى الوظيفي' : 'Job Title' ?></th>
                            <th><?= is_rtl() ? 'البريد والهاتف' : 'Email & Phone' ?></th>
                            <th><?= is_rtl() ? 'السند القانوني' : 'Lawful Basis' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($company['contacts'])): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <?= is_rtl() ? 'لا توجد جهات اتصال مسجلة لهذه الشركة.' : 'No contacts recorded.' ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($company['contacts'] as $ct): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= e($ct['first_name'] . ' ' . ($ct['last_name'] ?? '')) ?></div>
                                        <?php if ($ct['is_primary']): ?>
                                            <span class="badge bg-primary-subtle text-primary" style="font-size: 10px;"><?= is_rtl() ? 'رئيسي' : 'Primary' ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($ct['job_title'] ?? '—') ?></td>
                                    <td>
                                        <div><i class="bi bi-envelope text-muted me-1"></i> <?= e($ct['email'] ?? '—') ?></div>
                                        <small class="text-muted"><i class="bi bi-telephone text-muted me-1"></i> <?= e($ct['phone'] ?? '—') ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= e($ct['lawful_basis']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
