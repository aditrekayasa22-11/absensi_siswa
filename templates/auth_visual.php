<?php
/** Partial panel visual halaman auth. */
?>
<aside class="auth-visual">
    <div class="auth-aurora" aria-hidden="true">
        <span class="auth-aurora__blob auth-aurora__blob--indigo"></span>
        <span class="auth-aurora__blob auth-aurora__blob--emerald"></span>
        <span class="auth-aurora__blob auth-aurora__blob--cyan"></span>
    </div>

    <a class="brand brand--light" href="<?= url('login.php') ?>">
        <span class="brand__mark"><?= icon('calendar-check') ?></span>
        <span class="brand__text">
            <strong><?= e(config('app.name', 'PresensiKu')) ?></strong>
            <small>Sistem Absensi Karyawan</small>
        </span>
    </a>

    <div class="auth-toast" aria-hidden="true">
        <span class="auth-toast__icon"><?= icon('whatsapp') ?></span>
        <span class="auth-toast__body">
            <strong>Notifikasi terkirim</strong>
            <span>Absensi Budi &middot; 07.02 WIB</span>
        </span>
        <span class="auth-toast__check"><?= icon('check') ?></span>
    </div>

    <div class="auth-visual__content">
        <span class="auth-visual__badge">
            <span class="auth-visual__badge-dot"></span>
            <?= wa_mode_active() ? 'Terhubung dengan WhatsApp' : 'Notifikasi WhatsApp Otomatis' ?>
        </span>
        <h1>
            Kelola kehadiran lebih
            <span class="auth-rotate" data-rotate-words="mudah|cepat|cerdas|aman">
                <span class="auth-rotate__word">mudah</span>
            </span>
            dan otomatis.
        </h1>
        <p class="auth-visual__lead">
            Catat absensi masuk dan pulang, pantau kehadiran karyawan, dan kirim notifikasi
            WhatsApp secara otomatis &mdash; semuanya dalam satu dashboard.
        </p>

        <div class="auth-features">
            <div class="auth-feature">
                <span class="auth-feature__check"><?= icon('check') ?></span>
                <span>Absensi masuk &amp; pulang dengan validasi anti duplikasi.</span>
            </div>
            <div class="auth-feature">
                <span class="auth-feature__check"><?= icon('check') ?></span>
                <span>Notifikasi WhatsApp otomatis melalui integrasi Fonnte.</span>
            </div>
            <div class="auth-feature">
                <span class="auth-feature__check"><?= icon('check') ?></span>
                <span>Laporan kehadiran lengkap dan siap dicetak.</span>
            </div>
        </div>

        <div class="auth-demo-card">
            <div class="auth-demo-card__top">
                <span class="auth-demo-card__live"><i></i> Presensi hari ini</span>
                <span class="auth-demo-card__live-chip">Live</span>
            </div>
            <div class="auth-demo-card__time"><span data-clock>07:05:00</span> <small>WIB</small></div>
            <div class="auth-demo-card__date" data-clock-date>Kamis, 22 Oktober 2026</div>
            <div class="auth-demo-card__status">Siap melakukan absensi</div>

            <div class="auth-demo-card__divider"></div>

            <div class="auth-demo-card__stats">
                <div><strong>4</strong><span>Hadir</span></div>
                <div><strong>1</strong><span>Terlambat</span></div>
                <div><strong>0</strong><span>Izin</span></div>
            </div>

            <div class="auth-demo-card__meter">
                <span class="auth-demo-card__meter-bar"><i></i></span>
                <span class="auth-demo-card__meter-label">80% kehadiran hari ini</span>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/auth_ticker.php'; ?>

    <div class="auth-visual__footer">
        <span>&copy; <?= date('Y') ?> <?= e(config('app.name', 'PresensiKu')) ?></span>
        <span>PHP Native &middot; MySQL &middot; Fonnte API</span>
    </div>
</aside>
