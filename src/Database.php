<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = (string) config('db.host', '127.0.0.1');
        $port = (string) config('db.port', '3306');
        $name = (string) config('db.name', 'absensi_siswa');
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        try {
            self::$connection = new PDO(
                $dsn,
                (string) config('db.user', 'root'),
                (string) config('db.pass', ''),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ]
            );
        } catch (PDOException $exception) {
            http_response_code(500);
            $debug = (bool) config('debug', false);
            $message = $debug
                ? htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8')
                : 'Koneksi database gagal. Periksa konfigurasi pada file .env.';

            echo '<!doctype html><html lang="id"><head><meta charset="utf-8">'
                . '<meta name="viewport" content="width=device-width,initial-scale=1">'
                . '<title>Database tidak terhubung</title>'
                . '<style>body{font-family:system-ui,sans-serif;background:#f1f5f9;display:grid;place-items:center;min-height:100vh;margin:0}'
                . '.box{max-width:620px;background:#fff;padding:36px;border-radius:18px;box-shadow:0 20px 50px rgba(15,23,42,.12)}'
                . 'h1{margin:0 0 10px;font-size:22px;color:#0f172a}p{color:#475569;line-height:1.6}'
                . 'code{background:#f8fafc;border:1px solid #e2e8f0;padding:2px 6px;border-radius:6px}'
                . 'a{display:inline-block;margin-top:12px;background:#4f46e5;color:#fff;text-decoration:none;padding:11px 18px;border-radius:10px;font-weight:600}</style>'
                . '</head><body><div class="box"><h1>Database belum terhubung</h1>'
                . '<p>' . $message . '</p>'
                . '<p>Pastikan MySQL XAMPP sudah berjalan, lalu jalankan installer untuk membuat database dan tabel.</p>'
                . '<a href="' . htmlspecialchars((string) url('install.php'), ENT_QUOTES, 'UTF-8') . '">Buka Installer</a>'
                . '</div></body></html>';
            exit;
        }

        return self::$connection;
    }
}
