<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$user = require_admin();
$pdo = Database::connection();
$today = date('Y-m-d');

$totalKaryawan = (int) $pdo->query(
    'SELECT COUNT(*) FROM users WHERE role = "karyawan" AND aktif = 1'
)->fetchColumn();
$totalAdmin = (int) $pdo->query(
    'SELECT COUNT(*) FROM users WHERE role = "admin" AND aktif = 1'
)->fetchColumn();

$statement = $pdo->prepare(
    'SELECT COUNT(*) AS total,
            COALESCE(SUM(a.jam_masuk IS NOT NULL), 0) AS masuk,
            COALESCE(SUM(a.jam_pulang IS NOT NULL), 0) AS pulang,
            COALESCE(SUM(a.status = "HADIR"), 0) AS hadir,
            COALESCE(SUM(a.status = "TERLAMBAT"), 0) AS terlambat,
            COALESCE(SUM(a.status = "IZIN"), 0) AS izin,
            COALESCE(SUM(a.status = "SAKIT"), 0) AS sakit
     FROM attendance a
     INNER JOIN users u ON u.id = a.user_id
     WHERE a.tanggal = ? AND u.role = "karyawan" AND u.aktif = 1'
);
$statement->execute([$today]);
$todayStats = $statement->fetch() ?: [];

$sudahAbsen = (int) ($todayStats['total'] ?? 0);
$sudahPulang = (int) ($todayStats['pulang'] ?? 0);
$belumAbsen = max(0, $totalKaryawan - $sudahAbsen);
$persenAbsen = $totalKaryawan > 0 ? min(100, (int) round($sudahAbsen / $totalKaryawan * 100)) : 0;
$persenBelum = $totalKaryawan > 0 ? min(100, (int) round($belumAbsen / $totalKaryawan * 100)) : 0;
$persenPulang = $totalKaryawan > 0 ? min(100, (int) round($sudahPulang / $totalKaryawan * 100)) : 0;

$statement = $pdo->prepare(
    'SELECT a.*, u.nama, u.nik, u.jabatan
     FROM attendance a
     INNER JOIN users u ON u.id = a.user_id
     WHERE a.tanggal = ? AND u.role = "karyawan"
     ORDER BY a.updated_at DESC
     LIMIT 6'
);
$statement->execute([$today]);
$activities = $statement->fetchAll();

$startDate = date('Y-m-d', strtotime('-6 days'));
$statement = $pdo->prepare(
    'SELECT a.tanggal, COUNT(*) AS total
     FROM attendance a
     INNER JOIN users u ON u.id = a.user_id
     WHERE a.tanggal BETWEEN ? AND ? AND u.role = "karyawan" AND u.aktif = 1
     GROUP BY a.tanggal'
);
$statement->execute([$startDate, $today]);
$trendRaw = $statement->fetchAll();
$trendMap = [];
foreach ($trendRaw as $row) {
    $trendMap[(string) $row['tanggal']] = (int) $row['total'];
}

$trend = [];
$maxTrend = 1;
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $count = $trendMap[$date] ?? 0;
    $maxTrend = max($maxTrend, $count);
    $trend[] = [
        'date' => $date,
        'count' => $count,
        'label' => substr(hari_id($date), 0, 3),
        'day' => date('d/m', strtotime($date)),
        'is_today' => $date === $today,
    ];
}
foreach ($trend as &$item) {
    $item['height'] = $item['count'] > 0 ? max(6, (int) round($item['count'] / $maxTrend * 100)) : 2;
}
unset($item);

$statement = $pdo->prepare(
    'SELECT u.id, u.nama, u.nik, u.jabatan, u.no_wa
     FROM users u
     LEFT JOIN attendance a ON a.user_id = u.id AND a.tanggal = ?
     WHERE u.role = "karyawan" AND u.aktif = 1 AND a.id IS NULL
     ORDER BY u.nama ASC
     LIMIT 5'
);
$statement->execute([$today]);
$belumAbsenList = $statement->fetchAll();

$pageTitle = 'Dashboard';
$pageSubtitle = 'Ringkasan kehadiran dan aktivitas karyawan hari ini.';
$activeMenu = 'dashboard';

require BASE_PATH . '/templates/admin_shell_start.php';
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">Ringkasan hari ini</p>
        <h1>Selamat datang, <?= e(explode(' ', (string) $user['nama'])[0]) ?> 👋</h1>
        <p class="page-heading__sub">Pantau kehadiran karyawan, aktivitas absensi, dan notifikasi WhatsApp dalam satu tampilan.</p>
    </div>
    <div class="page-heading__actions">
        <a href="<?= url('admin/karyawan_tambah.php') ?>" class="btn btn--primary">
            <?= icon('plus') ?> Tambah Karyawan
        </a>
        <a href="<?= url('admin/laporan.php') ?>" class="btn btn--secondary">
            <?= icon('report') ?> Lihat Laporan
        </a>
    </div>
