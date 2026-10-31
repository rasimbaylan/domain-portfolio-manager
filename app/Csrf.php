<?php

declare(strict_types=1);

namespace App;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function isValid(mixed $token): bool
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? null;

        return is_string($token)
            && is_string($expected)
            && $expected !== ''
            && hash_equals($expected, $token);
    }
}
