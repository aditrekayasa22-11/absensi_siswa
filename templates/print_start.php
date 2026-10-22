<?php
/**
 * Layout cetak (print / simpan sebagai PDF).
 * Variabel: $pageTitle, $printSubtitle (opsional), $user.
 */
$pageTitle = $pageTitle ?? 'Laporan';
$printSubtitle = $printSubtitle ?? '';
$bodyClass = 'print-body';
require BASE_PATH . '/templates/head.php';
?>
<div class="print-toolbar no-print">
    <div>
        <strong><?= e(config('app.name', 'PresensiKu')) ?></strong>
        <span>Pratinjau cetak laporan</span>
    </div>
    <div class="print-toolbar__actions">
        <a href="<?= url('admin/laporan.php') ?>" class="btn btn--secondary btn--sm">
            <?= icon('arrow-left') ?> Kembali
        </a>
        <button type="button" class="btn btn--primary btn--sm" onclick="window.print()">
            <?= icon('print') ?> Cetak / Simpan PDF
        </button>
    </div>
</div>
<main class="print-page">
    <header class="print-header">
        <div class="print-header__brand">
            <img src="<?= asset('assets/img/logo.svg') ?>" alt="" width="46" height="46">
            <div>
                <h1><?= e(config('app.name', 'PresensiKu')) ?></h1>
                <p>Sistem Absensi Karyawan &middot; Laporan Kehadiran</p>
            </div>
        </div>
        <div class="print-header__meta">
            <strong><?= e($pageTitle) ?></strong>
            <?php if ($printSubtitle !== ''): ?><span><?= e($printSubtitle) ?></span><?php endif; ?>
            <span>Dicetak: <?= e(tanggal_id(date('Y-m-d'), true)) ?> <?= date('H:i') ?> WIB</span>
        </div>
    </header>
