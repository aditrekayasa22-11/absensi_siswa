<?php
/**
 * Running text (marquee) untuk halaman autentikasi.
 * Dua grup identik dipakai agar animasi berulang tanpa jeda.
 */
$tickerItems = [
    ['icon' => 'calendar-check', 'text' => 'Presensi real-time'],
    ['icon' => 'whatsapp', 'text' => 'Notifikasi WhatsApp otomatis'],
    ['icon' => 'trending', 'text' => 'Rekap kehadiran bulanan'],
    ['icon' => 'clock', 'text' => 'Monitoring jam masuk'],
    ['icon' => 'shield', 'text' => 'Data aman & terenkripsi'],
    ['icon' => 'check-circle', 'text' => 'Laporan siap cetak'],
];
?>
<div class="auth-ticker" aria-hidden="true">
    <div class="auth-ticker__track">
        <?php for ($group = 0; $group < 2; $group++): ?>
            <div class="auth-ticker__group">
                <?php foreach ($tickerItems as $item): ?>
                    <span class="auth-ticker__item">
                        <?= icon($item['icon']) ?>
                        <span><?= e($item['text']) ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>
