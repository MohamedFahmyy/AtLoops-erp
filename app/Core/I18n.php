<?php

declare(strict_types=1);

namespace App\Core;

class I18n
{
    private static ?string $currentLocale = null;
    /** @var array<string, array<string, string>> */
    private static array $translations = [];

    public static function getLocale(): string
    {
        if (self::$currentLocale !== null) {
            return self::$currentLocale;
        }

        // 1. Session check
        $sessionLocale = Session::get('locale');
        if ($sessionLocale && self::isValidLocale($sessionLocale)) {
            self::$currentLocale = $sessionLocale;
            return self::$currentLocale;
        }

        // 2. Cookie check
        $cookieLocale = $_COOKIE['atloops_locale'] ?? null;
        if ($cookieLocale && self::isValidLocale($cookieLocale)) {
            self::$currentLocale = $cookieLocale;
            Session::set('locale', $cookieLocale);
            return self::$currentLocale;
        }

        // 3. Fallback default
        self::$currentLocale = config('app.default_locale', 'ar');
        return self::$currentLocale;
    }

    public static function setLocale(string $locale): void
    {
        if (self::isValidLocale($locale)) {
            self::$currentLocale = $locale;
            Session::set('locale', $locale);
            if (php_sapi_name() !== 'cli' && !headers_sent()) {
                setcookie('atloops_locale', $locale, [
                    'expires' => time() + (86400 * 365),
                    'path' => '/',
                    'httponly' => false,
                    'samesite' => 'Lax',
                ]);
            }
        }
    }

    public static function isValidLocale(string $locale): bool
    {
        $supported = config('app.locales', ['ar' => [], 'en' => []]);
        return array_key_exists($locale, $supported);
    }

    public static function isRtl(): bool
    {
        $locale = self::getLocale();
        $localesConfig = config('app.locales', []);
        return ($localesConfig[$locale]['dir'] ?? 'rtl') === 'rtl';
    }

    public static function trans(string $key, array $replace = []): string
    {
        $locale = self::getLocale();
        self::loadLocale($locale);

        $keys = explode('.', $key);
        $group = array_shift($keys);

        $value = self::$translations[$locale][$group] ?? null;
        if (is_array($value)) {
            foreach ($keys as $segment) {
                if (is_array($value) && isset($value[$segment])) {
                    $value = $value[$segment];
                } else {
                    $value = null;
                    break;
                }
            }
        }

        if ($value === null || !is_string($value)) {
            // Fallback to default English if missing in current locale
            $fallbackLocale = config('app.fallback_locale', 'en');
            if ($locale !== $fallbackLocale) {
                self::loadLocale($fallbackLocale);
                $fallbackVal = self::$translations[$fallbackLocale][$group] ?? null;
                if (is_array($fallbackVal)) {
                    foreach ($keys as $segment) {
                        if (is_array($fallbackVal) && isset($fallbackVal[$segment])) {
                            $fallbackVal = $fallbackVal[$segment];
                        } else {
                            $fallbackVal = null;
                            break;
                        }
                    }
                }
                if (is_string($fallbackVal)) {
                    $value = $fallbackVal;
                }
            }
        }

        $translation = is_string($value) ? $value : $key;

        foreach ($replace as $placeholder => $val) {
            $translation = str_replace(':' . $placeholder, (string)$val, $translation);
        }

        return $translation;
    }

    private static function loadLocale(string $locale): void
    {
        if (isset(self::$translations[$locale])) {
            return;
        }

        self::$translations[$locale] = [];
        $dir = dirname(__DIR__, 2) . '/lang/' . $locale;

        if (is_dir($dir)) {
            $files = glob($dir . '/*.php');
            if ($files) {
                foreach ($files as $file) {
                    $group = basename($file, '.php');
                    $data = require $file;
                    if (is_array($data)) {
                        self::$translations[$locale][$group] = $data;
                    }
                }
            }
        }
    }
}
