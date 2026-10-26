<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Response;

class AuthMiddleware
{
    public function handle(): void
    {
        if (!Auth::check()) {
            if ($this->isAjax()) {
                Response::json(['error' => 'Unauthenticated'], 401);
            }
            Response::redirect(url('login'), ['error' => __('auth.unauthenticated')]);
        }
    }

    private function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));
    }
}
