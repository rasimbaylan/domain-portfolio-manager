<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    /** @var array<string, mixed> */
    private static array $config = [];

    /**
     * @param array<string, mixed> $config
     */
    public static function configure(array $config): void
    {
        self::$config = $config;
        self::$pdo = null;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            (string)(self::$config['host'] ?? '127.0.0.1'),
            (int)(self::$config['port'] ?? 3306),
            (string)(self::$config['name'] ?? 'opencode_test')
        );

        self::$pdo = new PDO(
            $dsn,
            (string)(self::$config['user'] ?? ''),
            (string)(self::$config['password'] ?? ''),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]
        );

        return self::$pdo;
    }
}
