<?php

declare(strict_types=1);

final class WhatsAppService
{
    /**
     * Normalisasi nomor Indonesia menjadi format 62xxxxxxxxxx.
     */
    public static function normalizeNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        } elseif (str_starts_with($digits, '620')) {
            $digits = '62' . substr($digits, 3);
        }

        return $digits;
    }

    public static function isValidNumber(string $phone): bool
    {
        $digits = self::normalizeNumber($phone);
        return str_starts_with($digits, '62') && strlen($digits) >= 10 && strlen($digits) <= 15;
    }

    /**
     * Kirim WhatsApp lalu catat hasilnya ke tabel whatsapp_logs.
     *
     * @return array{ok: bool, simulated: bool, status: string, message: string, to: string}
     */
    public static function send(string $target, string $message, string $type, ?int $userId = null): array
    {
        $to = self::normalizeNumber($target);

        if (!self::isValidNumber($to)) {
            $result = [
                'ok' => false,
                'simulated' => false,
                'status' => 'failed',
                'message' => 'Nomor WhatsApp tidak valid: ' . $target,
                'to' => $to,
            ];
            self::log($to, $message, $type, 'failed', $result['message'], $userId);
            return $result;
        }

        $response = Fonnte::send($to, $message);
        $status = $response['simulated'] ? 'simulated' : ($response['ok'] ? 'sent' : 'failed');

        self::log($to, $message, $type, $status, $response['raw'] ?? $response['message'], $userId);

        return [
            'ok' => $response['ok'],
            'simulated' => $response['simulated'],
            'status' => $status,
            'message' => $response['message'],
            'to' => $to,
        ];
    }

    public static function notifyAdmin(string $message, string $type): array
    {
        $adminWa = (string) config('admin_wa', '');
        if ($adminWa === '') {
            self::log('', $message, $type . '_admin', 'failed', 'ADMIN_WA belum diatur di .env', null);
            return [
                'ok' => false,
                'simulated' => false,
                'status' => 'failed',
                'message' => 'Nomor WhatsApp admin belum diatur.',
                'to' => '',
            ];
        }

        return self::send($adminWa, $message, $type . '_admin');
    }

    private static function log(
        string $target,
        string $message,
        string $type,
        string $status,
        string $response,
        ?int $userId
    ): void {
        try {
            $statement = Database::connection()->prepare(
                'INSERT INTO whatsapp_logs (user_id, penerima, tipe, pesan, status, respons, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())'
            );
            $statement->execute([
                $userId,
                $target,
                mb_substr($type, 0, 50),
                $message,
                $status,
                mb_substr($response, 0, 5000),
            ]);
        } catch (Throwable $exception) {
            self::writeFileLog($target, $status, $exception->getMessage());
        }
    }

    private static function writeFileLog(string $target, string $status, string $message): void
    {
        $dir = BASE_PATH . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $line = sprintf(
            "[%s] target=%s status=%s message=%s\n",
            date('Y-m-d H:i:s'),
            $target,
            $status,
            str_replace(["\r", "\n"], ' ', $message)
        );

        @file_put_contents($dir . '/whatsapp.log', $line, FILE_APPEND | LOCK_EX);
    }
}
