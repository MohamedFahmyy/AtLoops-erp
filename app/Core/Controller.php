<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /**
     * Render a view file inside an optional layout.
     */
    protected function render(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        extract($data);

        $viewFile = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View file [{$viewFile}] not found.");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout !== null) {
            $layoutFile = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $layout) . '.php';
            if (!file_exists($layoutFile)) {
                throw new \RuntimeException("Layout file [{$layoutFile}] not found.");
            }
            require $layoutFile;
        } else {
            echo $content;
        }
        exit;
    }

    /**
     * Send a JSON response.
     */
    protected function json(array $data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    /**
     * Validate incoming request parameters against rules.
     */
    protected function validate(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);

        if (!$validator->validate()) {
            Session::setOldInput($data);

            if ($this->isAjax()) {
                Response::json([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            Session::setFlash('errors', $validator->errors());
            Session::setFlash('error', __('validation.failed_check_form'));

            $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
            Response::redirect($referer);
        }

        return $data;
    }

    /**
     * Redirect to a specific URL with optional flash messages.
     */
    protected function redirect(string $url, array $flash = []): void
    {
        Response::redirect($url, $flash);
    }

    /**
     * Require the current user to be authenticated.
     */
    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            if ($this->isAjax()) {
                Response::json(['error' => 'Unauthenticated.'], 401);
            }
            Response::redirect(url('login'), ['error' => 'Please log in to continue.']);
        }
    }

    /**
     * Require the user to possess a specific permission.
     */
    protected function requirePermission(string $permission): void
    {
        $this->requireAuth();

        if (!Auth::can($permission)) {
            $this->forbidden("Access denied: You do not possess the required permission [{$permission}].");
        }
    }

    /**
     * Require the user to have at least one of the specified roles.
     */
    protected function requireRole(string ...$roles): void
    {
        $this->requireAuth();

        if (!Auth::hasRole(...$roles)) {
            $this->forbidden('Access denied: Your account role is not authorized for this resource.');
        }
    }

    /**
     * Render a standard 403 Forbidden response.
     */
    protected function forbidden(string $message = 'Access Forbidden'): void
    {
        http_response_code(403);

        if ($this->isAjax()) {
            Response::json(['error' => $message], 403);
        }

        $errorView = dirname(__DIR__) . '/Views/errors/403.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo "<h1>403 Forbidden</h1><p>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>";
        }
        exit;
    }

    /**
     * Redirect back to referer.
     */
    protected function redirectBack(array $flash = []): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
        Response::redirect($referer, $flash);
    }

    /**
     * Detect AJAX or JSON API requests.
     */
    protected function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));
    }
}
