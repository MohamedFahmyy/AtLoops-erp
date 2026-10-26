<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;
use RuntimeException;
use Throwable;

class Application
{
    private static ?Application $instance = null;
    private string $basePath;
    private array $config = [];
    private Router $router;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        self::$instance = $this;

        $this->bootstrap();
        $this->router = new Router();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException("Application instance not initialized.");
        }
        return self::$instance;
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getConfig(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $group = array_shift($keys);

        if (!isset($this->config[$group])) {
            $configFile = $this->basePath('config/' . $group . '.php');
            if (file_exists($configFile)) {
                $this->config[$group] = require $configFile;
            } else {
                return $default;
            }
        }

        $value = $this->config[$group];
        foreach ($keys as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    private function bootstrap(): void
    {
        // 1. Load .env if exists
        if (file_exists($this->basePath('.env'))) {
            $dotenv = Dotenv::createImmutable($this->basePath);
            $dotenv->safeLoad();
        }

        // 2. Set timezone
        $timezone = $this->getConfig('app.timezone', 'Africa/Cairo');
        date_default_timezone_set($timezone);

        // 3. Set error reporting
        $debug = $this->getConfig('app.debug', false);
        if ($debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        // 4. Register exception / error handlers
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);

        // 5. Initialize secure session
        Session::start();
    }

    public function handleException(Throwable $e): void
    {
        $debug = $this->getConfig('app.debug', false);

        // Log error
        $logDir = $this->basePath('storage/logs');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logMessage = sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        file_put_contents($logDir . '/app_' . date('Y-m-d') . '.log', $logMessage, FILE_APPEND | LOCK_EX);

        if (php_sapi_name() === 'cli') {
            echo "\n[ERROR] " . $e->getMessage() . "\n";
            echo "In " . $e->getFile() . ":" . $e->getLine() . "\n";
            if ($debug) {
                echo $e->getTraceAsString() . "\n";
            }
            exit(1);
        }

        http_response_code(500);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            Response::json([
                'error' => $debug ? $e->getMessage() : 'An internal server error occurred.',
                'trace' => $debug ? explode("\n", $e->getTraceAsString()) : null,
            ], 500);
        }

        if ($debug) {
            echo "<h1>Application Error (Debug Mode)</h1>";
            echo "<p><strong>" . e($e->getMessage()) . "</strong></p>";
            echo "<p><em>" . e($e->getFile()) . ":" . $e->getLine() . "</em></p>";
            echo "<pre>" . e($e->getTraceAsString()) . "</pre>";
        } else {
            $errorView = $this->basePath('app/Views/errors/500.php');
            if (file_exists($errorView)) {
                require $errorView;
            } else {
                echo "<h1>500 Internal Server Error</h1><p>Something went wrong. Please contact system administrator.</p>";
            }
        }
        exit;
    }

    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
        return false;
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $this->router->dispatch($method, $uri);
    }
}
