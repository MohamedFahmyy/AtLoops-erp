<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (php_sapi_name() === 'cli') {
            if (!isset($_SESSION)) {
                $_SESSION = [];
            }
            self::$started = true;
            return;
        }

        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $sessionConfig = config('app.session', []);
        $lifetime = $sessionConfig['lifetime'] ?? 7200;
        $secure = $sessionConfig['secure'] ?? false;
        $cookieName = $sessionConfig['cookie_name'] ?? 'atloops_session';

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');

        session_name($cookieName);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::$started = true;

        // Session expiration / inactivity check
        $now = time();
        if (isset($_SESSION['_last_activity']) && ($now - $_SESSION['_last_activity'] > $lifetime)) {
            self::destroy();
            session_start();
            self::$started = true;
        }
        $_SESSION['_last_activity'] = $now;

        // Prepare flash messages
        self::ageFlashData();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function regenerate(bool $deleteOld = true): void
    {
        if (php_sapi_name() === 'cli') {
            return;
        }
        self::start();
        session_regenerate_id($deleteOld);
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();
            self::$started = false;
        }
    }

    public static function setFlash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['_flash']['new'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION['_flash']['old'][$key] ?? $default;
    }

    public static function hasFlash(string $key): bool
    {
        self::start();
        return isset($_SESSION['_flash']['old'][$key]);
    }

    public static function setOldInput(array $input): void
    {
        self::start();
        // Exclude passwords and tokens
        unset($input['password'], $input['password_confirmation'], $input['_csrf_token']);
        $_SESSION['_old_input'] = $input;
    }

    public static function getOldInput(string $key, mixed $default = ''): mixed
    {
        self::start();
        return $_SESSION['_old_input'][$key] ?? $default;
    }

    public static function clearOldInput(): void
    {
        self::start();
        unset($_SESSION['_old_input']);
    }

    private static function ageFlashData(): void
    {
        $_SESSION['_flash']['old'] = $_SESSION['_flash']['new'] ?? [];
        $_SESSION['_flash']['new'] = [];
    }
}
