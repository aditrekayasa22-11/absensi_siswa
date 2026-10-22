<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$user = require_admin();
$filters = Laporan::filters($_GET);
$mode = ($_GET['mode'] ?? 'detail') === 'rekap' ? 'rekap' : 'detail';

$filename = 'laporan-absensi-' . $filters['dari'] . '_sd_' . $filters['sampai'] . '-' . $mode . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'wb');
if ($output === false) {
    exit;
}

// BOM agar karakter Indonesia tampil benar di Excel.
fwrite($output, "\xEF\xBB\xBF");

if ($mode === 'detail') {
    fputcsv($output, [
        'No', 'Nama', 'NIK', 'Jabatan', 'Tanggal', 'Jam Masuk', 'Jam Pulang', 'Status', 'Keterangan',
    ]);

    foreach (Laporan::detail($filters) as $index => $row) {
        fputcsv($output, [
            $index + 1,
            $row['nama'],
            $row['nik'],
            $row['jabatan'],
            date('d/m/Y', strtotime((string) $row['tanggal'])),
            jam($row['jam_masuk']),
            jam($row['jam_pulang']),
            status_label((string) $row['status']),
            $row['keterangan'] ?: '-',
        ]);
    }
} else {
    fputcsv($output, [
        'No', 'Nama', 'NIK', 'Jabatan', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Total Catatan', 'Kehadiran (%)',
    ]);

    foreach (Laporan::rekap($filters) as $index => $row) {
        $total = (int) $row['total'];
        $persen = $total > 0 ? (int) round(((int) $row['hadir'] + (int) $row['terlambat']) / $total * 100) : 0;

        fputcsv($output, [
            $index + 1,
            $row['nama'],
            $row['nik'],
            $row['jabatan'],
            (int) $row['hadir'],
            (int) $row['terlambat'],
            (int) $row['izin'],
            (int) $row['sakit'],
            $total,
            $persen,
        ]);
    }
}

fclose($output);
exit;
