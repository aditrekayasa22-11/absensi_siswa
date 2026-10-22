<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$user = require_admin();
$pdo = Database::connection();

$tanggal = trim((string) ($_GET['tanggal'] ?? date('Y-m-d')));
$dateObj = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);
if ($dateObj === false || $dateObj->format('Y-m-d') !== $tanggal) {
    $tanggal = date('Y-m-d');
}
$q = trim((string) ($_GET['q'] ?? ''));
$today = date('Y-m-d');

$where = 'a.tanggal = ? AND u.role = "karyawan"';
$params = [$tanggal];

if ($q !== '') {
    $where .= ' AND (u.nama LIKE ? OR u.nik LIKE ? OR u.jabatan LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$stmt = $pdo->prepare(
    'SELECT a.id, a.tanggal, a.jam_masuk, a.jam_pulang, a.status, a.keterangan,
            u.nama, u.nik, u.jabatan
     FROM attendance a
     INNER JOIN users u ON u.id = a.user_id
     WHERE ' . $where . '
     ORDER BY a.jam_masuk IS NULL, a.jam_masuk ASC, u.nama ASC'
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT COUNT(*) AS total,
            COALESCE(SUM(a.jam_masuk IS NOT NULL), 0) AS masuk,
            COALESCE(SUM(a.jam_pulang IS NOT NULL), 0) AS pulang,
            COALESCE(SUM(a.status = "HADIR"), 0) AS hadir,
            COALESCE(SUM(a.status = "TERLAMBAT"), 0) AS terlambat,
            COALESCE(SUM(a.status = "IZIN"), 0) AS izin,
            COALESCE(SUM(a.status = "SAKIT"), 0) AS sakit
     FROM attendance a
     INNER JOIN users u ON u.id = a.user_id
     WHERE a.tanggal = ? AND u.role = "karyawan"'
);
$stmt->execute([$tanggal]);
$summary = $stmt->fetch() ?: [];

$totalKaryawan = (int) $pdo->query(
    'SELECT COUNT(*) FROM users WHERE role = "karyawan" AND aktif = 1'
)->fetchColumn();

$pageTitle = 'Absensi';
$pageSubtitle = 'Pantau catatan absensi masuk dan pulang karyawan per tanggal.';
$activeMenu = 'absensi';

require BASE_PATH . '/templates/admin_shell_start.php';
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">Monitoring Kehadiran</p>
        <h1>Data Absensi</h1>
        <p class="page-heading__sub"><?= e(tanggal_id($tanggal, true)) ?> &middot; <?= count($rows) ?> catatan absensi ditemukan.</p>
    </div>
    <div class="page-heading__actions">
        <a href="<?= url('admin/laporan.php?dari=' . $tanggal . '&sampai=' . $tanggal) ?>" class="btn btn--primary">
            <?= icon('report') ?> Laporan Tanggal Ini
        </a>
        <a href="<?= url('admin/absensi.php') ?>" class="btn btn--secondary">
            <?= icon('refresh') ?> Hari Ini
        </a>
    </div>
</div>

<div class="mini-stats mb-2">
    <div class="mini-stat">
        <div class="mini-stat__label">Catatan Absensi</div>
        <div class="mini-stat__value"><?= (int) ($summary['total'] ?? 0) ?> <span>/ <?= $totalKaryawan ?> karyawan</span></div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat__label">Hadir</div>
        <div class="mini-stat__value" style="color:#047857"><?= (int) ($summary['hadir'] ?? 0) ?> <span>tepat waktu</span></div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat__label">Terlambat</div>
        <div class="mini-stat__value" style="color:#b45309"><?= (int) ($summary['terlambat'] ?? 0) ?> <span>karyawan</span></div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat__label">Sudah Pulang</div>
        <div class="mini-stat__value" style="color:#0369a1"><?= (int) ($summary['pulang'] ?? 0) ?> <span>karyawan</span></div>
    </div>
</div>

<form method="get" action="<?= url('admin/absensi.php') ?>" class="filter-bar">
    <div class="field">
        <label for="tanggal">Tanggal</label>
        <input type="date" id="tanggal" name="tanggal" class="input" value="<?= e($tanggal) ?>">
    </div>
    <div class="field" style="flex:1;min-width:220px">
        <label for="q">Cari karyawan</label>
        <input type="search" id="q" name="q" class="input" value="<?= e($q) ?>" placeholder="Nama, NIK, atau jabatan...">
    </div>
    <div class="filter-bar__actions">
        <button type="submit" class="btn btn--primary btn--sm"><?= icon('filter') ?> Terapkan</button>
        <?php if ($q !== '' || $tanggal !== $today): ?>
            <a href="<?= url('admin/absensi.php') ?>" class="btn btn--secondary btn--sm">Reset</a>
        <?php endif; ?>
    </div>
</form>

<section class="card">
    <div class="table-toolbar">
        <div class="table-toolbar__left">
            <span class="table-toolbar__title">Daftar absensi</span>
            <span class="table-toolbar__hint"><?= e(hari_id($tanggal)) ?>, <?= e(tanggal_id($tanggal)) ?></span>
        </div>
        <div class="table-toolbar__right">
            <span class="badge badge--success"><?= (int) ($summary['hadir'] ?? 0) ?> hadir</span>
            <span class="badge badge--warning"><?= (int) ($summary['terlambat'] ?? 0) ?> terlambat</span>
            <span class="badge badge--info"><?= (int) ($summary['izin'] ?? 0) ?> izin</span>
            <span class="badge badge--purple"><?= (int) ($summary['sakit'] ?? 0) ?> sakit</span>
        </div>
    </div>

    <?php if ($rows === []): ?>
        <div class="empty-state">
            <div class="empty-state__icon"><?= icon('calendar') ?></div>
            <h3>Belum ada absensi pada tanggal ini</h3>
            <p>Tidak ada catatan absensi yang cocok. Coba pilih tanggal lain atau ubah kata kunci pencarian.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>NIK</th>
                        <th>Tanggal</th>
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
                            <td>
                                <div class="person">
                                    <span class="avatar avatar--sm <?= avatar_tone($row['nik']) ?>"><?= e(initials((string) $row['nama'])) ?></span>
                                    <span class="person__text">
                                        <span class="person__name"><?= e($row['nama']) ?></span>
                                        <span class="person__meta"><?= e($row['jabatan']) ?></span>
                                    </span>
                                </div>
                            </td>
                            <td class="num"><?= e($row['nik']) ?></td>
                            <td class="muted"><?= e(date('d/m/Y', strtotime((string) $row['tanggal']))) ?></td>
                            <td class="num"><?= e(jam($row['jam_masuk'])) ?></td>
                            <td class="num"><?= e(jam($row['jam_pulang'])) ?></td>
                            <td class="muted">
                                <?php if (!empty($row['jam_masuk']) && !empty($row['jam_pulang'])): ?>
                                    <?= selisih_menit((string) $row['jam_masuk'], (string) $row['jam_pulang']) ?> menit
                                <?php else: ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge--<?= status_class((string) $row['status']) ?>"><?= e(status_label((string) $row['status'])) ?></span></td>
                            <td class="muted" style="max-width:220px"><?= e($row['keterangan'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/templates/admin_shell_end.php'; ?>
