<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$user = require_admin();
$pdo = Database::connection();

$filters = Laporan::filters($_GET);
$mode = ($_GET['mode'] ?? 'detail') === 'rekap' ? 'rekap' : 'detail';
$isPrint = isset($_GET['print']);

$summary = Laporan::summary($filters);
$detailRows = $mode === 'detail' ? Laporan::detail($filters) : [];
$rekapRows = $mode === 'rekap' ? Laporan::rekap($filters) : [];

$employees = $pdo->query(
    'SELECT id, nama, nik FROM users WHERE role = "karyawan" AND aktif = 1 ORDER BY nama ASC'
)->fetchAll();

$queryBase = [
    'dari' => $filters['dari'],
    'sampai' => $filters['sampai'],
    'mode' => $mode,
];
if ($filters['karyawan_id'] !== null) {
    $queryBase['karyawan_id'] = $filters['karyawan_id'];
}
if ($filters['status'] !== '') {
    $queryBase['status'] = $filters['status'];
}

$periodeLabel = tanggal_id($filters['dari']) . ' – ' . tanggal_id($filters['sampai']);
$pageTitle = 'Laporan Absensi';
$printSubtitle = $periodeLabel;

if ($isPrint) {
    require BASE_PATH . '/templates/print_start.php';
    ?>
    <div class="print-summary">
        <div><span>Total Catatan</span><strong><?= $summary['total'] ?></strong></div>
        <div><span>Hadir</span><strong><?= $summary['hadir'] ?></strong></div>
        <div><span>Terlambat</span><strong><?= $summary['terlambat'] ?></strong></div>
        <div><span>Izin / Sakit</span><strong><?= $summary['izin'] + $summary['sakit'] ?></strong></div>
    </div>

    <?php if ($mode === 'detail'): ?>
        <table class="print-table">
            <thead>
                <tr>
                    <th style="width:28px">No</th>
                    <th>Nama</th>
                    <th>NIK</th>
                    <th>Tanggal</th>
                    <th>Masuk</th>
                    <th>Pulang</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($detailRows === []): ?>
                    <tr><td colspan="7" style="text-align:center;padding:20px">Tidak ada data absensi pada periode ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($detailRows as $index => $row): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= e($row['nama']) ?></td>
                            <td><?= e($row['nik']) ?></td>
                            <td><?= e(tanggal_id((string) $row['tanggal'])) ?></td>
                            <td><?= e(jam($row['jam_masuk'])) ?></td>
                            <td><?= e(jam($row['jam_pulang'])) ?></td>
                            <td><?= e(status_label((string) $row['status'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <table class="print-table">
            <thead>
                <tr>
                    <th style="width:28px">No</th>
                    <th>Nama</th>
                    <th>NIK</th>
                    <th>Jabatan</th>
                    <th>Hadir</th>
                    <th>Terlambat</th>
                    <th>Izin</th>
                    <th>Sakit</th>
                    <th>Total</th>
                    <th>Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rekapRows === []): ?>
                    <tr><td colspan="10" style="text-align:center;padding:20px">Tidak ada data rekap pada periode ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($rekapRows as $index => $row): ?>
                        <?php
                        $totalRow = (int) $row['total'];
                        $persen = $totalRow > 0 ? (int) round(((int) $row['hadir'] + (int) $row['terlambat']) / $totalRow * 100) : 0;
                        ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= e($row['nama']) ?></td>
                            <td><?= e($row['nik']) ?></td>
                            <td><?= e($row['jabatan']) ?></td>
                            <td><?= (int) $row['hadir'] ?></td>
                            <td><?= (int) $row['terlambat'] ?></td>
                            <td><?= (int) $row['izin'] ?></td>
                            <td><?= (int) $row['sakit'] ?></td>
                            <td><?= $totalRow ?></td>
                            <td><?= $persen ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="print-signature">
        <div>
            <span>Dibuat oleh,</span>
            <strong><?= e($user['nama']) ?></strong>
            <small>Administrator</small>
        </div>
        <div>
            <span>Mengetahui,</span>
            <strong>&nbsp;</strong>
            <small>Pimpinan / HRD</small>
        </div>
    </div>
    <?php
    require BASE_PATH . '/templates/print_end.php';
    exit;
}

$pageSubtitle = 'Rekap kehadiran karyawan periode ' . $periodeLabel . '.';
$activeMenu = 'laporan';

require BASE_PATH . '/templates/admin_shell_start.php';
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">Pelaporan</p>
        <h1>Laporan Absensi</h1>
        <p class="page-heading__sub">Analisis kehadiran karyawan berdasarkan rentang tanggal, karyawan, dan status absensi.</p>
    </div>
    <div class="page-heading__actions">
        <a href="<?= url('admin/laporan_export.php?' . http_build_query($queryBase)) ?>" class="btn btn--secondary">
            <?= icon('download') ?> Export Excel (CSV)
        </a>
        <a href="<?= url('admin/laporan.php?' . http_build_query(array_merge($queryBase, ['print' => 1]))) ?>" class="btn btn--primary" target="_blank" rel="noopener">
            <?= icon('print') ?> Cetak / PDF
        </a>
    </div>
</div>

<form method="get" action="<?= url('admin/laporan.php') ?>" class="filter-bar">
    <input type="hidden" name="mode" value="<?= e($mode) ?>">
    <div class="field">
        <label for="dari">Dari tanggal</label>
        <input type="date" id="dari" name="dari" class="input" value="<?= e($filters['dari']) ?>">
    </div>
    <div class="field">
        <label for="sampai">Sampai tanggal</label>
        <input type="date" id="sampai" name="sampai" class="input" value="<?= e($filters['sampai']) ?>">
    </div>
    <div class="field">
        <label for="karyawan_id">Karyawan</label>
        <select id="karyawan_id" name="karyawan_id" class="select">
            <option value="">Semua karyawan</option>
            <?php foreach ($employees as $employee): ?>
                <option value="<?= (int) $employee['id'] ?>" <?= $filters['karyawan_id'] === (int) $employee['id'] ? 'selected' : '' ?>>
                    <?= e($employee['nama']) ?> (<?= e($employee['nik']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status" class="select">
            <option value="">Semua status</option>
            <?php foreach (['HADIR', 'TERLAMBAT', 'IZIN', 'SAKIT'] as $statusOption): ?>
                <option value="<?= $statusOption ?>" <?= $filters['status'] === $statusOption ? 'selected' : '' ?>><?= e(status_label($statusOption)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__actions">
        <button type="submit" class="btn btn--primary btn--sm"><?= icon('filter') ?> Terapkan</button>
        <a href="<?= url('admin/laporan.php') ?>" class="btn btn--secondary btn--sm">Reset</a>
    </div>
</form>

<div class="stats-grid">
    <article class="stat-card stat-card--indigo">
        <div class="stat-card__top">
            <span class="stat-card__icon"><?= icon('report') ?></span>
            <span class="stat-card__trend"><?= $summary['total'] > 0 ? 'Data' : 'Kosong' ?></span>
        </div>
        <div class="stat-card__value"><?= number_format($summary['total'], 0, ',', '.') ?></div>
        <div class="stat-card__label">Total Catatan Absensi</div>
        <div class="stat-card__foot"><strong><?= $summary['karyawan_absen'] ?></strong> karyawan tercatat</div>
    </article>

    <article class="stat-card stat-card--emerald">
        <div class="stat-card__top">
            <span class="stat-card__icon"><?= icon('check-circle') ?></span>
            <span class="stat-card__trend"><?= $summary['total'] > 0 ? (int) round($summary['hadir'] / $summary['total'] * 100) : 0 ?>%</span>
        </div>
        <div class="stat-card__value"><?= number_format($summary['hadir'], 0, ',', '.') ?></div>
        <div class="stat-card__label">Hadir Tepat Waktu</div>
        <div class="stat-card__foot"><strong><?= $summary['pulang'] ?></strong> sudah absen pulang</div>
    </article>

    <article class="stat-card stat-card--amber">
        <div class="stat-card__top">
            <span class="stat-card__icon"><?= icon('clock') ?></span>
            <span class="stat-card__trend"><?= $summary['total'] > 0 ? (int) round($summary['terlambat'] / $summary['total'] * 100) : 0 ?>%</span>
        </div>
        <div class="stat-card__value"><?= number_format($summary['terlambat'], 0, ',', '.') ?></div>
        <div class="stat-card__label">Terlambat</div>
        <div class="stat-card__foot">Batas masuk <strong><?= e(config('jam_masuk_batas', '07:30')) ?></strong> WIB</div>
    </article>

    <article class="stat-card stat-card--purple" style="--tone:#7c3aed;--tone-soft:#f5f3ff;--tone-line:#ede9fe">
        <div class="stat-card__top">
            <span class="stat-card__icon"><?= icon('inbox') ?></span>
            <span class="stat-card__trend"><?= $summary['izin'] + $summary['sakit'] ?></span>
        </div>
        <div class="stat-card__value"><?= number_format($summary['izin'] + $summary['sakit'], 0, ',', '.') ?></div>
        <div class="stat-card__label">Izin &amp; Sakit</div>
        <div class="stat-card__foot"><strong><?= $summary['izin'] ?></strong> izin &middot; <strong><?= $summary['sakit'] ?></strong> sakit</div>
    </article>
</div>

<div class="mb-2" style="margin-top:22px">
    <div class="tabs">
        <a href="<?= url('admin/laporan.php?' . http_build_query(array_merge($queryBase, ['mode' => 'detail']))) ?>" class="tab-btn <?= $mode === 'detail' ? 'is-active' : '' ?>">Detail Absensi</a>
        <a href="<?= url('admin/laporan.php?' . http_build_query(array_merge($queryBase, ['mode' => 'rekap']))) ?>" class="tab-btn <?= $mode === 'rekap' ? 'is-active' : '' ?>">Rekap Karyawan</a>
    </div>
</div>

<section class="card">
    <div class="table-toolbar">
        <div class="table-toolbar__left">
            <span class="table-toolbar__title"><?= $mode === 'detail' ? 'Detail absensi' : 'Rekap per karyawan' ?></span>
            <span class="table-toolbar__hint"><?= e($periodeLabel) ?></span>
        </div>
        <div class="table-toolbar__right">
            <span class="badge badge--neutral badge--plain"><?= icon('users') ?> <?= $mode === 'detail' ? count($detailRows) : count($rekapRows) ?> baris</span>
        </div>
    </div>

    <?php if ($mode === 'detail'): ?>
        <?php if ($detailRows === []): ?>
            <div class="empty-state">
                <div class="empty-state__icon"><?= icon('report') ?></div>
                <h3>Tidak ada data absensi</h3>
                <p>Belum ada catatan absensi yang cocok dengan filter pada periode ini.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:52px">No</th>
                            <th>Nama</th>
                            <th>NIK</th>
                            <th>Tanggal</th>
                            <th>Masuk</th>
                            <th>Pulang</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detailRows as $index => $row): ?>
                            <tr>
                                <td class="num"><?= $index + 1 ?></td>
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
                                <td><?= e(tanggal_id((string) $row['tanggal'])) ?></td>
                                <td class="num"><?= e(jam($row['jam_masuk'])) ?></td>
                                <td class="num"><?= e(jam($row['jam_pulang'])) ?></td>
                                <td><span class="badge badge--<?= status_class((string) $row['status']) ?>"><?= e(status_label((string) $row['status'])) ?></span></td>
                                <td class="muted" style="max-width:200px"><?= e($row['keterangan'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php if ($rekapRows === []): ?>
            <div class="empty-state">
                <div class="empty-state__icon"><?= icon('users') ?></div>
                <h3>Tidak ada data rekap</h3>
                <p>Belum ada karyawan atau catatan absensi yang cocok dengan filter.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:52px">No</th>
                            <th>Karyawan</th>
                            <th>NIK</th>
                            <th>Hadir</th>
                            <th>Terlambat</th>
                            <th>Izin</th>
                            <th>Sakit</th>
                            <th>Total</th>
                            <th style="min-width:150px">Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rekapRows as $index => $row): ?>
                            <?php
                            $totalRow = (int) $row['total'];
                            $persen = $totalRow > 0 ? (int) round(((int) $row['hadir'] + (int) $row['terlambat']) / $totalRow * 100) : 0;
                            ?>
                            <tr>
                                <td class="num"><?= $index + 1 ?></td>
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
                                <td><span class="badge badge--success badge--plain"><?= (int) $row['hadir'] ?></span></td>
                                <td><span class="badge badge--warning badge--plain"><?= (int) $row['terlambat'] ?></span></td>
                                <td><span class="badge badge--info badge--plain"><?= (int) $row['izin'] ?></span></td>
                                <td><span class="badge badge--purple badge--plain"><?= (int) $row['sakit'] ?></span></td>
                                <td class="num"><strong><?= $totalRow ?></strong></td>
                                <td>
                                    <div class="flex-between" style="gap:8px">
                                        <div class="progress" style="flex:1;margin-top:0"><div class="progress__bar" style="width: <?= $persen ?>%; background:#4f46e5"></div></div>
                                        <span class="text-sm num" style="font-weight:700;min-width:36px;text-align:right"><?= $persen ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/templates/admin_shell_end.php'; ?>
