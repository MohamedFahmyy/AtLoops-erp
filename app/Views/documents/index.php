<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><?= e(__('common.documents')) ?></h3>
        <p class="text-muted small mb-0"><?= is_rtl() ? 'خزينة المستندات المشفرة والآمنة خارج المسار العام للخادم' : 'Private secure document repository stored outside public web root with access checks' ?></p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
            <i class="bi bi-cloud-arrow-up"></i> <?= is_rtl() ? 'رفع مستند آمن' : 'Upload Document' ?>
        </button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th><?= is_rtl() ? 'عنوان المستند' : 'Document Title' ?></th>
                    <th><?= is_rtl() ? 'اسم الملف الأصلي' : 'Original Filename' ?></th>
                    <th><?= is_rtl() ? 'التصنيف' : 'Category' ?></th>
                    <th><?= is_rtl() ? 'الحجم' : 'File Size' ?></th>
                    <th><?= is_rtl() ? 'الرافع' : 'Uploaded By' ?></th>
                    <th><?= is_rtl() ? 'تاريخ الرفع' : 'Upload Date' ?></th>
                    <th class="text-end"><?= e(__('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documents)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-folder-lock fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <?= e(__('common.no_records')) ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><?= $doc['id'] ?></td>
                            <td>
                                <span class="fw-bold text-dark"><?= e($doc['title']) ?></span>
                            </td>
                            <td><code><?= e($doc['original_name']) ?></code></td>
                            <td><span class="badge bg-light text-dark border"><?= e($doc['category']) ?></span></td>
                            <td><?= round($doc['file_size'] / 1024, 1) ?> KB</td>
                            <td><?= e($doc['uploader_name']) ?></td>
                            <td><?= e($doc['created_at']) ?></td>
                            <td class="text-end">
                                <a href="<?= url('documents/' . $doc['id'] . '/download') ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download"></i> <?= is_rtl() ? 'تحميل آمن' : 'Download' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Upload Document -->
<div class="modal fade" id="uploadDocModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= url('documents/upload') ?>" method="POST" enctype="multipart/form-data" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?= is_rtl() ? 'رفع مستند إلى الخزينة الآمنة' : 'Upload Secure Document' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'عنوان المستند *' : 'Title *' ?></label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Commercial Registration Certificate">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'التصنيف *' : 'Category *' ?></label>
                    <select name="category" class="form-select">
                        <option value="legal">Legal & Contracts</option>
                        <option value="tax">Tax & Invoicing</option>
                        <option value="technical">Technical Specs</option>
                        <option value="general" selected>General</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= is_rtl() ? 'الملف (PDF, DOCX, ZIP, PNG) *' : 'File *' ?></label>
                    <input type="file" name="document" class="form-control" required>
                    <small class="text-muted"><?= is_rtl() ? 'الحد الأقصى: 10 ميجابايت. يتم فحص نوع الملف وحفظه خارج المجلد العام.' : 'Max 10 MB. Verified with MIME inspection and stored privately.' ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= e(__('common.cancel')) ?></button>
                <button type="submit" class="btn btn-primary fw-bold"><?= is_rtl() ? 'رفع وتأمين' : 'Upload & Vault' ?></button>
            </div>
        </form>
    </div>
</div>
