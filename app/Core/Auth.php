<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

class Auth
{
    private const SESSION_USER_ID = 'auth_user_id';
    private static ?array $cachedUser = null;
    private static ?array $cachedPermissions = null;
    private static ?array $cachedRoles = null;

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function id(): ?int
    {
        Session::start();
        $id = Session::get(self::SESSION_USER_ID);
        return $id ? (int)$id : null;
    }

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $userId = self::id();
        if (!$userId) {
            return null;
        }

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id AND is_active = 1 LIMIT 1');
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                self::logout();
                return null;
            }

            self::$cachedUser = $user;
            self::loadRolesAndPermissions($userId);
            return self::$cachedUser;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function attempt(string $email, string $password): bool
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => strtolower(trim($email))]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return false;
            }

            if (!($user['is_active'] ?? 1)) {
                return false;
            }

            if (!password_verify($password, $user['password'])) {
                return false;
            }

            // Check if password needs rehash
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
                $update->execute(['password' => $newHash, 'id' => $user['id']]);
            }

            // Update last login
            $update = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
            $update->execute(['id' => $user['id']]);

            self::login($user);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function login(array $user): void
    {
        Session::start();
        Session::regenerate(true);
        Session::set(self::SESSION_USER_ID, (int)$user['id']);
        self::$cachedUser = $user;
        self::loadRolesAndPermissions((int)$user['id']);
    }

    public static function logout(): void
    {
        Session::start();
        Session::remove(self::SESSION_USER_ID);
        Session::destroy();
        self::$cachedUser = null;
        self::$cachedPermissions = null;
        self::$cachedRoles = null;
    }

    public static function hasRole(string ...$roles): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        if (self::$cachedRoles === null) {
            self::loadRolesAndPermissions((int)$user['id']);
        }

        foreach ($roles as $role) {
            if (in_array($role, self::$cachedRoles ?? [], true)) {
                return true;
            }
        }
        return false;
    }

    public static function can(string $permission): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        if (self::$cachedPermissions === null) {
            self::loadRolesAndPermissions((int)$user['id']);
        }

        // Super Admin wildcard check
        if (in_array('*', self::$cachedPermissions ?? [], true)) {
            return true;
        }

        return in_array($permission, self::$cachedPermissions ?? [], true);
    }

    private static function loadRolesAndPermissions(int $userId): void
    {
        self::$cachedRoles = [];
        self::$cachedPermissions = [];

        try {
            $pdo = Database::pdo();

            // Load user roles
            $stmt = $pdo->prepare('
                SELECT r.slug
                FROM roles r
                INNER JOIN user_roles ur ON ur.role_id = r.id
                WHERE ur.user_id = :user_id
            ');
            $stmt->execute(['user_id' => $userId]);
            self::$cachedRoles = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            // If super_admin role is present, grant wildcard
            if (in_array('super_admin', self::$cachedRoles, true)) {
                self::$cachedPermissions = ['*'];
                return;
            }

            // Load role-based permissions from database
            $stmt = $pdo->prepare('
                SELECT DISTINCT p.slug
                FROM permissions p
                INNER JOIN role_permissions rp ON rp.permission_id = p.id
                INNER JOIN user_roles ur ON ur.role_id = rp.role_id
                WHERE ur.user_id = :user_id
            ');
            $stmt->execute(['user_id' => $userId]);
            $dbPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            // Merge with config defaults as fallback
            $configMatrix = config('permissions.role_permissions', []);
            $configPermissions = [];
            foreach (self::$cachedRoles as $roleSlug) {
                if (isset($configMatrix[$roleSlug])) {
                    $configPermissions = array_merge($configPermissions, $configMatrix[$roleSlug]);
                }
            }

            self::$cachedPermissions = array_values(array_unique(array_merge($dbPermissions, $configPermissions)));
        } catch (\Throwable $e) {
            self::$cachedRoles = [];
            self::$cachedPermissions = [];
        }
    }
}