</div>

<div class="stats-grid">
    <article class="stat-card stat-card--indigo">
        <div class="stat-card__top">
            <span class="stat-card__icon"><?= icon('users') ?></span>
            <span class="stat-card__trend">Aktif</span>
        </div>
        <div class="stat-card__value"><?= number_format($totalKaryawan, 0, ',', '.') ?></div>
        <div class="stat-card__label">Total Karyawan</div>
        <div class="stat-card__foot"><strong><?= number_format($totalAdmin, 0, ',', '.') ?></strong> administrator terdaftar</div>
    </article>

    <article class="stat-card stat-card--emerald">
        <div class="stat-card__top">
            <span class="stat-card__icon"><?= icon('check-circle') ?></span>
            <span class="stat-card__trend"><?= $persenAbsen ?>%</span>
        </div>
        <div class="stat-card__value"><?= number_format($sudahAbsen, 0, ',', '.') ?></div>
        <div class="stat-card__label">Sudah Absen Hari Ini</div>
        <div class="progress"><div class="progress__bar" style="width: <?= $persenAbsen ?>%"></div></div>
        <div class="stat-card__foot">
            <strong><?= (int) ($todayStats['hadir'] ?? 0) ?></strong> hadir &middot;
            <strong><?= (int) ($todayStats['terlambat'] ?? 0) ?></strong> terlambat
            <?php if ((int) ($todayStats['izin'] ?? 0) + (int) ($todayStats['sakit'] ?? 0) > 0): ?>
                &middot; <strong><?= (int) ($todayStats['izin'] ?? 0) + (int) ($todayStats['sakit'] ?? 0) ?></strong> izin/sakit
            <?php endif; ?>
        </div>
    </article>

    <article class="stat-card stat-card--amber">
        <div class="stat-card__top">
            <span class="stat-card__icon"><?= icon('clock') ?></span>
            <span class="stat-card__trend"><?= $persenBelum ?>%</span>
        </div>
        <div class="stat-card__value"><?= number_format($belumAbsen, 0, ',', '.') ?></div>
        <div class="stat-card__label">Belum Absen</div>
        <div class="progress"><div class="progress__bar" style="width: <?= $persenBelum ?>%"></div></div>
        <div class="stat-card__foot">
            <?= $belumAbsen > 0 ? '<strong>' . $belumAbsen . '</strong> karyawan belum memiliki catatan' : 'Semua karyawan sudah tercatat' ?>
        </div>
    </article>

    <article class="stat-card stat-card--sky">
        <div class="stat-card__top">
            <span class="stat-card__icon"><?= icon('logout') ?></span>
            <span class="stat-card__trend"><?= $persenPulang ?>%</span>
        </div>
        <div class="stat-card__value"><?= number_format($sudahPulang, 0, ',', '.') ?></div>
        <div class="stat-card__label">Sudah Pulang</div>
        <div class="progress"><div class="progress__bar" style="width: <?= $persenPulang ?>%"></div></div>
        <div class="stat-card__foot"><strong><?= max(0, $sudahAbsen - $sudahPulang) ?></strong> masih di kantor / belum pulang</div>
    </article>
</div>

