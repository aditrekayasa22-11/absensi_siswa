<?php

declare(strict_types=1);

/**
 * Mengambil nilai konfigurasi dengan notasi titik, contoh: config('fonnte.token').
 *
 * @param mixed $default
 * @return mixed
 */
function config(string $key, mixed $default = null): mixed
{
    $segments = explode('.', $key);
    $value = $GLOBALS['app_config'] ?? [];

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

/**
 * Escape output untuk mencegah XSS.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    $base = (string) config('app.url', '');

    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
        $appRoot = realpath(BASE_PATH);
        $sub = '';

        if ($documentRoot && $appRoot && str_starts_with(strtolower($appRoot), strtolower($documentRoot))) {
            $sub = str_replace('\\', '/', substr($appRoot, strlen($documentRoot)));
        }

        $base = $scheme . '://' . $host . rtrim($sub, '/');
    }

    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $fullPath = BASE_PATH . '/' . ltrim($path, '/');
    $version = is_file($fullPath) ? '?v=' . filemtime($fullPath) : '';
    return url($path) . $version;
}

function redirect(string $path): never
{
    $destination = preg_match('#^https?://#i', $path) === 1 ? $path : url($path);
    $destination = str_replace(["\r", "\n"], '', $destination);
    header('Location: ' . $destination);
    exit;
}

function flash(string $type, string $message): void
{
    $allowed = ['success', 'error', 'warning', 'info'];
    if (!in_array($type, $allowed, true)) {
        $type = 'info';
    }

    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * @return array<int, array{type: string, message: string}>
 */
function consume_flashes(): array
{
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return is_array($items) ? $items : [];
}

function render_flashes(): string
{
    $icons = [
        'success' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>',
        'error' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/></svg>',
        'warning' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>',
        'info' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>',
    ];

    $html = '';
    foreach (consume_flashes() as $flash) {
        $type = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info';
        $html .= '<div class="alert alert--' . $type . '" role="alert" data-auto-dismiss="6500">'
            . '<span class="alert__icon">' . $icons[$type] . '</span>'
            . '<span class="alert__text">' . e($flash['message']) . '</span>'
            . '<button type="button" class="alert__close" aria-label="Tutup">&times;</button>'
            . '</div>';
    }

    return $html;
}

/**
 * @return array<string, mixed>
 */
function require_login(): array
{
    $user = Auth::user();
    if ($user === null) {
        flash('error', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        redirect('login.php');
    }

    return $user;
}

/**
 * @return array<string, mixed>
 */
function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        flash('error', 'Halaman tersebut hanya dapat diakses oleh admin.');
        redirect('karyawan/index.php');
    }

    return $user;
}

/**
 * @return array<string, mixed>
 */
function require_karyawan(): array
{
    $user = require_login();
    if ($user['role'] !== 'karyawan') {
        flash('info', 'Anda masuk sebagai admin.');
        redirect('admin/index.php');
    }

    return $user;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_scalar($value) ? trim((string) $value) : $default;
}

function tanggal_id(?string $date, bool $withDay = false): string
{
    if ($date === null || $date === '' || $date === '0000-00-00') {
        return '-';
    }

    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    $dateTime = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if ($dateTime === false) {
        return $date;
    }

    $result = $dateTime->format('d') . ' ' . $months[(int) $dateTime->format('n')] . ' ' . $dateTime->format('Y');

    return $withDay ? $days[(int) $dateTime->format('w')] . ', ' . $result : $result;
}

function hari_id(?string $date): string
{
    if ($date === null || $date === '') {
        return '-';
    }

    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $timestamp = strtotime($date);
    return $timestamp === false ? '-' : $days[(int) date('w', $timestamp)];
}

function jam(?string $time): string
{
    if ($time === null || $time === '' || $time === '00:00:00') {
        return '-';
    }

    return substr($time, 0, 5);
}

function status_label(string $status): string
{
    return match (strtoupper($status)) {
        'HADIR' => 'Hadir',
        'TERLAMBAT' => 'Terlambat',
        'IZIN' => 'Izin',
        'SAKIT' => 'Sakit',
        'PULANG' => 'Pulang',
        default => ucfirst(strtolower($status)),
    };
}

function status_class(string $status): string
{
    return match (strtoupper($status)) {
        'HADIR' => 'success',
        'TERLAMBAT' => 'warning',
        'IZIN' => 'info',
        'SAKIT' => 'purple',
        'PULANG' => 'success',
        default => 'neutral',
    };
}

function wa_type_label(string $type): string
{
    $labels = [
        'register' => 'Registrasi',
        'register_admin' => 'Registrasi (admin)',
        'admin_create' => 'Tambah karyawan',
        'update' => 'Perubahan data',
        'delete' => 'Penghapusan',
        'absen_masuk' => 'Absen masuk',
        'absen_masuk_admin' => 'Absen masuk (admin)',
        'absen_pulang' => 'Absen pulang',
        'absen_pulang_admin' => 'Absen pulang (admin)',
        'izin' => 'Pengajuan izin',
        'izin_admin' => 'Izin (admin)',
        'sakit' => 'Pengajuan sakit',
        'sakit_admin' => 'Sakit (admin)',
        'test' => 'Tes koneksi',
        'api' => 'API eksternal',
    ];

    return $labels[$type] ?? ucwords(str_replace('_', ' ', $type));
}

function wa_status_label(string $status): string
{
    return match ($status) {
        'sent' => 'Terkirim',
        'failed' => 'Gagal',
        'simulated' => 'Simulasi',
        'pending' => 'Menunggu',
        default => ucfirst($status),
    };
}

function wa_status_class(string $status): string
{
    return match ($status) {
        'sent' => 'success',
        'failed' => 'danger',
        'simulated' => 'info',
        'pending' => 'warning',
        default => 'neutral',
    };
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $parts = array_values(array_filter($parts, static fn ($part) => $part !== ''));
    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    return $initials !== '' ? $initials : '?';
}

function avatar_tone(int|string $seed): string
{
    $index = (int) (crc32((string) $seed) % 5) + 1;
    return 'tone-' . $index;
}

function phone_display(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (str_starts_with($digits, '62')) {
        $digits = '0' . substr($digits, 2);
    }

    return $digits !== '' ? $digits : $phone;
}

function wa_mode_active(): bool
{
    return (bool) config('fonnte.enabled', false) && trim((string) config('fonnte.token', '')) !== '';
}

function selisih_menit(string $start, string $end): int
{
    $startTime = strtotime($start);
    $endTime = strtotime($end);
    if ($startTime === false || $endTime === false) {
        return 0;
    }

    return max(0, (int) round(($endTime - $startTime) / 60));
}
