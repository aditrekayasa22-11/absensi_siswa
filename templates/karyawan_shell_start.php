<?php

/**
 * Shell karyawan: navbar atas + area konten.
 */
$user = $user ?? require_karyawan();
$activeMenu = $activeMenu ?? 'absensi';
$pageTitle = $pageTitle ?? 'Absensi';
$pageSubtitle = $pageSubtitle ?? '';

require BASE_PATH . '/templates/head.php';
?>
<div class="emp-shell">
    <header class="emp-nav">
        <div class="emp-nav__inner">
            <a class="brand brand--light" href="<?= url('karyawan/index.php') ?>">
                <span class="brand__mark"><?= icon('calendar-check') ?></span>
                <span class="brand__text">
                    <strong><?= e(config('app.name', 'PresensiKu')) ?></strong>
                    <small>Portal Karyawan</small>
                </span>
            </a>

            <nav class="emp-nav__links" aria-label="Navigasi karyawan">
                <a href="<?= url('karyawan/index.php') ?>" class="emp-link <?= $activeMenu === 'absensi' ? 'is-active' : '' ?>">
                    <?= icon('calendar-check') ?> <span>Absensi</span>
                </a>
                <a href="<?= url('karyawan/riwayat.php') ?>" class="emp-link <?= $activeMenu === 'riwayat' ? 'is-active' : '' ?>">
                    <?= icon('clock') ?> <span>Riwayat</span>
                </a>
            </nav>

            <div class="emp-nav__user">
                <span class="avatar avatar--sm <?= avatar_tone($user['id']) ?>"><?= e(initials((string) $user['nama'])) ?></span>
                <span class="emp-nav__user-text">
                    <strong><?= e($user['nama']) ?></strong>
                    <small><?= e($user['jabatan']) ?></small>
                </span>
                <form method="post" action="<?= url('logout.php') ?>">
                    <?= Csrf::field() ?>
                    <button type="submit" class="icon-btn icon-btn--ghost" title="Logout" aria-label="Logout">
                        <?= icon('logout') ?>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="page page--employee">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Portal Karyawan</p>
                <h1><?= e($pageTitle) ?></h1>
                <?php if ($pageSubtitle !== ''): ?><p class="page-heading__sub"><?= e($pageSubtitle) ?></p><?php endif; ?>
            </div>
            <div class="page-heading__aside">
                <span class="date-chip"><?= icon('calendar') ?> <?= e(tanggal_id(date('Y-m-d'), true)) ?></span>
            </div>
        </div>

        <?= render_flashes() ?>
