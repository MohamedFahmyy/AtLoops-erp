<?php

declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$publicFile = __DIR__ . '/public' . $uri;

if ($uri !== '/' && file_exists($publicFile) && !is_dir($publicFile)) {
    return false; // serve requested file as-is
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';

require_once __DIR__ . '/public/index.php';
