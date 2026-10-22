<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$user = require_admin();

if (!is_post()) {
    flash('error', 'Permintaan penghapusan harus menggunakan metode POST.');
    redirect('admin/karyawan.php');
}

Csrf::verifyOrRedirect('admin/karyawan.php');

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    flash('error', 'ID karyawan tidak valid.');
    redirect('admin/karyawan.php');
}

$pdo = Database::connection();

/*
 * PENTING: ambil data terlebih dahulu (nama + nomor WhatsApp), kirim notifikasi,
 * baru setelah itu hapus dari database. Jangan dibalik urutannya.
 */
$stmt = $pdo->prepare(
    'SELECT id, nik, nama, jabatan, no_wa, email
     FROM users WHERE id = ? AND role = "karyawan" LIMIT 1'
);
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    flash('error', 'Data karyawan tidak ditemukan atau sudah dihapus.');
    redirect('admin/karyawan.php');
}

$waResult = WhatsAppService::send(
    (string) $employee['no_wa'],
    MessageTemplates::deleted($employee),
    'delete',
    (int) $employee['id']
);

$delete = $pdo->prepare('DELETE FROM users WHERE id = ? AND role = "karyawan"');
$delete->execute([$id]);

if ($delete->rowCount() < 1) {
    flash('error', 'Data karyawan gagal dihapus. Silakan coba lagi.');
    redirect('admin/karyawan.php');
}

flash('success', 'Data karyawan ' . $employee['nama'] . ' (NIK ' . $employee['nik'] . ') berhasil dihapus.');

if ($waResult['simulated']) {
    flash('info', 'Notifikasi penghapusan WhatsApp dicatat dalam mode simulasi.');
} elseif ($waResult['ok']) {
    flash('success', 'Notifikasi penghapusan telah dikirim ke WhatsApp karyawan sebelum data dihapus.');
} else {
    flash('warning', 'Data sudah dihapus, tetapi notifikasi WhatsApp gagal dikirim. Periksa log WhatsApp.');
}

redirect('admin/karyawan.php');
