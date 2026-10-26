<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\I18n;
use App\Core\Session;

if (!function_exists('app')) {
    function app(): \App\Core\Application
    {
        return \App\Core\Application::getInstance();
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return \App\Core\Application::getInstance()->getConfig($key, $default);
    }
}

if (!function_exists('__')) {
    function __(string $key, array $replace = []): string
    {
        return I18n::trans($key, $replace);
    }
}

if (!function_exists('current_locale')) {
    function current_locale(): string
    {
        return I18n::getLocale();
    }
}

if (!function_exists('is_rtl')) {
    function is_rtl(): bool
    {
        return I18n::isRtl();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return CSRF::getToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('auth_check')) {
    function auth_check(): bool
    {
        return Auth::check();
    }
}

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        return Auth::can($permission);
    }
}

if (!function_exists('has_role')) {
    function has_role(string ...$roles): bool
    {
        return Auth::hasRole(...$roles);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return Session::getOldInput($key, $default);
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $default = null): mixed
    {
        return Session::getFlash($key, $default);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
            $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            if ($scriptDir !== '/' && $scriptDir !== '.' && $scriptDir !== '') {
                $baseUrl .= $scriptDir;
            }
        } else {
            $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('format_money')) {
    function format_money(mixed $amount, string $currency = 'EGP', int $decimals = 2): string
    {
        $amount = (float)($amount ?? 0);
        $formatted = number_format($amount, $decimals, '.', ',');
        return is_rtl() ? "{$formatted} {$currency}" : "{$currency} {$formatted}";
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'Y-m-d'): string
    {
        if (empty($date)) {
            return '—';
        }
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : $date;
    }
}
