<?php

declare(strict_types=1);

namespace App\Core;

class RateLimiter
{
    private static function getStoragePath(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache/rate_limits';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private static function getFile(string $key): string
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $key);
        return self::getStoragePath() . '/' . md5($safeKey) . '.json';
    }

    public static function hit(string $key, int $decaySeconds = 300): int
    {
        $file = self::getFile($key);
        $data = ['count' => 0, 'expires_at' => time() + $decaySeconds];

        if (file_exists($file)) {
            $content = file_get_contents($file);
            $parsed = $content ? json_decode($content, true) : null;
            if ($parsed && ($parsed['expires_at'] ?? 0) > time()) {
                $data = $parsed;
            }
        }

        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);

        return $data['count'];
    }

    public static function tooManyAttempts(string $key, int $maxAttempts = 5): bool
    {
        $file = self::getFile($key);
        if (!file_exists($file)) {
            return false;
        }

        $content = file_get_contents($file);
        $parsed = $content ? json_decode($content, true) : null;
        if (!$parsed || ($parsed['expires_at'] ?? 0) <= time()) {
            @unlink($file);
            return false;
        }

        return ($parsed['count'] ?? 0) >= $maxAttempts;
    }

    public static function availableIn(string $key): int
    {
        $file = self::getFile($key);
        if (!file_exists($file)) {
            return 0;
        }

        $content = file_get_contents($file);
        $parsed = $content ? json_decode($content, true) : null;
        if (!$parsed) {
            return 0;
        }

        return max(0, ($parsed['expires_at'] ?? 0) - time());
    }

    public static function clear(string $key): void
    {
        $file = self::getFile($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}
