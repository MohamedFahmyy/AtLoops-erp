<?php
$user = auth_user() ?? ['name' => 'User', 'email' => ''];
?>
<header class="topbar">
    <div class="topbar-left d-flex align-items-center gap-3">
        <button class="btn btn-outline-secondary btn-sm d-lg-none" id="sidebarToggle" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div class="page-title fw-bold text-dark fs-5 mb-0">
            <?= e($pageTitle ?? __('common.dashboard')) ?>
        </div>
    </div>

    <div class="topbar-right d-flex align-items-center gap-3">
        <!-- Language Switcher -->
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-translate"></i>
                <span><?= is_rtl() ? 'العربية' : 'English' ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><a class="dropdown-item <?= is_rtl() ? 'active' : '' ?>" href="<?= url('lang?lang=ar') ?>">العربية (RTL)</a></li>
                <li><a class="dropdown-item <?= !is_rtl() ? 'active' : '' ?>" href="<?= url('lang?lang=en') ?>">English (LTR)</a></li>
            </ul>
        </div>

        <!-- Dark/Light Mode Switcher -->
        <button class="btn btn-sm btn-outline-secondary" id="themeToggle" title="<?= e(__('common.dark_mode')) ?>">
            <i class="bi bi-moon-stars" id="themeIcon"></i>
        </button>

        <!-- User Menu -->
        <div class="dropdown">
            <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2 py-1 px-2" type="button" data-bs-toggle="dropdown">
                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 13px;">
                    <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="text-start d-none d-md-block" style="line-height: 1.2;">
                    <span class="d-block fw-semibold text-dark small"><?= e($user['name']) ?></span>
                    <span class="d-block text-muted" style="font-size: 10px;"><?= e($user['job_title'] ?? 'Staff') ?></span>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li class="px-3 py-2 text-muted border-bottom small">
                    <b><?= e($user['name']) ?></b><br>
                    <span><?= e($user['email']) ?></span>
                </li>
                <li>
                    <form action="<?= url('logout') ?>" method="POST" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="dropdown-item text-danger py-2 d-flex align-items-center gap-2">
                            <i class="bi bi-box-arrow-right"></i>
                            <span><?= e(__('common.logout')) ?></span>
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
