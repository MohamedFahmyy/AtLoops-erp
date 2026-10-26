<!-- Custom Dashboard Design System Styles -->
<style>
    .dash-hero {
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.08) 0%, rgba(16, 185, 129, 0.05) 50%, rgba(99, 102, 241, 0.08) 100%);
        border: 1px solid var(--bs-border-color);
        border-radius: 16px;
        padding: 24px 28px;
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(10px);
    }
    [data-bs-theme="dark"] .dash-hero {
        background: linear-gradient(135deg, rgba(30, 58, 138, 0.25) 0%, rgba(6, 78, 59, 0.2) 50%, rgba(49, 46, 129, 0.25) 100%);
        border-color: rgba(255, 255, 255, 0.08);
    }

    .pulse-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
        background: rgba(16, 185, 129, 0.12);
        color: #10b981;
    }
    .pulse-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background-color: #10b981;
        display: inline-block;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: dashPulse 2s infinite;
    }
    @keyframes dashPulse {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* Currency Segmented Control */
    .currency-pill-group {
        background: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 30px;
        padding: 3px;
        display: inline-flex;
        gap: 2px;
    }
    .currency-pill-btn {
        padding: 5px 14px;
        border-radius: 25px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        color: var(--bs-body-color);
        transition: all 0.2s ease;
        border: none;
    }
    .currency-pill-btn:hover {
        color: var(--bs-primary);
        background: rgba(37, 99, 235, 0.08);
    }
    .currency-pill-btn.active {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);
    }

    /* KPI Modern Cards */
    .kpi-card {
        border-radius: 16px;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
        padding: 22px;
        position: relative;
        overflow: hidden;
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s ease, border-color 0.25s ease;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
    }
    .kpi-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px -6px rgba(0, 0, 0, 0.08);
    }
    [data-bs-theme="dark"] .kpi-card:hover {
        box-shadow: 0 12px 28px -6px rgba(0, 0, 0, 0.35);
    }

    .kpi-icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .kpi-emerald {
        border-top: 3px solid #10b981;
    }
    .kpi-emerald .kpi-icon-wrap {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.3));
        color: #10b981;
    }

    .kpi-rose {
        border-top: 3px solid #ef4444;
    }
    .kpi-rose .kpi-icon-wrap {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.3));
        color: #ef4444;
    }

    .kpi-indigo {
        border-top: 3px solid #6366f1;
    }
    .kpi-indigo .kpi-icon-wrap {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(99, 102, 241, 0.3));
        color: #6366f1;
    }

    .kpi-cyan {
        border-top: 3px solid #06b6d4;
    }
    .kpi-cyan .kpi-icon-wrap {
        background: linear-gradient(135deg, rgba(6, 182, 212, 0.15), rgba(6, 182, 212, 0.3));
        color: #06b6d4;
    }

    .kpi-value {
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -0.5px;
        line-height: 1.15;
    }

    /* Subtle Progress Mini Bars */
    .mini-progress {
        height: 6px;
        border-radius: 6px;
        background-color: var(--bs-tertiary-bg);
        overflow: hidden;
    }

    /* Quick Action Button Pill */
    .quick-action-btn {
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        padding: 8px 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .quick-action-btn:hover {
        transform: translateY(-1px);
    }

    /* Chart Cards & Section Headers */
    .chart-card {
        border-radius: 16px;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
    }
    .chart-card .card-header {
        border-bottom: 1px solid var(--bs-border-color);
        padding: 18px 22px;
        background: transparent;
    }

    .chart-toggle-pill {
        background: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 20px;
        padding: 2px;
        display: inline-flex;
    }
    .chart-toggle-btn {
        border: none;
        background: transparent;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 15px;
        color: var(--bs-secondary-color);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .chart-toggle-btn.active {
        background: var(--bs-body-bg);
        color: var(--bs-primary);
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    }

    /* Operational Hub Cards */
    .hub-card {
        border-radius: 16px;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hub-card:hover {
        box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.06);
    }

    .hub-item-row {
        padding: 12px 0;
        border-bottom: 1px solid var(--bs-border-color-translucent);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .hub-item-row:last-child {
        border-bottom: none;
        padding-bottom: 4px;
    }

    /* Activity Desk Tabs */
    .desk-tabs .nav-link {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--bs-secondary-color);
        border: none;
        border-bottom: 2px solid transparent;
        padding: 12px 18px;
        border-radius: 0;
    }
    .desk-tabs .nav-link:hover {
        color: var(--bs-primary);
    }
    .desk-tabs .nav-link.active {
        color: var(--bs-primary);
        border-bottom: 2px solid var(--bs-primary);
        background: transparent;
    }

    /* Smooth Table Enhancements */
    .desk-table th {
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--bs-secondary-color);
        background-color: var(--bs-tertiary-bg);
        border-bottom: 1px solid var(--bs-border-color);
        padding: 12px 16px;
    }
    .desk-table td {
        padding: 14px 16px;
        vertical-align: middle;
        font-size: 13.5px;
        border-bottom: 1px solid var(--bs-border-color-translucent);
    }
    .desk-table tr:hover td {
        background-color: rgba(37, 99, 235, 0.03);
    }
</style>

<!-- 1. Executive Hero & Quick Actions Banner -->
<div class="dash-hero mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <!-- Title & Subtitle -->
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="pulse-indicator">
                    <span class="pulse-dot"></span>
                    <?= is_rtl() ? 'بيانات حية متزامنة' : 'Real-time Operations' ?>
                </span>
                <span class="text-muted small">|</span>
                <span class="text-muted small fw-medium">
                    <i class="bi bi-calendar3 me-1 ms-1"></i><?= date('D, d M Y') ?>
                </span>
            </div>
            <h2 class="fw-bolder mb-1 text-dark d-flex align-items-center gap-2">
                <span><?= e(__('common.dashboard')) ?></span>
                <span class="badge bg-primary bg-opacity-10 text-primary fs-6 fw-normal px-2 py-1 rounded-pill">
                    <?= e($selectedCurrency) ?>
                </span>
            </h2>
            <p class="text-muted small mb-0">
                <?= is_rtl()
                    ? 'مركز التحكم التنفيذي الشامل للسيولة، تدفق المبيعات، ومؤشرات إنجاز المشاريع'
                    : 'Authoritative executive intelligence on liquidity, revenue pipeline, and operational delivery' ?>
            </p>
        </div>

        <!-- Right Side: Currency Segmented Switcher & Quick Navigation -->
        <div class="d-flex flex-wrap align-items-center gap-3">
            <!-- Currency Segmented Control -->
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small fw-semibold d-none d-sm-inline"><?= e(__('common.currency')) ?>:</span>
                <div class="currency-pill-group" role="group" aria-label="Currency Selector">
                    <a href="<?= url('dashboard?currency=EGP') ?>" class="currency-pill-btn <?= $selectedCurrency === 'EGP' ? 'active' : '' ?>">EGP (جنيه)</a>
                    <a href="<?= url('dashboard?currency=SAR') ?>" class="currency-pill-btn <?= $selectedCurrency === 'SAR' ? 'active' : '' ?>">SAR (ريال)</a>
                    <a href="<?= url('dashboard?currency=USD') ?>" class="currency-pill-btn <?= $selectedCurrency === 'USD' ? 'active' : '' ?>">USD ($)</a>
                </div>
            </div>

            <!-- Quick Action Shortcuts -->
            <div class="dropdown">
                <button class="btn btn-primary btn-sm quick-action-btn dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-plus-lg"></i>
                    <span><?= is_rtl() ? 'إجراء سريع' : 'Quick Action' ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li><h6 class="dropdown-header small text-uppercase"><?= is_rtl() ? 'عمليات سريعة' : 'Create Record' ?></h6></li>
                    <li>
                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= url('finance/invoices/create') ?>">
                            <i class="bi bi-receipt text-primary"></i> <?= is_rtl() ? 'إصدار فاتورة جديدة' : 'New Invoice' ?>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= url('crm/opportunities/create') ?>">
                            <i class="bi bi-funnel text-success"></i> <?= is_rtl() ? 'إضافة فرصة بيعية' : 'New Opportunity' ?>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= url('crm/companies/create') ?>">
                            <i class="bi bi-building text-info"></i> <?= is_rtl() ? 'تسجيل عميل / شركة' : 'New Company' ?>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= url('finance/expenses') ?>">
                            <i class="bi bi-credit-card text-danger"></i> <?= is_rtl() ? 'تسجيل مصروف تشغيلي' : 'Record Expense' ?>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= url('reports') ?>">
                            <i class="bi bi-graph-up text-secondary"></i> <?= is_rtl() ? 'التقارير المالية والتحليلية' : 'Executive Reports' ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- 2. Authoritative Financial KPI Grid -->
<div class="row g-3 mb-4">
    <!-- Card 1: Monthly Collections -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card kpi-emerald h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase tracking-wider">
                        <?= is_rtl() ? 'تحصيلات هذا الشهر' : 'Monthly Collections' ?>
                    </span>
                    <div class="kpi-icon-wrap">
                        <i class="bi bi-arrow-down-left-circle-fill"></i>
                    </div>
                </div>
                <div class="kpi-value text-success mb-2">
                    <?= format_money($financial['month_collected'], $selectedCurrency) ?>
                </div>
            </div>

            <div>
                <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                    <span><?= is_rtl() ? 'إجمالي التحصيلات الكلية:' : 'All-time collected:' ?></span>
                    <span class="fw-bold text-dark"><?= format_money($financial['total_collected'], $selectedCurrency) ?></span>
                </div>
                <!-- Mini Progress Ratio Indicator -->
                <?php
                $collTotal = max((float)$financial['total_collected'], 1.0);
                $collRatio = min(100, round(((float)$financial['month_collected'] / $collTotal) * 100, 1));
                ?>
                <div class="mini-progress mt-2" title="Month Share: <?= $collRatio ?>%">
                    <div class="progress-bar bg-success" style="width: <?= max(5, $collRatio) ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Operating Expenses -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card kpi-rose h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase tracking-wider">
                        <?= is_rtl() ? 'مصروفات هذا الشهر' : 'Monthly Expenses' ?>
                    </span>
                    <div class="kpi-icon-wrap">
                        <i class="bi bi-arrow-up-right-circle-fill"></i>
                    </div>
                </div>
                <div class="kpi-value text-danger mb-2">
                    <?= format_money($financial['month_expenses'], $selectedCurrency) ?>
                </div>
            </div>

            <div>
                <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                    <span><?= is_rtl() ? 'إجمالي المصروفات الكلية:' : 'All-time paid expenses:' ?></span>
                    <span class="fw-bold text-dark"><?= format_money($financial['total_expenses'], $selectedCurrency) ?></span>
                </div>
                <?php
                $expTotal = max((float)$financial['total_expenses'], 1.0);
                $expRatio = min(100, round(((float)$financial['month_expenses'] / $expTotal) * 100, 1));
                ?>
                <div class="mini-progress mt-2" title="Month Share: <?= $expRatio ?>%">
                    <div class="progress-bar bg-danger" style="width: <?= max(5, $expRatio) ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Net Cash Flow -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card <?= $financial['net_cash_flow_month'] >= 0 ? 'kpi-emerald' : 'kpi-rose' ?> h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase tracking-wider">
                        <?= is_rtl() ? 'صافي التدفق الشهري' : 'Net Cash Flow (Month)' ?>
                    </span>
                    <div class="kpi-icon-wrap">
                        <i class="bi <?= $financial['net_cash_flow_month'] >= 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow' ?>"></i>
                    </div>
                </div>
                <div class="kpi-value <?= $financial['net_cash_flow_month'] >= 0 ? 'text-success' : 'text-danger' ?> mb-2">
                    <?= format_money($financial['net_cash_flow_month'], $selectedCurrency) ?>
                </div>
            </div>

            <div>
                <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                    <span><?= is_rtl() ? 'السيولة الصافية:' : 'Net Liquidity Status:' ?></span>
                    <span class="badge <?= $financial['net_cash_flow_month'] >= 0 ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger' ?> rounded-pill">
                        <?= $financial['net_cash_flow_month'] >= 0 ? (is_rtl() ? 'فائض نقدي' : 'Cash Surplus') : (is_rtl() ? 'عجز تشغيلي' : 'Cash Deficit') ?>
                    </span>
                </div>
                <div class="mini-progress mt-2">
                    <div class="progress-bar <?= $financial['net_cash_flow_month'] >= 0 ? 'bg-success' : 'bg-danger' ?>" style="width: 100%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Cash Balance & MRR -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card kpi-cyan h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase tracking-wider">
                        <?= is_rtl() ? 'رصيد الخزينة والحسابات' : 'Current Cash Balance' ?>
                    </span>
                    <div class="kpi-icon-wrap">
                        <i class="bi bi-bank2"></i>
                    </div>
                </div>
                <div class="kpi-value text-info mb-2">
                    <?= format_money($financial['total_cash_balance'], $selectedCurrency) ?>
                </div>
            </div>

            <div>
                <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                    <span><?= is_rtl() ? 'إيرادات شهرية متكررة:' : 'Monthly Recurring (MRR):' ?></span>
                    <span class="fw-bold text-primary"><?= format_money($financial['mrr'], $selectedCurrency) ?></span>
                </div>
                <div class="mini-progress mt-2">
                    <div class="progress-bar bg-info" style="width: 100%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3. Modern Charts Row -->
<div class="row g-3 mb-4">
    <!-- Cash Flow Dynamics Chart -->
    <div class="col-12 col-lg-8">
        <div class="chart-card h-100">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-bar-chart-line-fill text-primary fs-5"></i>
                    <span class="fw-bold">
                        <?= is_rtl() ? 'حركة التحصيلات الشهرية مقابل المصروفات (آخر 6 أشهر)' : 'Monthly Collections vs. Expenses (Last 6 Months)' ?>
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- Chart Type Toggle -->
                    <div class="chart-toggle-pill" role="group">
                        <button type="button" class="chart-toggle-btn active" id="btnChartBar" onclick="setChartType('bar')">
                            <i class="bi bi-bar-chart-fill me-1"></i><?= is_rtl() ? 'أعمدة' : 'Bars' ?>
                        </button>
                        <button type="button" class="chart-toggle-btn" id="btnChartLine" onclick="setChartType('line')">
                            <i class="bi bi-graph-up me-1"></i><?= is_rtl() ? 'منحنى' : 'Area' ?>
                        </button>
                    </div>
                    <span class="badge bg-secondary bg-opacity-25 text-secondary px-2 py-1 rounded-pill small">
                        <?= e($selectedCurrency) ?>
                    </span>
                </div>
            </div>
            <div class="card-body p-3">
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="cashFlowChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Pipeline Stage Distribution Doughnut -->
    <div class="col-12 col-lg-4">
        <div class="chart-card h-100 d-flex flex-column">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-pie-chart-fill text-info fs-5"></i>
                    <span class="fw-bold"><?= is_rtl() ? 'توزيع خط سير الفرص البيعية' : 'Pipeline Stage Distribution' ?></span>
                </div>
                <span class="badge bg-info bg-opacity-10 text-info px-2 py-1 rounded-pill small">
                    <?= (int)($sales['active_opportunities'] ?? 0) ?> <?= is_rtl() ? 'فرصة نشطة' : 'Deals' ?>
                </span>
            </div>
            <div class="card-body d-flex flex-column justify-content-center p-3">
                <div style="position: relative; height: 260px; width: 100%;">
                    <canvas id="pipelineChart"></canvas>
                </div>
                <div class="text-center mt-2 small text-muted">
                    <?= is_rtl() ? 'إجمالي قيمة الفرص:' : 'Total Pipeline Value:' ?>
                    <strong class="text-success"><?= format_money($sales['pipeline_value'], $selectedCurrency) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 4. Strategic Operational Hubs Row -->
<div class="row g-3 mb-4">
    <!-- Hub 1: Accounts Receivable & Overdue Risk -->
    <div class="col-12 col-md-4">
        <div class="hub-card h-100 d-flex flex-column justify-content-between p-4">
            <div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="p-2 rounded-circle bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-receipt fs-5"></i>
                        </span>
                        <h6 class="fw-bold mb-0"><?= is_rtl() ? 'المستحقات والمتأخرات' : 'Receivables & Overdue' ?></h6>
                    </div>
                    <?php if ((float)$financial['total_overdue'] > 0): ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill">
                            <i class="bi bi-exclamation-circle me-1"></i><?= is_rtl() ? 'متأخرات حرجة' : 'Overdue Alert' ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill">
                            <i class="bi bi-check-circle me-1"></i><?= is_rtl() ? 'منتظمة' : 'Healthy' ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="hub-item-row">
                    <span class="text-muted small"><?= is_rtl() ? 'إجمالي فواتير مستحقة:' : 'Total Outstanding:' ?></span>
                    <span class="fw-bold text-dark"><?= format_money($financial['total_outstanding'], $selectedCurrency) ?></span>
                </div>
                <div class="hub-item-row">
                    <span class="text-muted small"><?= is_rtl() ? 'فواتير متأخرة عن موعدها:' : 'Overdue Invoices:' ?></span>
                    <span class="fw-bold text-danger"><?= format_money($financial['total_overdue'], $selectedCurrency) ?></span>
                </div>

                <!-- Overdue vs Current Ratio Bar -->
                <?php
                $outTotal = max((float)$financial['total_outstanding'], 1.0);
                $overduePct = min(100, round(((float)$financial['total_overdue'] / $outTotal) * 100));
                ?>
                <div class="mt-3">
                    <div class="d-flex justify-content-between text-muted" style="font-size: 11px;">
                        <span><?= is_rtl() ? 'نسبة المتأخر من المستحق' : 'Overdue Ratio' ?></span>
                        <span class="fw-bold <?= $overduePct > 20 ? 'text-danger' : 'text-success' ?>"><?= $overduePct ?>%</span>
                    </div>
                    <div class="mini-progress mt-1">
                        <div class="progress-bar bg-danger" style="width: <?= $overduePct ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="pt-3 mt-3 border-top">
                <a href="<?= url('finance/invoices') ?>" class="btn btn-outline-primary btn-sm w-100 rounded-pill d-flex align-items-center justify-content-center gap-2">
                    <span><?= is_rtl() ? 'عرض جدول الفواتير والمطالبات' : 'Open Invoices Ledger' ?></span>
                    <i class="bi <?= is_rtl() ? 'bi-arrow-left-short' : 'bi-arrow-right-short' ?> fs-5"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Hub 2: Sales Pipeline & Deal Velocity -->
    <div class="col-12 col-md-4">
        <div class="hub-card h-100 d-flex flex-column justify-content-between p-4">
            <div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="p-2 rounded-circle bg-success bg-opacity-10 text-success">
                            <i class="bi bi-funnel-fill fs-5"></i>
                        </span>
                        <h6 class="fw-bold mb-0"><?= is_rtl() ? 'قيمة خط سير المبيعات' : 'Sales Pipeline Velocity' ?></h6>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill">
                        <?= $sales['conversion_rate'] ?>% <?= is_rtl() ? 'إغلاق ناجح' : 'Win Rate' ?>
                    </span>
                </div>

                <div class="hub-item-row">
                    <span class="text-muted small"><?= is_rtl() ? 'إجمالي قيمة الفرص النشطة:' : 'Total Pipeline Value:' ?></span>
                    <span class="fw-bold text-success"><?= format_money($sales['pipeline_value'], $selectedCurrency) ?></span>
                </div>
                <div class="hub-item-row">
                    <span class="text-muted small"><?= is_rtl() ? 'القيمة المرجحة باحتمالية الإغلاق:' : 'Weighted Value:' ?></span>
                    <span class="fw-bold text-primary"><?= format_money($sales['weighted_pipeline_value'], $selectedCurrency) ?></span>
                </div>

                <!-- Win Rate Meter -->
                <div class="mt-3">
                    <div class="d-flex justify-content-between text-muted" style="font-size: 11px;">
                        <span><?= is_rtl() ? 'فرص رابحة / خاسرة:' : 'Won vs. Lost Deals:' ?></span>
                        <span class="fw-semibold text-dark">
                            <span class="text-success"><?= $sales['won_deals'] ?> <?= is_rtl() ? 'رابحة' : 'won' ?></span> /
                            <span class="text-danger"><?= $sales['lost_deals'] ?> <?= is_rtl() ? 'خاسرة' : 'lost' ?></span>
                        </span>
                    </div>
                    <div class="mini-progress mt-1">
                        <div class="progress-bar bg-success" style="width: <?= min(100, max(5, $sales['conversion_rate'])) ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="pt-3 mt-3 border-top">
                <a href="<?= url('crm/opportunities') ?>" class="btn btn-outline-success btn-sm w-100 rounded-pill d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-kanban"></i>
                    <span><?= is_rtl() ? 'عرض لوحة خط سير الفرص (Kanban)' : 'Open Pipeline Board' ?></span>
                </a>
            </div>
        </div>
    </div>

    <!-- Hub 3: Project Execution & Delivery -->
    <div class="col-12 col-md-4">
        <div class="hub-card h-100 d-flex flex-column justify-content-between p-4">
            <div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-kanban-fill fs-5"></i>
                        </span>
                        <h6 class="fw-bold mb-0"><?= is_rtl() ? 'تنفيذ المشاريع والمهام' : 'Project Operations' ?></h6>
                    </div>
                    <?php if ((int)$projects['overdue_tasks'] > 0): ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill">
                            <?= $projects['overdue_tasks'] ?> <?= is_rtl() ? 'مهام متأخرة' : 'Overdue' ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill">
                            <?= is_rtl() ? 'على الموعد' : 'On Track' ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="hub-item-row">
                    <span class="text-muted small"><?= is_rtl() ? 'المشاريع الجارية حالياً:' : 'Active Projects:' ?></span>
                    <span class="badge bg-primary rounded-pill px-2 py-1"><?= $projects['active_projects'] ?></span>
                </div>
                <div class="hub-item-row">
                    <span class="text-muted small"><?= is_rtl() ? 'مهام قيد العمل:' : 'In-Progress Tasks:' ?></span>
                    <span class="badge bg-info text-dark rounded-pill px-2 py-1"><?= $projects['in_progress_tasks'] ?></span>
                </div>

                <!-- Overdue Tasks Warning -->
                <div class="mt-3">
                    <div class="d-flex justify-content-between text-muted" style="font-size: 11px;">
                        <span><?= is_rtl() ? 'إجمالي المهام المسجلة:' : 'Total Tasks Tracked:' ?></span>
                        <span class="fw-bold text-dark"><?= $projects['total_tasks'] ?></span>
                    </div>
                    <div class="mini-progress mt-1">
                        <?php
                        $totalTasks = max((int)$projects['total_tasks'], 1);
                        $overdueTaskPct = min(100, round(((int)$projects['overdue_tasks'] / $totalTasks) * 100));
                        ?>
                        <div class="progress-bar <?= $overdueTaskPct > 0 ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= max(8, 100 - $overdueTaskPct) ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="pt-3 mt-3 border-top">
                <a href="<?= url('projects') ?>" class="btn btn-outline-primary btn-sm w-100 rounded-pill d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-folder2-open"></i>
                    <span><?= is_rtl() ? 'إدارة المشاريع والتكاليف' : 'Manage Projects' ?></span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 5. Executive Activity Hub & Recent Operations Desk -->
<div class="chart-card mb-4">
    <div class="card-header p-0">
        <ul class="nav nav-tabs desk-tabs px-3 border-0" id="dashDeskTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active d-flex align-items-center gap-2" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices-pane" type="button" role="tab">
                    <i class="bi bi-receipt text-primary"></i>
                    <span><?= is_rtl() ? 'أحدث الفواتير الصادرة' : 'Recent Invoices' ?></span>
                    <span class="badge bg-secondary bg-opacity-25 text-secondary rounded-pill"><?= count($recentInvoices ?? []) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link d-flex align-items-center gap-2" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments-pane" type="button" role="tab">
                    <i class="bi bi-cash-stack text-success"></i>
                    <span><?= is_rtl() ? 'أحدث التحصيلات النقدية' : 'Recent Cash Inflows' ?></span>
                    <span class="badge bg-secondary bg-opacity-25 text-secondary rounded-pill"><?= count($recentPayments ?? []) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link d-flex align-items-center gap-2" id="deals-tab" data-bs-toggle="tab" data-bs-target="#deals-pane" type="button" role="tab">
                    <i class="bi bi-funnel text-info"></i>
                    <span><?= is_rtl() ? 'أعلى الصفقات المفتوحة' : 'Top Open Opportunities' ?></span>
                    <span class="badge bg-secondary bg-opacity-25 text-secondary rounded-pill"><?= count($topOpportunities ?? []) ?></span>
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="tab-content" id="dashDeskTabsContent">
            <!-- Tab 1: Recent Invoices -->
            <div class="tab-pane fade show active" id="invoices-pane" role="tabpanel">
                <?php if (!empty($recentInvoices)): ?>
                    <div class="table-responsive">
                        <table class="table desk-table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th><?= is_rtl() ? 'رقم الفاتورة' : 'Invoice #' ?></th>
                                    <th><?= is_rtl() ? 'الشركة / العميل' : 'Client' ?></th>
                                    <th><?= is_rtl() ? 'تاريخ الاستحقاق' : 'Due Date' ?></th>
                                    <th><?= is_rtl() ? 'إجمالي المبلغ' : 'Total Amount' ?></th>
                                    <th><?= is_rtl() ? 'المسدد' : 'Paid' ?></th>
                                    <th><?= is_rtl() ? 'الحالة' : 'Status' ?></th>
                                    <th class="text-end"><?= is_rtl() ? 'إجراء' : 'Action' ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentInvoices as $inv): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url('finance/invoices/' . $inv['id']) ?>" class="fw-bold text-decoration-none">
                                                <?= e($inv['invoice_number']) ?>
                                            </a>
                                        </td>
                                        <td><?= e($inv['company_name']) ?></td>
                                        <td>
                                            <?php
                                            $isOverdue = (!in_array($inv['status'], ['paid', 'void'], true) && strtotime($inv['due_date']) < time());
                                            ?>
                                            <span class="<?= $isOverdue ? 'text-danger fw-bold' : 'text-muted' ?>">
                                                <?= e(format_date($inv['due_date'])) ?>
                                                <?php if ($isOverdue): ?>
                                                    <i class="bi bi-exclamation-triangle ms-1"></i>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold"><?= format_money($inv['total_amount'], $selectedCurrency) ?></td>
                                        <td class="text-success"><?= format_money($inv['paid_amount'], $selectedCurrency) ?></td>
                                        <td>
                                            <?php
                                            $statusMap = [
                                                'paid' => ['bg-success-subtle text-success border border-success-subtle', is_rtl() ? 'مسددة' : 'Paid'],
                                                'partially_paid' => ['bg-info-subtle text-info border border-info-subtle', is_rtl() ? 'مسددة جزئياً' : 'Partial'],
                                                'unpaid' => ['bg-warning-subtle text-warning border border-warning-subtle', is_rtl() ? 'غير مسددة' : 'Unpaid'],
                                                'overdue' => ['bg-danger-subtle text-danger border border-danger-subtle', is_rtl() ? 'متأخرة' : 'Overdue'],
                                                'draft' => ['bg-secondary-subtle text-secondary border border-secondary-subtle', is_rtl() ? 'مسودة' : 'Draft'],
                                                'void' => ['bg-dark-subtle text-dark border border-dark-subtle', is_rtl() ? 'ملغاة' : 'Void'],
                                            ];
                                            $st = $statusMap[$inv['status']] ?? ['bg-light text-dark', $inv['status']];
                                            ?>
                                            <span class="badge <?= $st[0] ?> rounded-pill px-2 py-1"><?= $st[1] ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('finance/invoices/' . $inv['id']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-receipt text-secondary opacity-50 fs-1 d-block mb-2"></i>
                        <p class="mb-2"><?= is_rtl() ? 'لا توجد فواتير مسجلة حالياً بالعملة المحددة' : 'No invoices recorded in this currency yet.' ?></p>
                        <a href="<?= url('finance/invoices/create') ?>" class="btn btn-sm btn-primary rounded-pill">
                            <i class="bi bi-plus-lg me-1"></i><?= is_rtl() ? 'إصدار أول فاتورة' : 'Create First Invoice' ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab 2: Recent Payments -->
            <div class="tab-pane fade" id="payments-pane" role="tabpanel">
                <?php if (!empty($recentPayments)): ?>
                    <div class="table-responsive">
                        <table class="table desk-table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th><?= is_rtl() ? 'رقم الإيصال' : 'Payment #' ?></th>
                                    <th><?= is_rtl() ? 'الشركة / العميل' : 'Client' ?></th>
                                    <th><?= is_rtl() ? 'الحساب المستلم' : 'Cash Account' ?></th>
                                    <th><?= is_rtl() ? 'طريقة السداد' : 'Method' ?></th>
                                    <th><?= is_rtl() ? 'التاريخ' : 'Payment Date' ?></th>
                                    <th><?= is_rtl() ? 'المبلغ' : 'Amount' ?></th>
                                    <th><?= is_rtl() ? 'الحالة' : 'Status' ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPayments as $pay): ?>
                                    <tr>
                                        <td class="fw-bold"><?= e($pay['payment_number']) ?></td>
                                        <td><?= e($pay['company_name']) ?></td>
                                        <td>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                <i class="bi bi-bank me-1"></i><?= e($pay['account_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted small text-capitalize"><?= str_replace('_', ' ', e($pay['payment_method'])) ?></span>
                                        </td>
                                        <td class="text-muted"><?= e(format_date($pay['payment_date'])) ?></td>
                                        <td class="fw-bold text-success"><?= format_money($pay['amount'], $selectedCurrency) ?></td>
                                        <td>
                                            <?php if ($pay['status'] === 'completed'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                                    <?= is_rtl() ? 'مكتمل' : 'Completed' ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">
                                                    <?= is_rtl() ? 'معكوس' : 'Reversed' ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-cash-stack text-secondary opacity-50 fs-1 d-block mb-2"></i>
                        <p class="mb-0"><?= is_rtl() ? 'لا توجد دفعات محصلة مسجلة بعد' : 'No recorded payments in this currency yet.' ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab 3: Top Open Opportunities -->
            <div class="tab-pane fade" id="deals-pane" role="tabpanel">
                <?php if (!empty($topOpportunities)): ?>
                    <div class="table-responsive">
                        <table class="table desk-table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th><?= is_rtl() ? 'اسم الفرصة' : 'Opportunity' ?></th>
                                    <th><?= is_rtl() ? 'الشركة' : 'Company' ?></th>
                                    <th><?= is_rtl() ? 'المرحلة' : 'Stage' ?></th>
                                    <th><?= is_rtl() ? 'احتمالية الفوز' : 'Probability' ?></th>
                                    <th><?= is_rtl() ? 'القيمة المتوقعة' : 'Estimated Value' ?></th>
                                    <th><?= is_rtl() ? 'الإغلاق المتوقع' : 'Target Close' ?></th>
                                    <th class="text-end"><?= is_rtl() ? 'إجراء' : 'Action' ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topOpportunities as $opp): ?>
                                    <tr>
                                        <td class="fw-bold"><?= e($opp['title']) ?></td>
                                        <td><?= e($opp['company_name']) ?></td>
                                        <td>
                                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-1 text-capitalize">
                                                <?= e($opp['stage']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2" style="min-width: 100px;">
                                                <div class="mini-progress flex-grow-1">
                                                    <div class="progress-bar bg-success" style="width: <?= (int)$opp['probability'] ?>%"></div>
                                                </div>
                                                <span class="small fw-semibold"><?= (int)$opp['probability'] ?>%</span>
                                            </div>
                                        </td>
                                        <td class="fw-bold text-success"><?= format_money($opp['estimated_value'], $selectedCurrency) ?></td>
                                        <td class="text-muted"><?= e(format_date($opp['expected_close_date'])) ?></td>
                                        <td class="text-end">
                                            <a href="<?= url('crm/opportunities') ?>" class="btn btn-sm btn-outline-success rounded-pill py-0 px-2">
                                                <i class="bi bi-arrow-right-short"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-funnel text-secondary opacity-50 fs-1 d-block mb-2"></i>
                        <p class="mb-2"><?= is_rtl() ? 'لا توجد صفقات مفتوحة حالياً بالعملة المحددة' : 'No open opportunities in this currency.' ?></p>
                        <a href="<?= url('crm/opportunities/create') ?>" class="btn btn-sm btn-success rounded-pill">
                            <i class="bi bi-plus-lg me-1"></i><?= is_rtl() ? 'إضافة فرصة جديدة' : 'Add Opportunity' ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 6. Reactive Chart Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.04)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    // 1. Cash Flow Dynamics Chart
    const cashCtx = document.getElementById('cashFlowChart').getContext('2d');

    // Create subtle canvas gradients for bars & areas
    const collGradient = cashCtx.createLinearGradient(0, 0, 0, 300);
    collGradient.addColorStop(0, 'rgba(16, 185, 129, 0.85)');
    collGradient.addColorStop(1, 'rgba(16, 185, 129, 0.25)');

    const expGradient = cashCtx.createLinearGradient(0, 0, 0, 300);
    expGradient.addColorStop(0, 'rgba(239, 68, 68, 0.85)');
    expGradient.addColorStop(1, 'rgba(239, 68, 68, 0.25)');

    const chartLabels = <?= json_encode($chartData['labels']) ?>;
    const collData = <?= json_encode($chartData['collections']) ?>;
    const expData = <?= json_encode($chartData['expenses']) ?>;

    window.cashFlowChartInstance = new Chart(cashCtx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [
                {
                    label: '<?= is_rtl() ? "التحصيلات النقدية" : "Collections" ?>',
                    data: collData,
                    backgroundColor: collGradient,
                    borderColor: '#10b981',
                    borderWidth: 1.5,
                    borderRadius: 8,
                    fill: true,
                    tension: 0.35,
                },
                {
                    label: '<?= is_rtl() ? "المصروفات التشغيلية" : "Expenses" ?>',
                    data: expData,
                    backgroundColor: expGradient,
                    borderColor: '#ef4444',
                    borderWidth: 1.5,
                    borderRadius: 8,
                    fill: true,
                    tension: 0.35,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        color: textColor,
                        usePointStyle: true,
                        boxWidth: 8,
                        boxHeight: 8,
                        font: { size: 12, weight: '600' }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.92)',
                    titleColor: '#ffffff',
                    bodyColor: '#e2e8f0',
                    padding: 12,
                    cornerRadius: 8,
                    boxPadding: 4,
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            label += Number(context.parsed.y).toLocaleString() + ' <?= e($selectedCurrency) ?>';
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: textColor, font: { size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor, drawBorder: false },
                    ticks: {
                        color: textColor,
                        font: { size: 11 },
                        callback: function (val) {
                            if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                            if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
                            return Number(val).toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Chart Type Toggle Functionality
    window.setChartType = function(type) {
        if (!window.cashFlowChartInstance) return;
        window.cashFlowChartInstance.config.type = type;
        window.cashFlowChartInstance.update();

        document.getElementById('btnChartBar').classList.toggle('active', type === 'bar');
        document.getElementById('btnChartLine').classList.toggle('active', type === 'line');
    };

    // 2. Sales Pipeline Stage Doughnut Chart
    const pCtx = document.getElementById('pipelineChart').getContext('2d');
    const pipelineData = <?= json_encode($pipelineChart) ?>;
    const pLabels = pipelineData.map(d => d.stage.toUpperCase());
    const pCounts = pipelineData.map(d => Number(d.total_count));

    const pipelinePalette = [
        '#64748b', '#06b6d4', '#3b82f6', '#f59e0b', '#8b5cf6', '#334155', '#10b981', '#ef4444'
    ];

    window.pipelineChartInstance = new Chart(pCtx, {
        type: 'doughnut',
        data: {
            labels: pLabels.length > 0 ? pLabels : ['No Deals'],
            datasets: [{
                data: pCounts.length > 0 ? pCounts : [1],
                backgroundColor: pipelinePalette,
                borderWidth: isDark ? 2 : 2,
                borderColor: isDark ? '#1e293b' : '#ffffff',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: textColor,
                        usePointStyle: true,
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 12,
                        font: { size: 11, weight: '500' }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.92)',
                    titleColor: '#ffffff',
                    bodyColor: '#e2e8f0',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            const val = context.parsed;
                            return ' ' + context.label + ': ' + val + ' <?= is_rtl() ? "فرص" : "deals" ?>';
                        }
                    }
                }
            }
        }
    });

    // Theme Switch Observer to automatically update charts
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.attributeName === 'data-bs-theme') {
                const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
                const newGrid = dark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.04)';
                const newText = dark ? '#94a3b8' : '#64748b';

                if (window.cashFlowChartInstance) {
                    window.cashFlowChartInstance.options.scales.x.ticks.color = newText;
                    window.cashFlowChartInstance.options.scales.y.ticks.color = newText;
                    window.cashFlowChartInstance.options.scales.y.grid.color = newGrid;
                    window.cashFlowChartInstance.options.plugins.legend.labels.color = newText;
                    window.cashFlowChartInstance.update();
                }

                if (window.pipelineChartInstance) {
                    window.pipelineChartInstance.options.plugins.legend.labels.color = newText;
                    window.pipelineChartInstance.data.datasets[0].borderColor = dark ? '#1e293b' : '#ffffff';
                    window.pipelineChartInstance.update();
                }
            }
        });
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
});
</script>
