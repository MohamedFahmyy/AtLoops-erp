<?php

declare(strict_types=1);

namespace App\Core;

class CSRF
{
    private const SESSION_KEY = '_csrf_token';

    public static function getToken(): string
    {
        Session::start();
        $token = Session::get(self::SESSION_KEY);
        if (!$token || !is_string($token)) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }
        return $token;
    }

    public static function validate(?string $token): bool
    {
        Session::start();
        $sessionToken = Session::get(self::SESSION_KEY);
        if (!$sessionToken || !$token) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }

    public static function regenerate(): string
    {
        Session::start();
        $token = bin2hex(random_bytes(32));
        Session::set(self::SESSION_KEY, $token);
        return $token;
    }
}
