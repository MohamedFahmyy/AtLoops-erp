<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>404 — الصفحة غير موجودة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; background-color: #0f172a; color: #f8fafc; height: 100vh; display: flex; align-items: center; justify-content: center; }</style>
</head>
<body class="text-center">
    <div class="card p-5 bg-white text-dark shadow-lg rounded-4" style="max-width: 480px;">
        <h1 class="display-1 fw-bold text-primary mb-2">404</h1>
        <h4 class="fw-bold mb-3">الصفحة غير موجودة</h4>
        <p class="text-muted mb-4">عذراً، المسار الذي تحاول الوصول إليه غير موجود أو تم نقله.</p>
        <a href="<?= url('dashboard') ?>" class="btn btn-primary fw-bold py-2">العودة إلى لوحة القيادة</a>
    </div>
</body>
</html>
