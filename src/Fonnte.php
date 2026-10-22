<?php

declare(strict_types=1);

/**
 * Klien HTTP Fonnte.
 * Token dibaca dari .env dan tidak pernah dikirim ke browser.
 */
final class Fonnte
{
    /**
     * @return array{ok: bool, simulated: bool, http_code: int, message: string, raw: ?string}
     */
    public static function send(string $target, string $message): array
    {
        $token = trim((string) config('fonnte.token', ''));
        $enabled = (bool) config('fonnte.enabled', false);

        if (!$enabled || $token === '') {
            return [
                'ok' => false,
                'simulated' => true,
                'http_code' => 0,
                'message' => 'Mode simulasi: FONNTE_ENABLED=false atau FONNTE_TOKEN belum diisi.',
                'raw' => null,
            ];
        }

        if (!function_exists('curl_init')) {
            return [
                'ok' => false,
                'simulated' => false,
                'http_code' => 0,
                'message' => 'Ekstensi cURL PHP tidak tersedia.',
                'raw' => null,
            ];
        }

        $payload = http_build_query([
            'target' => $target,
            'message' => $message,
            'countryCode' => (string) config('fonnte.country_code', '62'),
        ]);

        $curl = curl_init((string) config('fonnte.endpoint', 'https://api.fonnte.com/send'));
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $token,
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $raw = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($raw === false) {
            return [
                'ok' => false,
                'simulated' => false,
                'http_code' => $httpCode,
                'message' => 'Gagal menghubungi Fonnte: ' . ($curlError !== '' ? $curlError : 'unknown error'),
                'raw' => null,
            ];
        }

        $decoded = json_decode((string) $raw, true);
        $ok = $httpCode >= 200 && $httpCode < 300
            && is_array($decoded)
            && (($decoded['status'] ?? false) === true || (string) ($decoded['status'] ?? '') === 'true');

        $messageResult = 'Respons Fonnte tidak dikenali.';
        if (is_array($decoded)) {
            $messageResult = (string) ($decoded['reason'] ?? $decoded['detail'] ?? $decoded['message'] ?? $messageResult);
        }

        return [
            'ok' => $ok,
            'simulated' => false,
            'http_code' => $httpCode,
            'message' => $messageResult,
            'raw' => mb_substr((string) $raw, 0, 5000),
        ];
    }
}
