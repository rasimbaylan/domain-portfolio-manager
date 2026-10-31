<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;

final class Auth
{
    private const SESSION_KEY = 'admin';
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 60;
    private const REGENERATE_SECONDS = 900;

    // Zamanlama saldırılarını azaltmak için kullanılan geçerli bir bcrypt özeti.
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    /**
     * @return array{id: int, username: string}|null
     */
    public static function user(): ?array
    {
        $admin = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_array($admin) || !isset($admin['id'], $admin['username'])) {
            return null;
        }

        return [
            'id' => (int)$admin['id'],
            'username' => (string)$admin['username'],
        ];
    }

    public static function check(): bool
    {
        $admin = self::user();
        if ($admin === null) {
            return false;
        }

        $appConfig = $GLOBALS['appConfig']['app'] ?? [];
        $idleTimeout = (int)($appConfig['session_idle_timeout'] ?? 1800);
        $maxLifetime = (int)($appConfig['session_max_lifetime'] ?? 28800);
        $now = time();

        $lastActivity = (int)($_SESSION['_last_activity'] ?? 0);
        $loginTime = (int)($_SESSION['_login_time'] ?? 0);

        if ($lastActivity > 0 && ($now - $lastActivity) > $idleTimeout) {
            self::logout();
            return false;
        }

        if ($loginTime > 0 && ($now - $loginTime) > $maxLifetime) {
            self::logout();
            return false;
        }

        $_SESSION['_last_activity'] = $now;

        $lastRegeneration = (int)($_SESSION['_last_regeneration'] ?? 0);
        if ($lastRegeneration > 0 && ($now - $lastRegeneration) > self::REGENERATE_SECONDS) {
            session_regenerate_id(true);
            $_SESSION['_last_regeneration'] = $now;
        }

        return true;
    }

    public static function attempt(string $username, string $password): bool
    {
        if (self::isLockedOut()) {
            return false;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1'
        );
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        $hash = is_array($admin) ? (string)$admin['password_hash'] : self::DUMMY_HASH;
        $passwordMatches = password_verify($password, $hash);

        if (!is_array($admin) || !$passwordMatches) {
            self::recordFailure();
            return false;
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash === false) {
                throw new RuntimeException('Şifre özeti oluşturulamadı.');
            }

            $update = Database::pdo()->prepare(
                'UPDATE admins SET password_hash = :hash WHERE id = :id LIMIT 1'
            );
            $update->execute([':hash' => $newHash, ':id' => (int)$admin['id']]);
        }

        session_regenerate_id(true);

        $now = time();
        $_SESSION[self::SESSION_KEY] = [
            'id' => (int)$admin['id'],
            'username' => (string)$admin['username'],
        ];
        $_SESSION['_last_activity'] = $now;
        $_SESSION['_login_time'] = $now;
        $_SESSION['_last_regeneration'] = $now;

        unset($_SESSION['_login_failures'], $_SESSION['_login_last_failure']);

        return true;
    }

    public static function isLockedOut(): bool
    {
        $failures = (int)($_SESSION['_login_failures'] ?? 0);
        $lastFailure = (int)($_SESSION['_login_last_failure'] ?? 0);

        return $failures >= self::MAX_ATTEMPTS && (time() - $lastFailure) < self::LOCK_SECONDS;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    private static function recordFailure(): void
    {
        $_SESSION['_login_failures'] = (int)($_SESSION['_login_failures'] ?? 0) + 1;
        $_SESSION['_login_last_failure'] = time();
    }
}
