<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?Database $instance = null;
    /** @var array<string, PDO> */
    private array $connections = [];

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(?string $name = null): PDO
    {
        $name = $name ?? (config('database.default', 'mysql'));

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $config = config("database.connections.{$name}");
        if (!$config) {
            throw new RuntimeException("Database connection '{$name}' not configured.");
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );

        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options'] ?? []);
            $pdo->exec("SET NAMES '{$config['charset']}' COLLATE '{$config['collation']}'");
            $this->connections[$name] = $pdo;
            return $pdo;
        } catch (PDOException $e) {
            if (config('app.debug')) {
                throw new RuntimeException("Database connection failed [{$name}]: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
            throw new RuntimeException("Database service is currently unavailable. Please try again later.");
        }
    }

    public static function pdo(?string $name = null): PDO
    {
        return self::getInstance()->getConnection($name);
    }

    public static function beginTransaction(?string $name = null): bool
    {
        return self::pdo($name)->beginTransaction();
    }

    public static function commit(?string $name = null): bool
    {
        return self::pdo($name)->commit();
    }

    public static function rollBack(?string $name = null): bool
    {
        if (self::pdo($name)->inTransaction()) {
            return self::pdo($name)->rollBack();
        }
        return false;
    }

    /**
     * Execute a callback inside a transactional boundary.
     * Automatically rolls back on any exception and rethrows.
     *
     * @template T
     * @param callable(PDO): T $callback
     * @return T
     */
    public static function transaction(callable $callback, ?string $name = null): mixed
    {
        $pdo = self::pdo($name);
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
