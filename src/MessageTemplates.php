<?php

declare(strict_types=1);

final class MessageTemplates
{
    /**
     * @param array<string, mixed> $user
     */
    public static function register(array $user): string
    {
        return "REGISTRASI BERHASIL.\n\n"
            . "Halo {$user['nama']}, data Anda telah berhasil terdaftar sebagai karyawan.\n\n"
            . "NIK: {$user['nik']}\n"
            . "Jabatan: {$user['jabatan']}\n"
            . "Email: {$user['email']}\n\n"
            . "Silakan login ke sistem absensi untuk melakukan presensi. Terima kasih.";
    }

    /**
     * @param array<string, mixed> $user
     */
    public static function adminRegister(array $user): string
    {
        return "KARYAWAN BARU TERDAFTAR\n\n"
            . "Nama: {$user['nama']}\n"
            . "NIK: {$user['nik']}\n"
            . "Jabatan: {$user['jabatan']}\n"
            . "No. WhatsApp: {$user['no_wa']}\n"
            . "Email: {$user['email']}\n\n"
            . "Registrasi karyawan baru berhasil dan data telah masuk ke sistem.";
    }

    /**
     * @param array<string, mixed> $user
     */
    public static function updated(array $user): string
    {
        return "DATA KARYAWAN DIPERBARUI\n\n"
            . "Halo {$user['nama']}, data Anda telah berhasil diperbarui oleh admin.\n\n"
            . "Nama: {$user['nama']}\n"
            . "NIK: {$user['nik']}\n"
            . "Jabatan: {$user['jabatan']}\n"
            . "No. WhatsApp: {$user['no_wa']}\n"
            . "Email: {$user['email']}\n\n"
            . "Jika ada data yang tidak sesuai, silakan hubungi admin.";
    }

    /**
     * @param array<string, mixed> $user
     */
    public static function deleted(array $user): string
    {
        return "DATA KARYAWAN DIHAPUS\n\n"
            . "Halo {$user['nama']}, data karyawan dengan NIK {$user['nik']} telah dihapus dari sistem absensi.\n\n"
            . "Jabatan: {$user['jabatan']}\n\n"
            . "Terima kasih atas kontribusi Anda. Jika penghapusan ini tidak sesuai, silakan hubungi admin.";
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $attendance
     */
    public static function checkIn(array $user, array $attendance): string
    {
        $status = strtoupper((string) $attendance['status']);
        $message = "ABSENSI MASUK\n\n"
            . "Nama: {$user['nama']}\n"
            . "NIK: {$user['nik']}\n"
            . "Tanggal: " . tanggal_id((string) $attendance['tanggal'], true) . "\n"
            . "Jam: " . jam((string) $attendance['jam_masuk']) . "\n"
            . "Status: {$status}.\n";

        if ($status === 'TERLAMBAT') {
            $message .= "\nAnda tercatat melewati batas jam masuk " . config('jam_masuk_batas', '07:30') . ".";
        }

        return $message . "\n\nTerima kasih dan selamat bekerja.";
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $attendance
     */
    public static function checkOut(array $user, array $attendance): string
    {
        return "ABSENSI PULANG\n\n"
            . "Nama: {$user['nama']}\n"
            . "NIK: {$user['nik']}\n"
            . "Tanggal: " . tanggal_id((string) $attendance['tanggal'], true) . "\n"
            . "Jam Masuk: " . jam((string) $attendance['jam_masuk']) . "\n"
            . "Jam Pulang: " . jam((string) $attendance['jam_pulang']) . "\n"
            . "Status: PULANG.\n\n"
            . "Terima kasih atas kerja keras Anda hari ini.";
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $attendance
     */
    public static function leave(array $user, array $attendance): string
    {
        $status = strtoupper((string) $attendance['status']);
        $keterangan = trim((string) ($attendance['keterangan'] ?? ''));

        return "PENGAJUAN {$status}\n\n"
            . "Nama: {$user['nama']}\n"
            . "NIK: {$user['nik']}\n"
            . "Tanggal: " . tanggal_id((string) $attendance['tanggal'], true) . "\n"
            . "Status: {$status}\n"
            . ($keterangan !== '' ? "Keterangan: {$keterangan}\n" : '')
            . "\nPengajuan Anda telah dicatat oleh sistem.";
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $attendance
     */
    public static function adminAttendance(array $user, array $attendance, string $event): string
    {
        $status = strtoupper((string) $attendance['status']);
        $lines = [
            'NOTIFIKASI ABSENSI KARYAWAN',
            '',
            "Nama: {$user['nama']}",
            "NIK: {$user['nik']}",
            "Jabatan: {$user['jabatan']}",
            "Tanggal: " . tanggal_id((string) $attendance['tanggal'], true),
            'Aktivitas: ' . strtoupper($event),
        ];

        if (!empty($attendance['jam_masuk'])) {
            $lines[] = 'Jam Masuk: ' . jam((string) $attendance['jam_masuk']);
        }
        if (!empty($attendance['jam_pulang'])) {
            $lines[] = 'Jam Pulang: ' . jam((string) $attendance['jam_pulang']);
        }

        $lines[] = "Status: {$status}.";

        return implode("\n", $lines);
    }

    public static function test(string $name = 'Admin'): string
    {
        return "TES INTEGRASI FONNTE\n\n"
            . "Halo {$name}, integrasi WhatsApp berhasil.\n"
            . "Dikirim pada: " . tanggal_id(date('Y-m-d'), true) . ' pukul ' . date('H:i') . " WIB.\n\n"
            . "Pesan ini dikirim dari aplikasi " . config('app.name', 'PresensiKu') . '.';
    }
}
