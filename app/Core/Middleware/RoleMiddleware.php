<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Response;

class RoleMiddleware
{
    private array $roles;

    public function __construct(string ...$roles)
    {
        $this->roles = $roles;
    }

    public function handle(): void
    {
        if (!Auth::check() || !Auth::hasRole(...$this->roles)) {
            if ($this->isAjax()) {
                Response::json(['error' => 'Forbidden: insufficient role privileges'], 403);
            }
            Response::redirect(url('dashboard'), ['error' => __('auth.forbidden')]);
        }
    }

    private function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));
    }
}
