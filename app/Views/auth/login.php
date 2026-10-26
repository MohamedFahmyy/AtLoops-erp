<div class="auth-card">
    <div class="auth-header">
        <div class="mb-3 text-center">
            <img src="<?= asset('images/logo.png') ?>" alt="At Loops" style="height: 76px; width: auto; object-fit: contain;">
        </div>
        <div class="brand-badge">
            <i class="bi bi-shield-check text-primary"></i> Business OS
        </div>
        <h4 class="fw-bold mb-1"><?= e(__('auth.login_title')) ?></h4>
        <p class="text-muted small mb-0"><?= e(__('auth.login_subtitle')) ?></p>
    </div>

    <div class="auth-body">
        <?php if ($flashError = flash('error')): ?>
            <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 ms-2"></i>
                <div><?= e($flashError) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($flashSuccess = flash('success')): ?>
            <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                <i class="bi bi-check-circle-fill me-2 ms-2"></i>
                <div><?= e($flashSuccess) ?></div>
            </div>
        <?php endif; ?>

        <form action="<?= url('login') ?>" method="POST" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="email" class="form-label fw-semibold"><?= e(__('auth.email')) ?></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email"
                           class="form-control <?= flash('errors')['email'] ?? false ? 'is-invalid' : '' ?>"
                           id="email"
                           name="email"
                           value="<?= e(old('email', 'admin@atloops.com')) ?>"
                           placeholder="name@atloops.com"
                           required
                           autofocus>
                </div>
                <?php if (isset(flash('errors')['email'])): ?>
                    <div class="text-danger small mt-1"><?= e(flash('errors')['email'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold"><?= e(__('auth.password')) ?></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                    <input type="password"
                           class="form-control <?= flash('errors')['password'] ?? false ? 'is-invalid' : '' ?>"
                           id="password"
                           name="password"
                           value="Password123!"
                           placeholder="••••••••"
                           required>
                </div>
                <?php if (isset(flash('errors')['password'])): ?>
                    <div class="text-danger small mt-1"><?= e(flash('errors')['password'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary py-2 fw-bold">
                    <i class="bi bi-box-arrow-in-right me-1 ms-1"></i> <?= e(__('auth.login')) ?>
                </button>
            </div>
        </form>

        <div class="p-3 bg-light rounded text-center small text-muted border">
            <i class="bi bi-info-circle me-1"></i>
            <?= is_rtl() ? 'بيانات الدخول الافتراضية للتجربة:' : 'Demo Default Credentials:' ?><br>
            <code>admin@atloops.com</code> / <code>Password123!</code>
        </div>
    </div>
</div>
