<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\CSRF;
use App\Core\Response;

class CSRFMiddleware
{
    public function handle(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $_POST['_csrf_token']
                ?? $_SERVER['HTTP_X_CSRF_TOKEN']
                ?? null;

            // Also check raw json body if payload is JSON
            if (!$token) {
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (str_contains($contentType, 'application/json')) {
                    $raw = file_get_contents('php://input');
                    $json = json_decode($raw, true);
                    $token = $json['_csrf_token'] ?? null;
                }
            }

            if (!CSRF::validate($token)) {
                if ($this->isAjax()) {
                    Response::json(['error' => 'CSRF token mismatch or expired. Please refresh the page.'], 419);
                }
                Response::redirect($_SERVER['HTTP_REFERER'] ?? url('/'), [
                    'error' => __('auth.csrf_mismatch')
                ]);
            }
        }
    }

    private function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));
    }
}
