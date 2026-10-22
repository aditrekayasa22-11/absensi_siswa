<?php

declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function verify(?string $token): bool
    {
        return is_string($token)
            && $token !== ''
            && !empty($_SESSION['_csrf'])
            && hash_equals((string) $_SESSION['_csrf'], $token);
    }

    public static function verifyOrRedirect(string $fallback = 'index.php'): void
    {
        if (!self::verify($_POST['_token'] ?? null)) {
            flash('error', 'Sesi keamanan tidak valid atau sudah kedaluwarsa. Silakan coba lagi.');
            redirect($fallback);
        }
    }
}
