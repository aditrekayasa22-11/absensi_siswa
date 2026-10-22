<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

/**
 * @param array<string, mixed> $payload
 */
function api_response(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    api_response([
        'success' => false,
        'message' => 'Metode tidak diizinkan. Gunakan POST.',
    ], 405);
}

$configuredKey = trim((string) config('api_key', ''));
$providedKey = trim((string) ($_SERVER['HTTP_X_API_KEY'] ?? ''));

if ($providedKey === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authorization = trim((string) $_SERVER['HTTP_AUTHORIZATION']);
    if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches) === 1) {
        $providedKey = trim($matches[1]);
    }
}

if ($configuredKey === '') {
    api_response([
        'success' => false,
        'message' => 'API key belum dikonfigurasi pada server.',
    ], 503);
}

if ($providedKey === '' || !hash_equals($configuredKey, $providedKey)) {
    api_response([
        'success' => false,
        'message' => 'API key tidak valid.',
    ], 401);
}

$rawBody = file_get_contents('php://input') ?: '';
$rawBody = preg_replace('/^\xEF\xBB\xBF/', '', $rawBody) ?? $rawBody;
$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
$input = [];

if (str_contains($contentType, 'application/json')) {
    $decoded = json_decode($rawBody, true);
    if (!is_array($decoded)) {
        api_response([
            'success' => false,
            'message' => 'Body JSON tidak valid.',
        ], 400);
    }
    $input = $decoded;
} else {
    $input = $_POST;
}

$target = is_scalar($input['target'] ?? null) ? trim((string) $input['target']) : '';
$message = is_scalar($input['message'] ?? null) ? trim((string) $input['message']) : '';

if ($target === '' || $message === '') {
    api_response([
        'success' => false,
        'message' => 'Field target dan message wajib diisi.',
    ], 422);
}

if (!WhatsAppService::isValidNumber($target)) {
    api_response([
        'success' => false,
        'message' => 'Nomor target tidak valid. Gunakan format 08xxxxxxxxxx atau 628xxxxxxxxxx.',
    ], 422);
}

if (mb_strlen($message) > 4000) {
    api_response([
        'success' => false,
        'message' => 'Pesan maksimal 4000 karakter.',
    ], 422);
}

try {
    $result = WhatsAppService::send($target, $message, 'api');

    if ($result['simulated']) {
        api_response([
            'success' => false,
            'simulated' => true,
            'status' => 'simulated',
            'target' => $result['to'],
            'message' => 'Mode simulasi aktif. Pesan dicatat tetapi tidak dikirim. Isi FONNTE_TOKEN dan aktifkan FONNTE_ENABLED.',
        ], 200);
    }

    if (!$result['ok']) {
        api_response([
            'success' => false,
            'simulated' => false,
            'status' => 'failed',
            'target' => $result['to'],
            'message' => 'Gagal mengirim pesan: ' . $result['message'],
        ], 502);
    }

    api_response([
        'success' => true,
        'simulated' => false,
        'status' => 'sent',
        'target' => $result['to'],
        'message' => 'Pesan berhasil masuk antrean Fonnte.',
    ], 200);
} catch (Throwable $exception) {
    api_response([
        'success' => false,
        'message' => (bool) config('debug', false)
            ? 'Terjadi kesalahan: ' . $exception->getMessage()
            : 'Terjadi kesalahan pada server.',
    ], 500);
}
