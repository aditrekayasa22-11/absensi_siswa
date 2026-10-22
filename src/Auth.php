<?php

declare(strict_types=1);

final class Auth
{
    /** @var array<string, mixed>|null */
    private static ?array $user = null;
    private static bool $loaded = false;

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }

        self::$loaded = true;
        $id = $_SESSION['user_id'] ?? null;
        if (!is_numeric($id)) {
            return null;
        }

        $statement = Database::connection()->prepare(
            'SELECT id, nik, nama, jabatan, no_wa, email, role, aktif, created_at, updated_at
             FROM users WHERE id = ? LIMIT 1'
        );
        $statement->execute([(int) $id]);
        $user = $statement->fetch();

        if (!$user || (int) $user['aktif'] !== 1) {
            unset($_SESSION['user_id']);
            return null;
        }

        self::$user = $user;
        return self::$user;
    }

    public static function attempt(string $email, string $password): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM users WHERE email = ? LIMIT 1'
        );
        $statement->execute([$email]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, (string) $user['password'])) {
            return null;
        }

        if ((int) $user['aktif'] !== 1) {
            return null;
        }

        self::login($user);

        return $user;
    }

    /**
     * @param array<string, mixed> $user
     */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role'] = (string) $user['role'];
        $_SESSION['login_at'] = time();

        self::$user = null;
        self::$loaded = false;
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();
        self::$user = null;
        self::$loaded = true;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user !== null && $user['role'] === 'admin';
    }
}
