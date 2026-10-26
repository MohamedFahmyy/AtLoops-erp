<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><?= e(__('common.opportunities')) ?></h3>
        <p class="text-muted small mb-0"><?= is_rtl() ? 'خط سير الصفقات والمبيعات من أول تواصل وحتى الإغلاق والتعاقد' : 'Visual sales pipeline from initial contact to contract execution' ?></p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('crm/opportunities/create') ?>" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-plus-circle"></i> <?= e(__('common.add')) ?>
        </a>
    </div>
</div>

<!-- Pipeline Kanban Board -->
<div class="row g-3 flex-nowrap overflow-auto pb-4" style="min-height: 520px;">
    <?php foreach ($stages as $stageKey => $stageMeta): ?>
        <?php $deals = $oppsByStage[$stageKey] ?? []; ?>
        <div class="col-12 col-md-6 col-xl-3" style="min-width: 280px; max-width: 320px;">
            <div class="card h-100 bg-light-subtle border">
                <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
                    <span class="fw-bold fs-6 text-uppercase">
                        <span class="badge bg-<?= $stageMeta['color'] ?> me-1 ms-1"><?= count($deals) ?></span>
                        <?= $stageMeta['name'] ?>
                    </span>
                    <small class="text-muted">
                        <?= format_money(array_sum(array_column($deals, 'estimated_value')), $selectedCurrency) ?>
                    </small>
                </div>

                <div class="card-body p-2 d-flex flex-column gap-2" id="stage-<?= $stageKey ?>">
                    <?php if (empty($deals)): ?>
                        <div class="text-center py-4 text-muted small opacity-75">
                            <?= is_rtl() ? 'لا توجد صفقات في هذه المرحلة' : 'No opportunities here' ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($deals as $opp): ?>
                            <div class="card shadow-sm border mb-1">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold mb-0 text-dark"><?= e($opp['title']) ?></h6>
                                        <span class="badge bg-<?= $stageMeta['color'] ?>-subtle text-<?= $stageMeta['color'] ?> border" style="font-size: 11px;">
                                            <?= $opp['probability'] ?>%
                                        </span>
                                    </div>
                                    <div class="text-muted small mb-2">
                                        <i class="bi bi-building me-1"></i> <?= e($opp['company_name']) ?>
                                    </div>
                                    <div class="fw-bold text-primary fs-6 mb-3">
                                        <?= format_money($opp['estimated_value'], $opp['currency']) ?>
                                    </div>

                                    <!-- Quick Stage Changer -->
                                    <div class="d-flex justify-content-between align-items-center gap-2 pt-2 border-top">
                                        <select class="form-select form-select-sm stage-selector" data-opp-id="<?= $opp['id'] ?>" style="font-size: 11px;">
                                            <?php foreach ($stages as $k => $m): ?>
                                                <option value="<?= $k ?>" <?= $k === $stageKey ? 'selected' : '' ?>><?= $m['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                        <?php if ($stageKey !== 'won' && $stageKey !== 'lost'): ?>
                                            <form action="<?= url('crm/opportunities/' . $opp['id'] . '/convert-quote') ?>" method="POST" class="m-0">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="<?= is_rtl() ? 'تحويل إلى عرض سعر' : 'Convert to Quotation' ?>" style="font-size: 11px; white-space: nowrap;">
                                                    <i class="bi bi-file-earmark-text"></i> <?= is_rtl() ? 'عرض سعر' : 'Quote' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.stage-selector').forEach(select => {
        select.addEventListener('change', async function () {
            const oppId = this.getAttribute('data-opp-id');
            const newStage = this.value;

            try {
                const response = await fetch(`<?= url('crm/opportunities/') ?>${oppId}/stage`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: `stage=${encodeURIComponent(newStage)}&_csrf_token=${encodeURIComponent(getCsrfToken())}`
                });

                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || 'Failed to update stage');
                }
            } catch (err) {
                alert('Connection error while updating deal stage.');
            }
        });
    });
});
</script>
