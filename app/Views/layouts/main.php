<!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle ?? __('common.dashboard')) ?> — <?= e(config('app.name')) ?></title>
    <link rel="icon" type="image/png" href="<?= asset('images/logo.png') ?>">

    <!-- Google Fonts: Cairo for Arabic, Inter for English -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if (is_rtl()): ?>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php else: ?>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>

    <!-- Bootstrap 5 CSS (RTL or LTR) -->
    <?php if (is_rtl()): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <?php endif; ?>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom Enterprise Theme Styles -->
    <style>
        :root {
            --font-family: <?= is_rtl() ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>;
            --sidebar-width: 260px;
            --topbar-height: 64px;
            --primary-color: #0d6efd;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            <?= is_rtl() ? 'right: 0;' : 'left: 0;' ?>
            background-color: var(--sidebar-bg);
            color: #94a3b8;
            z-index: 1050;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 18px 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #ffffff;
        }

        .brand-logo-wrap {
            width: 38px;
            height: 38px;
            background: #ffffff;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
            flex-shrink: 0;
            overflow: hidden;
        }

        .brand-logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 18px;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .brand-title {
            font-weight: 800;
            font-size: 15px;
            letter-spacing: 0.5px;
        }

        .brand-sub {
            font-size: 9px;
            color: #60a5fa;
            letter-spacing: 1px;
            font-weight: 700;
        }

        .sidebar-menu {
            padding: 16px 12px;
        }

        .menu-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            padding: 12px 14px 6px;
        }

        .sidebar .nav-link {
            color: #cbd5e1;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link i {
            font-size: 16px;
        }

        .sidebar .nav-link:hover {
            color: #ffffff;
            background-color: var(--sidebar-hover);
        }

        .sidebar .nav-link.active {
            color: #ffffff;
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        /* Topbar & Content Styles */
        .main-wrapper {
            <?= is_rtl() ? 'margin-right: var(--sidebar-width);' : 'margin-left: var(--sidebar-width);' ?>
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin 0.3s ease;
        }

        .topbar {
            height: var(--topbar-height);
            background: var(--bs-body-bg);
            border-bottom: 1px solid var(--bs-border-color);
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1040;
        }

        .content-area {
            padding: 24px;
            flex: 1;
        }

        /* Card Enhancements */
        .card {
            border: 1px solid var(--bs-border-color);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--bs-border-color);
            padding: 16px 20px;
            font-weight: 700;
        }

        .metric-card {
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .metric-icon {
            position: absolute;
            <?= is_rtl() ? 'left: 16px;' : 'right: 16px;' ?>
            top: 16px;
            font-size: 32px;
            opacity: 0.15;
        }

        /* Mobile Responsive */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: <?= is_rtl() ? 'translateX(100%)' : 'translateX(-100%)' ?>;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-wrapper {
                margin-right: 0 !important;
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar Include -->
<?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

<!-- Main Wrapper -->
<div class="main-wrapper">
    <!-- Topbar Include -->
    <?php require_once dirname(__DIR__) . '/partials/topbar.php'; ?>

    <!-- Flash Toasts Include -->
    <?php require_once dirname(__DIR__) . '/partials/toasts.php'; ?>

    <!-- Page Content -->
    <main class="content-area">
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="py-3 px-4 border-top text-center text-muted small bg-body">
        &copy; <?= date('Y') ?> <b>At Loops</b> For Software & Digital Transformation. All rights reserved.
    </footer>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
    // Theme Switcher (Dark/Light)
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const htmlElem = document.documentElement;

    function applyTheme(theme) {
        htmlElem.setAttribute('data-bs-theme', theme);
        localStorage.setItem('atloops_theme', theme);
        if (theme === 'dark') {
            themeIcon.className = 'bi bi-sun';
        } else {
            themeIcon.className = 'bi bi-moon-stars';
        }
    }

    const savedTheme = localStorage.getItem('atloops_theme') || 'light';
    applyTheme(savedTheme);

    themeToggle?.addEventListener('click', () => {
        const current = htmlElem.getAttribute('data-bs-theme');
        applyTheme(current === 'dark' ? 'light' : 'dark');
    });

    // Mobile Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('appSidebar');

    sidebarToggle?.addEventListener('click', () => {
        sidebar.classList.toggle('show');
    });

    // Close sidebar on click outside on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });

    // CSRF helper for Fetch API
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }
</script>

</body>
</html>
