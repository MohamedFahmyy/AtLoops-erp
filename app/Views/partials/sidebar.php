<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
if ($scriptName !== '/' && $scriptName !== '\\' && str_starts_with($currentPath, $scriptName)) {
    $currentPath = substr($currentPath, strlen($scriptName));
}
$currentPath = '/' . ltrim($currentPath, '/');
?>
<aside class="sidebar" id="appSidebar">
    <div class="sidebar-header">
        <a href="<?= url('dashboard') ?>" class="brand-link">
            <div class="brand-logo-wrap">
                <img src="<?= asset('images/logo.png') ?>" alt="At Loops">
            </div>
            <div class="brand-text">
                <span class="brand-title">At Loops</span>
                <span class="brand-sub">BUSINESS OS</span>
            </div>
        </a>
    </div>

    <div class="sidebar-menu">
        <div class="menu-label"><?= is_rtl() ? 'الرئيسية والتحليلات' : 'Core & Analytics' ?></div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $currentPath === '/' || $currentPath === '/dashboard' ? 'active' : '' ?>" href="<?= url('dashboard') ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span><?= e(__('common.dashboard')) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with($currentPath, '/reports') ? 'active' : '' ?>" href="<?= url('reports') ?>">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                    <span><?= e(__('common.reports')) ?></span>
                </a>
            </li>
        </ul>

        <div class="menu-label"><?= is_rtl() ? 'المبيعات والعملاء' : 'Sales & CRM' ?></div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with($currentPath, '/crm/companies') ? 'active' : '' ?>" href="<?= url('crm/companies') ?>">
                    <i class="bi bi-buildings-fill"></i>
                    <span><?= e(__('common.companies')) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with($currentPath, '/crm/opportunities') ? 'active' : '' ?>" href="<?= url('crm/opportunities') ?>">
                    <i class="bi bi-funnel-fill"></i>
                    <span><?= e(__('common.opportunities')) ?></span>
                </a>
            </li>
        </ul>

        <div class="menu-label"><?= is_rtl() ? 'العمليات والمشاريع' : 'Operations' ?></div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with($currentPath, '/projects') ? 'active' : '' ?>" href="<?= url('projects') ?>">
                    <i class="bi bi-kanban-fill"></i>
                    <span><?= e(__('common.projects')) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with($currentPath, '/recurring') ? 'active' : '' ?>" href="<?= url('recurring') ?>">
                    <i class="bi bi-arrow-repeat"></i>
                    <span><?= e(__('common.recurring')) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with($currentPath, '/documents') ? 'active' : '' ?>" href="<?= url('documents') ?>">
                    <i class="bi bi-folder-fill"></i>
                    <span><?= e(__('common.documents')) ?></span>
                </a>
            </li>
        </ul>

        <div class="menu-label"><?= is_rtl() ? 'الإدارة المالية' : 'Financials' ?></div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with($currentPath, '/finance/invoices') ? 'active' : '' ?>" href="<?= url('finance/invoices') ?>">
                    <i class="bi bi-receipt-cutoff"></i>
                    <span><?= e(__('common.invoices')) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with($currentPath, '/finance/expenses') ? 'active' : '' ?>" href="<?= url('finance/expenses') ?>">
                    <i class="bi bi-wallet2"></i>
                    <span><?= e(__('common.expenses')) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with($currentPath, '/finance/accounts') ? 'active' : '' ?>" href="<?= url('finance/accounts') ?>">
                    <i class="bi bi-bank2"></i>
                    <span><?= e(__('common.cash_accounts')) ?></span>
                </a>
            </li>
        </ul>
    </div>
</aside>