<div class="grid grid--2-1 section-gap">
    <section class="card">
        <div class="card__head">
            <div>
                <h2 class="card__title">Aktivitas Absensi Hari Ini</h2>
                <p class="card__sub">Enam pembaruan absensi terakhir pada <?= e(tanggal_id($today, true)) ?>.</p>
            </div>
            <a href="<?= url('admin/absensi.php') ?>" class="btn btn--secondary btn--sm">Semua absensi <?= icon('arrow-right') ?></a>
        </div>

        <?php if ($activities === []): ?>
            <div class="empty-state">
                <div class="empty-state__icon"><?= icon('calendar') ?></div>
                <h3>Belum ada aktivitas absensi</h3>
                <p>Data absensi hari ini akan muncul di sini setelah karyawan melakukan absen masuk.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Masuk</th>
                            <th>Pulang</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td>
                                    <div class="person">
                                        <span class="avatar avatar--sm <?= avatar_tone($activity['nik']) ?>"><?= e(initials((string) $activity['nama'])) ?></span>
                                        <span class="person__text">
                                            <span class="person__name"><?= e($activity['nama']) ?></span>
                                            <span class="person__meta"><?= e($activity['nik']) ?> &middot; <?= e($activity['jabatan']) ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="num"><?= e(jam($activity['jam_masuk'])) ?></td>
                                <td class="num"><?= e(jam($activity['jam_pulang'])) ?></td>
                                <td><span class="badge badge--<?= status_class((string) $activity['status']) ?>"><?= e(status_label((string) $activity['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card__head">
            <div>
                <h2 class="card__title">Rekap Status</h2>
                <p class="card__sub">Komposisi kehadiran hari ini.</p>
            </div>
        </div>
        <div class="card__body">
            <div class="legend-list">
                <?php
                $statusItems = [
                    ['label' => 'Hadir', 'value' => (int) ($todayStats['hadir'] ?? 0), 'color' => '#10b981'],
                    ['label' => 'Terlambat', 'value' => (int) ($todayStats['terlambat'] ?? 0), 'color' => '#f59e0b'],
                    ['label' => 'Izin', 'value' => (int) ($todayStats['izin'] ?? 0), 'color' => '#0ea5e9'],
                    ['label' => 'Sakit', 'value' => (int) ($todayStats['sakit'] ?? 0), 'color' => '#8b5cf6'],
                ];
                $statusTotal = max(1, $sudahAbsen);
                ?>
                <?php foreach ($statusItems as $item): ?>
                    <div>
                        <div class="legend-item">
                            <span class="legend-item__dot" style="background: <?= e($item['color']) ?>"></span>
                            <span class="legend-item__text"><?= e($item['label']) ?></span>
                            <span class="legend-item__value"><?= $item['value'] ?></span>
                        </div>
                        <div class="legend-item__bar">
                            <span style="width: <?= min(100, (int) round($item['value'] / $statusTotal * 100)) ?>%; background: <?= e($item['color']) ?>"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="divider"></div>

            <p class="card__sub mb-2">Aksi cepat</p>
            <div class="quick-actions">
                <a href="<?= url('admin/karyawan_tambah.php') ?>" class="quick-action"><?= icon('plus') ?> Tambah karyawan</a>
                <a href="<?= url('admin/laporan.php') ?>" class="quick-action"><?= icon('download') ?> Unduh laporan</a>
                <a href="<?= url('admin/wa.php') ?>" class="quick-action"><?= icon('whatsapp') ?> Tes WhatsApp</a>
                <a href="<?= url('admin/absensi.php') ?>" class="quick-action"><?= icon('calendar-check') ?> Data absensi</a>
            </div>
        </div>
    </section>
</div>

<div class="grid grid--2-1 section-gap">
    <section class="card">
        <div class="card__head">
            <div>
                <h2 class="card__title">Tren Kehadiran 7 Hari</h2>
                <p class="card__sub">Jumlah catatan absensi per hari.</p>
            </div>
            <span class="badge badge--neutral badge--plain"><?= icon('trending') ?> 7 hari terakhir</span>
        </div>
        <div class="card__body">
            <div class="chart-bars">
                <?php foreach ($trend as $item): ?>
                    <div class="chart-col">
                        <div class="chart-col__track" title="<?= $item['count'] ?> catatan">
                            <div class="chart-col__bar <?= $item['is_today'] ? 'is-today' : '' ?>" style="height: <?= $item['height'] ?>%"></div>
                        </div>
                        <div class="chart-col__label">
                            <?= e($item['label']) ?>
                            <small><?= e($item['day']) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card__head">
            <div>
                <h2 class="card__title">Belum Absen</h2>
                <p class="card__sub">Karyawan tanpa catatan hari ini.</p>
            </div>
            <span class="badge badge--warning"><?= $belumAbsen ?></span>
        </div>

        <?php if ($belumAbsenList === []): ?>
            <div class="empty-state">
                <div class="empty-state__icon"><?= icon('check-circle') ?></div>
                <h3>Semua sudah tercatat</h3>
                <p>Tidak ada karyawan yang belum melakukan absensi hari ini.</p>
            </div>
        <?php else: ?>
            <div class="card__body" style="padding-top: 8px; padding-bottom: 10px;">
                <div class="legend-list">
                    <?php foreach ($belumAbsenList as $employee): ?>
                        <div class="legend-item">
                            <span class="avatar avatar--sm <?= avatar_tone($employee['id']) ?>"><?= e(initials((string) $employee['nama'])) ?></span>
                            <span class="legend-item__text">
                                <?= e($employee['nama']) ?>
                                <small class="text-muted" style="display:block;font-weight:500;font-size:10px"><?= e($employee['jabatan']) ?></small>
                            </span>
                            <a href="https://wa.me/<?= e($employee['no_wa']) ?>" target="_blank" rel="noopener" class="action-btn" title="Chat WhatsApp">
                                <?= icon('whatsapp') ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card__footer">
                <a href="<?= url('admin/karyawan.php') ?>" class="text-sm" style="font-weight:700">Kelola seluruh karyawan <?= icon('arrow-right') ?></a>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require BASE_PATH . '/templates/admin_shell_end.php'; ?>
