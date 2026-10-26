<!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? __('auth.login')) ?> — <?= e(config('app.name')) ?></title>
    <link rel="icon" type="image/png" href="<?= asset('images/logo.png') ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if (is_rtl()): ?>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php else: ?>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>

    <!-- Bootstrap 5 CSS -->
    <?php if (is_rtl()): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <?php endif; ?>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --font-family: <?= is_rtl() ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>;
            --primary-color: #0d6efd;
            --primary-hover: #0b5ed7;
        }
        body {
            font-family: var(--font-family);
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f8fafc;
        }
        .auth-card {
            background: #ffffff;
            color: #1e293b;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .auth-header {
            background: #f8fafc;
            padding: 30px 30px 20px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }
        .auth-body {
            padding: 30px;
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.875rem;
            margin-bottom: 12px;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
            border-color: #0d6efd;
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 d-flex justify-content-center">
            <?= $content ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
