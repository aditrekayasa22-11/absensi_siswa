<?php

declare(strict_types=1);

final class Laporan
{
    /**
     * @param array<string, mixed> $source
     * @return array{dari: string, sampai: string, karyawan_id: ?int, status: string}
     */
    public static function filters(array $source): array
    {
        $today = date('Y-m-d');
        $dari = trim((string) ($source['dari'] ?? date('Y-m-01')));
        $sampai = trim((string) ($source['sampai'] ?? $today));

        $dariDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dari);
        if ($dariDate === false || $dariDate->format('Y-m-d') !== $dari) {
            $dari = date('Y-m-01');
        }

        $sampaiDate = DateTimeImmutable::createFromFormat('!Y-m-d', $sampai);
        if ($sampaiDate === false || $sampaiDate->format('Y-m-d') !== $sampai) {
            $sampai = $today;
        }

        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $status = strtoupper(trim((string) ($source['status'] ?? '')));
        if (!in_array($status, ['HADIR', 'TERLAMBAT', 'IZIN', 'SAKIT'], true)) {
            $status = '';
        }

        $karyawanId = filter_var($source['karyawan_id'] ?? null, FILTER_VALIDATE_INT);
        if ($karyawanId === false || $karyawanId < 1) {
            $karyawanId = null;
        }

        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'karyawan_id' => $karyawanId,
            'status' => $status,
        ];
    }

    /**
     * @param array{dari: string, sampai: string, karyawan_id: ?int, status: string} $filters
     * @return array{total: int, hadir: int, terlambat: int, izin: int, sakit: int, pulang: int, karyawan_absen: int}
     */
    public static function summary(array $filters): array
    {
        [$where, $params] = self::where($filters);
        $sql = 'SELECT COUNT(*) AS total,
                       COALESCE(SUM(a.status = "HADIR"), 0) AS hadir,
                       COALESCE(SUM(a.status = "TERLAMBAT"), 0) AS terlambat,
                       COALESCE(SUM(a.status = "IZIN"), 0) AS izin,
                       COALESCE(SUM(a.status = "SAKIT"), 0) AS sakit,
                       COALESCE(SUM(a.jam_pulang IS NOT NULL), 0) AS pulang,
                       COUNT(DISTINCT a.user_id) AS karyawan_absen
                FROM attendance a
                INNER JOIN users u ON u.id = a.user_id
                WHERE ' . $where;

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'hadir' => (int) ($row['hadir'] ?? 0),
            'terlambat' => (int) ($row['terlambat'] ?? 0),
            'izin' => (int) ($row['izin'] ?? 0),
            'sakit' => (int) ($row['sakit'] ?? 0),
            'pulang' => (int) ($row['pulang'] ?? 0),
            'karyawan_absen' => (int) ($row['karyawan_absen'] ?? 0),
        ];
    }

    /**
     * @param array{dari: string, sampai: string, karyawan_id: ?int, status: string} $filters
     * @return array<int, array<string, mixed>>
     */
    public static function detail(array $filters): array
    {
        [$where, $params] = self::where($filters);
        $sql = 'SELECT a.id, a.tanggal, a.jam_masuk, a.jam_pulang, a.status, a.keterangan,
                       u.nama, u.nik, u.jabatan
                FROM attendance a
                INNER JOIN users u ON u.id = a.user_id
                WHERE ' . $where . '
                ORDER BY a.tanggal DESC, u.nama ASC';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    /**
     * @param array{dari: string, sampai: string, karyawan_id: ?int, status: string} $filters
     * @return array<int, array<string, mixed>>
     */
    public static function rekap(array $filters): array
    {
        $joinParams = [$filters['dari'], $filters['sampai']];
        $joinStatus = '';

        if ($filters['status'] !== '') {
            $joinStatus = ' AND a.status = ?';
            $joinParams[] = $filters['status'];
        }

        $where = 'u.role = "karyawan" AND u.aktif = 1';
        $params = $joinParams;

        if ($filters['karyawan_id'] !== null) {
            $where .= ' AND u.id = ?';
            $params[] = $filters['karyawan_id'];
        }

        $having = $filters['status'] !== '' ? ' HAVING total > 0' : '';

        $sql = 'SELECT u.id, u.nama, u.nik, u.jabatan,
                       COUNT(a.id) AS total,
                       COALESCE(SUM(a.status = "HADIR"), 0) AS hadir,
                       COALESCE(SUM(a.status = "TERLAMBAT"), 0) AS terlambat,
                       COALESCE(SUM(a.status = "IZIN"), 0) AS izin,
                       COALESCE(SUM(a.status = "SAKIT"), 0) AS sakit
                FROM users u
                LEFT JOIN attendance a ON a.user_id = u.id
                    AND a.tanggal BETWEEN ? AND ?' . $joinStatus . '
                WHERE ' . $where . '
                GROUP BY u.id, u.nama, u.nik, u.jabatan' . $having . '
                ORDER BY u.nama ASC';

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    /**
     * @param array{dari: string, sampai: string, karyawan_id: ?int, status: string} $filters
     * @return array{0: string, 1: array<int, mixed>}
     */
    private static function where(array $filters): array
    {
        $where = 'u.role = "karyawan" AND a.tanggal BETWEEN ? AND ?';
        $params = [$filters['dari'], $filters['sampai']];

        if ($filters['karyawan_id'] !== null) {
            $where .= ' AND u.id = ?';
            $params[] = $filters['karyawan_id'];
        }

        if ($filters['status'] !== '') {
            $where .= ' AND a.status = ?';
            $params[] = $filters['status'];
        }

        return [$where, $params];
    }
}
