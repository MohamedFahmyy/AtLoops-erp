<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function json(array $data, int $status = 200, array $headers = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $url, array $flash = []): void
    {
        foreach ($flash as $key => $message) {
            Session::setFlash($key, $message);
        }
        header("Location: {$url}");
        exit;
    }

    public static function download(string $filePath, ?string $downloadName = null, string $mimeType = 'application/octet-stream'): void
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            die("File not found or access denied.");
        }

        $fileName = $downloadName ?: basename($filePath);
        // Prevent header injection in filename
        $safeFileName = preg_replace('/[^a-zA-Z0-9_\-\. \(\)]/', '_', $fileName);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $safeFileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        // Clear output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }

        readfile($filePath);
        exit;
    }
}
