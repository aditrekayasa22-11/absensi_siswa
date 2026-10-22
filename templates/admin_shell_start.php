<?php

/**
 * Shell admin: sidebar + topbar + area konten.
 *
 * Variabel yang disiapkan halaman:
 * - $user (array)
 * - $activeMenu (string)
 * - $pageTitle (string)
 * - $pageSubtitle (string, opsional)
 */
$user = $user ?? require_admin();
$activeMenu = $activeMenu ?? 'dashboard';
$pageSubtitle = $pageSubtitle ?? '';

$menuItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'admin/index.php', 'icon' => 'dashboard'],
    ['key' => 'karyawan', 'label' => 'Karyawan', 'href' => 'admin/karyawan.php', 'icon' => 'users'],
    ['key' => 'absensi', 'label' => 'Absensi', 'href' => 'admin/absensi.php', 'icon' => 'calendar-check'],
    ['key' => 'laporan', 'label' => 'Laporan', 'href' => 'admin/laporan.php', 'icon' => 'report'],
    ['key' => 'wa', 'label' => 'WhatsApp', 'href' => 'admin/wa.php', 'icon' => 'whatsapp'],
];

require BASE_PATH . '/templates/head.php';
?>
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Navigasi admin">
        <a class="brand" href="<?= url('admin/index.php') ?>">
            <span class="brand__mark"><?= icon('calendar-check') ?></span>
            <span class="brand__text">
                <strong><?= e(config('app.name', 'PresensiKu')) ?></strong>
                <small>Admin Panel</small>
            </span>
        </a>

        <nav class="sidebar__nav">
            <p class="sidebar__label">Menu utama</p>
            <?php foreach ($menuItems as $item): ?>
                <a href="<?= url($item['href']) ?>"
                   class="nav-link <?= $activeMenu === $item['key'] ? 'is-active' : '' ?>">
                    <?= icon($item['icon'], 'nav-link__icon') ?>
                    <span><?= e($item['label']) ?></span>
                    <?php if ($activeMenu === $item['key']): ?><span class="nav-link__dot"></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar__spacer"></div>

        <div class="sidebar__status">
            <span class="pulse <?= wa_mode_active() ? 'pulse--live' : 'pulse--sim' ?>"></span>
            <div>
                <strong><?= wa_mode_active() ? 'Fonnte aktif' : 'Mode simulasi' ?></strong>
                <small><?= wa_mode_active() ? 'Notifikasi dikirim ke WhatsApp' : 'Token belum diaktifkan' ?></small>
            </div>
        </div>

        <div class="sidebar__footer">
            <div class="sidebar__user">
                <span class="avatar <?= avatar_tone($user['id']) ?>"><?= e(initials((string) $user['nama'])) ?></span>
                <span class="sidebar__user-text">
                    <strong><?= e($user['nama']) ?></strong>
                    <small>Administrator</small>
                </span>
            </div>
            <form method="post" action="<?= url('logout.php') ?>">
                <?= Csrf::field() ?>
                <button type="submit" class="sidebar__logout">
                    <?= icon('logout') ?>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="sidebar-overlay" data-sidebar-close></div>

    <div class="app-main">
        <header class="topbar">
            <button type="button" class="icon-btn topbar__burger" data-sidebar-toggle aria-label="Buka menu">
                <?= icon('menu') ?>
            </button>
            <div class="topbar__titles">
                <h1><?= e($pageTitle) ?></h1>
                <?php if ($pageSubtitle !== ''): ?><p><?= e($pageSubtitle) ?></p><?php endif; ?>
            </div>
            <div class="topbar__meta">
                <span class="topbar__date">
                    <?= icon('calendar') ?>
                    <?= e(tanggal_id(date('Y-m-d'), true)) ?>
                </span>
                <div class="topbar__profile">
                    <span class="avatar avatar--sm <?= avatar_tone($user['id']) ?>"><?= e(initials((string) $user['nama'])) ?></span>
                    <span>
                        <strong><?= e($user['nama']) ?></strong>
                        <small>Admin</small>
                    </span>
                </div>
            </div>
        </header>

        <main class="page">
            <?= render_flashes() ?>
