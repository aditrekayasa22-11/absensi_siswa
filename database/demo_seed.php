<?php

/**
 * Seed data contoh. Dipanggil dari install.php bila opsi demo dipilih.
 * File ini tidak dapat diakses langsung dari browser (diblokir .htaccess).
 *
 * @var PDO $pdo
 */

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$demoPassword = password_hash('Karyawan#123', PASSWORD_DEFAULT);

$employees = [
    ['123456', 'Budi Santoso', 'Staff IT', '081234567801', 'budi@presensiku.test'],
    ['123457', 'Siti Aminah', 'Staff HRD', '081234567802', 'siti@presensiku.test'],
    ['123458', 'Andi Pratama', 'Staff Finance', '081234567803', 'andi@presensiku.test'],
    ['123459', 'Rina Wulandari', 'Staff Marketing', '081234567804', 'rina@presensiku.test'],
    ['123460', 'Dewa Saputra', 'Staff Operasional', '081234567805', 'dewa@presensiku.test'],
];

$insertUser = $pdo->prepare(
    'INSERT IGNORE INTO users (nik, nama, jabatan, no_wa, email, password, role, aktif, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, "karyawan", 1, NOW(), NOW())'
);

foreach ($employees as $employee) {
    $insertUser->execute([
        $employee[0],
        $employee[1],
        $employee[2],
        $employee[3],
        $employee[4],
        $demoPassword,
    ]);
}

$findUser = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$insertAttendance = $pdo->prepare(
    'INSERT IGNORE INTO attendance (user_id, tanggal, jam_masuk, jam_pulang, status, keterangan, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
);

$today = new DateTimeImmutable('today');

$findUser->execute(['budi@presensiku.test']);
$budi = $findUser->fetchColumn();

// Absensi hari ini: sebagian sudah pulang, sebagian masih bekerja, sebagian belum absen.
if ($budi) {
    $insertAttendance->execute([(int) $budi, $today->format('Y-m-d'), '07:02:00', '15:41:00', 'HADIR', null]);

    $findUser->execute(['siti@presensiku.test']);
    $siti = $findUser->fetchColumn();
    if ($siti) {
        $insertAttendance->execute([(int) $siti, $today->format('Y-m-d'), '07:18:00', null, 'HADIR', null]);
    }

    $findUser->execute(['andi@presensiku.test']);
    $andi = $findUser->fetchColumn();
    if ($andi) {
        $insertAttendance->execute([(int) $andi, $today->format('Y-m-d'), '07:52:00', null, 'TERLAMBAT', null]);
    }
}

// Riwayat 13 hari ke belakang (kecuali hari Minggu).
for ($dayOffset = 1; $dayOffset <= 13; $dayOffset++) {
    $date = $today->modify('-' . $dayOffset . ' days');
    if ((int) $date->format('w') === 0) {
        continue;
    }

    foreach ($employees as $index => $employee) {
        $findUser->execute([$employee[4]]);
        $userId = $findUser->fetchColumn();
        if (!$userId) {
            continue;
        }

        $pattern = ($dayOffset + $index) % 12;

        if ($pattern === 3) {
            $insertAttendance->execute([(int) $userId, $date->format('Y-m-d'), null, null, 'IZIN', 'Keperluan keluarga']);
        } elseif ($pattern === 7) {
            $insertAttendance->execute([(int) $userId, $date->format('Y-m-d'), null, null, 'SAKIT', 'Istirahat atas anjuran dokter']);
        } elseif ($pattern === 9) {
            // Tidak ada catatan (alpha) untuk variasi data.
            continue;
        } else {
            $late = $pattern % 4 === 0;
            $minute = 5 + (($index * 7 + $dayOffset * 3) % 22);
            $hour = 7;
            if ($late) {
                $minute = 34 + (($index * 5 + $dayOffset) % 20);
            }
            $clockIn = sprintf('%02d:%02d:00', $hour, $minute);
            $clockOut = sprintf('15:%02d:00', 30 + (($index * 9 + $dayOffset) % 25));

            $insertAttendance->execute([
                (int) $userId,
                $date->format('Y-m-d'),
                $clockIn,
                $clockOut,
                $late ? 'TERLAMBAT' : 'HADIR',
                null,
            ]);
        }
    }
}
