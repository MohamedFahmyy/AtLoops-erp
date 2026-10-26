<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><?= e(__('common.companies')) ?></h3>
        <p class="text-muted small mb-0"><?= is_rtl() ? 'إدارة سجلات الشركات، العملاء الحاليين والمحتملين في مصر والخليج' : 'Manage corporate client and prospect directory across target markets' ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('crm/companies-export') ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-file-earmark-spreadsheet"></i> <?= e(__('common.export_csv')) ?>
        </a>
        <a href="<?= url('crm/companies/create') ?>" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-plus-circle"></i> <?= e(__('common.add')) ?>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small"><?= is_rtl() ? 'تصفية الحالة:' : 'Status Filter:' ?></span>
            <div class="btn-group btn-group-sm" role="group">
                <a href="<?= url('crm/companies') ?>" class="btn <?= empty($selectedStatus) ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= e(__('common.all')) ?></a>
                <a href="<?= url('crm/companies?status=active_client') ?>" class="btn <?= $selectedStatus === 'active_client' ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= is_rtl() ? 'عميل نشط' : 'Active Client' ?></a>
                <a href="<?= url('crm/companies?status=prospect') ?>" class="btn <?= $selectedStatus === 'prospect' ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= is_rtl() ? 'محتمل' : 'Prospect' ?></a>
                <a href="<?= url('crm/companies?status=lead') ?>" class="btn <?= $selectedStatus === 'lead' ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= is_rtl() ? 'مبدئي (Lead)' : 'Lead' ?></a>
            </div>
        </div>

        <form action="<?= url('crm/companies') ?>" method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="<?= e(__('common.search')) ?>" value="<?= e($searchQuery ?? '') ?>">
            <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th><?= is_rtl() ? 'اسم الشركة / العميل' : 'Company Name' ?></th>
                    <th><?= is_rtl() ? 'الدولة / المدينة' : 'Country / City' ?></th>
                    <th><?= is_rtl() ? 'جهات الاتصال' : 'Contacts' ?></th>
                    <th><?= is_rtl() ? 'المشاريع' : 'Projects' ?></th>
                    <th><?= e(__('common.status')) ?></th>
                    <th class="text-end"><?= e(__('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($companies)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-buildings fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <?= e(__('common.no_records')) ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($companies as $c): ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td>
                                <a href="<?= url('crm/companies/' . $c['id']) ?>" class="fw-bold text-decoration-none">
                                    <?= e($c['name']) ?>
                                </a>
                                <?php if (!empty($c['industry'])): ?>
                                    <span class="d-block text-muted small"><?= e($c['industry']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span><?= e($c['country']) ?></span>
                                <?php if (!empty($c['city'])): ?>
                                    <span class="text-muted small">, <?= e($c['city']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= $c['contacts_count'] ?></span></td>
                            <td><span class="badge bg-light text-dark border"><?= $c['projects_count'] ?></span></td>
                            <td>
                                <?php if ($c['status'] === 'active_client'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><?= is_rtl() ? 'عميل نشط' : 'Active Client' ?></span>
                                <?php elseif ($c['status'] === 'prospect'): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= is_rtl() ? 'محتمل' : 'Prospect' ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><?= is_rtl() ? 'مبدئي' : 'Lead' ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('crm/companies/' . $c['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> <?= e(__('common.view')) ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
