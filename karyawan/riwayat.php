<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$user = require_karyawan();
$pdo = Database::connection();
$userId = (int) $user['id'];

$bulan = trim((string) ($_GET['bulan'] ?? date('Y-m')));
if (preg_match('/^\d{4}-\d{2}$/', $bulan) !== 1) {
    $bulan = date('Y-m');
}

$monthDate = DateTimeImmutable::createFromFormat('!Y-m', $bulan);
if ($monthDate === false) {
    $bulan = date('Y-m');
    $monthDate = new DateTimeImmutable(date('Y-m-01'));
}

$monthStart = $monthDate->format('Y-m-01');
$monthEnd = $monthDate->format('Y-m-t');

$statusFilter = strtoupper(trim((string) ($_GET['status'] ?? '')));
if (!in_array($statusFilter, ['HADIR', 'TERLAMBAT', 'IZIN', 'SAKIT'], true)) {
    $statusFilter = '';
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;

$where = 'user_id = ? AND tanggal BETWEEN ? AND ?';
$params = [$userId, $monthStart, $monthEnd];

if ($statusFilter !== '') {
    $where .= ' AND status = ?';
    $params[] = $statusFilter;
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE ' . $where);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    'SELECT * FROM attendance WHERE ' . $where . ' ORDER BY tanggal DESC LIMIT ? OFFSET ?'
);
foreach ($params as $index => $value) {
    $stmt->bindValue($index + 1, $value, PDO::PARAM_STR);
}
$stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT status, COUNT(*) AS total
     FROM attendance WHERE user_id = ? AND tanggal BETWEEN ? AND ?
     GROUP BY status'
);
$stmt->execute([$userId, $monthStart, $monthEnd]);
$counts = ['HADIR' => 0, 'TERLAMBAT' => 0, 'IZIN' => 0, 'SAKIT' => 0];
foreach ($stmt->fetchAll() as $row) {
    $counts[(string) $row['status']] = (int) $row['total'];
}

$totalBulan = array_sum($counts);
$persenHadir = $totalBulan > 0 ? (int) round(($counts['HADIR'] + $counts['TERLAMBAT']) / $totalBulan * 100) : 0;

$filterQuery = ['bulan' => $bulan];
if ($statusFilter !== '') {
    $filterQuery['status'] = $statusFilter;
}

$pageTitle = 'Riwayat Absensi';
$pageSubtitle = 'Rekap seluruh catatan kehadiran Anda berdasarkan bulan.';
$activeMenu = 'riwayat';

require BASE_PATH . '/templates/karyawan_shell_start.php';
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">Arsip Kehadiran</p>
        <h1>Riwayat Absensi</h1>
        <p class="page-heading__sub">Menampilkan data bulan <?= e(tanggal_id($monthStart)) ?> sampai <?= e(tanggal_id($monthEnd)) ?>.</p>
    </div>
    <div class="page-heading__actions">
        <a href="<?= url('karyawan/index.php') ?>" class="btn btn--primary"><?= icon('calendar-check') ?> Absensi Hari Ini</a>
    </div>
</div>

<div class="mini-stats mb-2">
    <div class="mini-stat">
        <div class="mini-stat__label">Hadir</div>
        <div class="mini-stat__value" style="color:#047857"><?= $counts['HADIR'] ?> <span>hari</span></div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat__label">Terlambat</div>
        <div class="mini-stat__value" style="color:#b45309"><?= $counts['TERLAMBAT'] ?> <span>hari</span></div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat__label">Izin / Sakit</div>
        <div class="mini-stat__value" style="color:#6d28d9"><?= $counts['IZIN'] + $counts['SAKIT'] ?> <span>hari</span></div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat__label">Tingkat Kehadiran</div>
        <div class="mini-stat__value" style="color:#4f46e5"><?= $persenHadir ?>% <span>dari <?= $totalBulan ?> catatan</span></div>
    </div>
</div>

<form method="get" action="<?= url('karyawan/riwayat.php') ?>" class="filter-bar">
    <div class="field">
        <label for="bulan">Bulan</label>
        <input type="month" id="bulan" name="bulan" class="input" value="<?= e($bulan) ?>">
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status" class="select">
            <option value="">Semua status</option>
            <?php foreach (['HADIR', 'TERLAMBAT', 'IZIN', 'SAKIT'] as $option): ?>
                <option value="<?= $option ?>" <?= $statusFilter === $option ? 'selected' : '' ?>><?= e(status_label($option)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__actions">
        <button type="submit" class="btn btn--primary btn--sm"><?= icon('filter') ?> Tampilkan</button>
        <a href="<?= url('karyawan/riwayat.php') ?>" class="btn btn--secondary btn--sm">Reset</a>
    </div>
</form>

<section class="card">
    <div class="table-toolbar">
        <div class="table-toolbar__left">
            <span class="table-toolbar__title">Catatan absensi</span>
            <span class="table-toolbar__hint"><?= number_format($total, 0, ',', '.') ?> data ditemukan</span>
        </div>
        <div class="table-toolbar__right">
            <span class="badge badge--success badge--plain">Hadir <?= $counts['HADIR'] ?></span>
            <span class="badge badge--warning badge--plain">Terlambat <?= $counts['TERLAMBAT'] ?></span>
        </div>
    </div>

    <?php if ($rows === []): ?>
        <div class="empty-state">
            <div class="empty-state__icon"><?= icon('calendar') ?></div>
            <h3>Belum ada catatan</h3>
            <p>Tidak ada data absensi pada bulan dan status yang dipilih.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Hari</th>
                        <th>Masuk</th>
                        <th>Pulang</th>
                        <th>Durasi</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td style="font-weight:600"><?= e(tanggal_id((string) $row['tanggal'])) ?></td>
                            <td class="muted"><?= e(hari_id((string) $row['tanggal'])) ?></td>
                            <td class="num"><?= e(jam($row['jam_masuk'])) ?></td>
                            <td class="num"><?= e(jam($row['jam_pulang'])) ?></td>
                            <td class="muted">
                                <?= !empty($row['jam_masuk']) && !empty($row['jam_pulang'])
                                    ? selisih_menit((string) $row['jam_masuk'], (string) $row['jam_pulang']) . ' menit'
                                    : '—' ?>
                            </td>
                            <td><span class="badge badge--<?= status_class((string) $row['status']) ?>"><?= e(status_label((string) $row['status'])) ?></span></td>
                            <td class="muted" style="max-width:220px"><?= e($row['keterangan'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="card__footer flex-between">
                <span class="text-sm text-muted">Halaman <?= $page ?> dari <?= $totalPages ?></span>
                <div style="display:flex;gap:6px">
                    <?php if ($page > 1): ?>
                        <a href="<?= url('karyawan/riwayat.php?' . http_build_query(array_merge($filterQuery, ['page' => $page - 1]))) ?>" class="btn btn--secondary btn--sm"><?= icon('arrow-left') ?> Sebelumnya</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= url('karyawan/riwayat.php?' . http_build_query(array_merge($filterQuery, ['page' => $page + 1]))) ?>" class="btn btn--secondary btn--sm">Berikutnya <?= icon('arrow-right') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/templates/karyawan_shell_end.php'; ?>
