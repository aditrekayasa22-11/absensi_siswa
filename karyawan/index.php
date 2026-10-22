<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$user = require_karyawan();
$pdo = Database::connection();
$userId = (int) $user['id'];
$today = date('Y-m-d');
$currentTime = date('H:i:s');

$stmt = $pdo->prepare('SELECT * FROM attendance WHERE user_id = ? AND tanggal = ? LIMIT 1');
$stmt->execute([$userId, $today]);
$todayAttendance = $stmt->fetch() ?: null;

if (is_post()) {
    Csrf::verifyOrRedirect('karyawan/index.php');
    $action = input('action');

    if ($action === 'masuk') {
        if ($todayAttendance !== null) {
            flash('error', 'Anda sudah memiliki catatan absensi hari ini. Absen masuk hanya dapat dilakukan satu kali.');
            redirect('karyawan/index.php');
        }

        $status = date('H:i') > (string) config('jam_masuk_batas', '07:30') ? 'TERLAMBAT' : 'HADIR';

        try {
            $statement = $pdo->prepare(
                'INSERT INTO attendance (user_id, tanggal, jam_masuk, jam_pulang, status, keterangan, created_at, updated_at)
                 VALUES (?, ?, ?, NULL, ?, NULL, NOW(), NOW())'
            );
            $statement->execute([$userId, $today, $currentTime, $status]);
            $attendanceId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare('SELECT * FROM attendance WHERE id = ? LIMIT 1');
            $stmt->execute([$attendanceId]);
            $attendance = $stmt->fetch();

            if (is_array($attendance)) {
                $waResult = WhatsAppService::send(
                    (string) $user['no_wa'],
                    MessageTemplates::checkIn($user, $attendance),
                    'absen_masuk',
                    $userId
                );

                WhatsAppService::notifyAdmin(
                    MessageTemplates::adminAttendance($user, $attendance, $status === 'TERLAMBAT' ? 'masuk (terlambat)' : 'masuk'),
                    'absen_masuk'
                );

                flash('success', 'Absen masuk berhasil dicatat pukul ' . jam($attendance['jam_masuk']) . ' dengan status ' . status_label($status) . '.');

                if ($waResult['simulated']) {
                    flash('info', 'Notifikasi WhatsApp dicatat dalam mode simulasi.');
                } elseif (!$waResult['ok']) {
                    flash('warning', 'Absensi tersimpan, tetapi notifikasi WhatsApp gagal dikirim.');
                }
            }

            redirect('karyawan/index.php');
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                flash('error', 'Absen masuk hari ini sudah tercatat. Sistem menolak absensi ganda.');
            } else {
                flash('error', 'Gagal menyimpan absensi. Silakan coba lagi.');
            }
            redirect('karyawan/index.php');
        }
    }

    if ($action === 'pulang') {
        if ($todayAttendance === null || empty($todayAttendance['jam_masuk'])) {
            flash('error', 'Anda belum melakukan absen masuk hari ini. Absen pulang tidak dapat dilakukan.');
            redirect('karyawan/index.php');
        }

        if (!empty($todayAttendance['jam_pulang'])) {
            flash('error', 'Absen pulang hari ini sudah tercatat pukul ' . jam((string) $todayAttendance['jam_pulang']) . '.');
            redirect('karyawan/index.php');
        }

        $statement = $pdo->prepare(
            'UPDATE attendance SET jam_pulang = ?, updated_at = NOW()
             WHERE id = ? AND user_id = ? AND jam_pulang IS NULL'
        );
        $statement->execute([$currentTime, (int) $todayAttendance['id'], $userId]);

        if ($statement->rowCount() < 1) {
            flash('error', 'Absen pulang gagal dicatat. Mungkin sudah tercatat sebelumnya.');
            redirect('karyawan/index.php');
        }

        $stmt = $pdo->prepare('SELECT * FROM attendance WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $todayAttendance['id']]);
        $attendance = $stmt->fetch();

        if (is_array($attendance)) {
            $waResult = WhatsAppService::send(
                (string) $user['no_wa'],
                MessageTemplates::checkOut($user, $attendance),
                'absen_pulang',
                $userId
            );

            WhatsAppService::notifyAdmin(
                MessageTemplates::adminAttendance($user, $attendance, 'pulang'),
                'absen_pulang'
            );

            flash('success', 'Absen pulang berhasil dicatat pukul ' . jam($attendance['jam_pulang']) . '.');

            if ($waResult['simulated']) {
                flash('info', 'Notifikasi WhatsApp dicatat dalam mode simulasi.');
            } elseif (!$waResult['ok']) {
                flash('warning', 'Absensi pulang tersimpan, tetapi notifikasi WhatsApp gagal dikirim.');
            }
        }

        redirect('karyawan/index.php');
    }

    if ($action === 'izin') {
        $status = strtoupper(input('status'));
        $keterangan = trim((string) ($_POST['keterangan'] ?? ''));

        if (!in_array($status, ['IZIN', 'SAKIT'], true)) {
            flash('error', 'Status pengajuan tidak valid.');
            redirect('karyawan/index.php');
        }

        if ($todayAttendance !== null) {
            flash('error', 'Anda sudah memiliki catatan absensi hari ini.');
            redirect('karyawan/index.php');
        }

        if (mb_strlen($keterangan) < 5) {
            flash('error', 'Keterangan wajib diisi minimal 5 karakter.');
            redirect('karyawan/index.php');
        }

        if (mb_strlen($keterangan) > 255) {
            flash('error', 'Keterangan maksimal 255 karakter.');
            redirect('karyawan/index.php');
        }

        try {
            $statement = $pdo->prepare(
                'INSERT INTO attendance (user_id, tanggal, jam_masuk, jam_pulang, status, keterangan, created_at, updated_at)
                 VALUES (?, ?, NULL, NULL, ?, ?, NOW(), NOW())'
            );
            $statement->execute([$userId, $today, $status, $keterangan]);
            $attendanceId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare('SELECT * FROM attendance WHERE id = ? LIMIT 1');
            $stmt->execute([$attendanceId]);
            $attendance = $stmt->fetch();

            if (is_array($attendance)) {
                $waResult = WhatsAppService::send(
                    (string) $user['no_wa'],
                    MessageTemplates::leave($user, $attendance),
                    strtolower($status),
                    $userId
                );

                WhatsAppService::notifyAdmin(
                    MessageTemplates::adminAttendance($user, $attendance, 'pengajuan ' . strtolower($status)),
                    strtolower($status)
                );

                flash('success', 'Pengajuan ' . status_label($status) . ' berhasil dicatat untuk hari ini.');

                if ($waResult['simulated']) {
                    flash('info', 'Notifikasi WhatsApp dicatat dalam mode simulasi.');
                } elseif (!$waResult['ok']) {
                    flash('warning', 'Pengajuan tersimpan, tetapi notifikasi WhatsApp gagal dikirim.');
                }
            }

            redirect('karyawan/index.php');
        } catch (PDOException $exception) {
            flash('error', 'Pengajuan gagal disimpan. Anda mungkin sudah memiliki catatan hari ini.');
            redirect('karyawan/index.php');
        }
    }

    flash('error', 'Aksi absensi tidak dikenali.');
    redirect('karyawan/index.php');
}

$stmt = $pdo->prepare('SELECT * FROM attendance WHERE user_id = ? AND tanggal = ? LIMIT 1');
$stmt->execute([$userId, $today]);
$todayAttendance = $stmt->fetch() ?: null;

$canCheckIn = $todayAttendance === null;
$canCheckOut = $todayAttendance !== null
    && !empty($todayAttendance['jam_masuk'])
    && empty($todayAttendance['jam_pulang']);

$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$stmt = $pdo->prepare(
    'SELECT status, COUNT(*) AS total
     FROM attendance
     WHERE user_id = ? AND tanggal BETWEEN ? AND ?
     GROUP BY status'
);
$stmt->execute([$userId, $monthStart, $monthEnd]);
$monthCounts = ['HADIR' => 0, 'TERLAMBAT' => 0, 'IZIN' => 0, 'SAKIT' => 0];
foreach ($stmt->fetchAll() as $row) {
    $monthCounts[(string) $row['status']] = (int) $row['total'];
}

$stmt = $pdo->prepare(
    'SELECT * FROM attendance WHERE user_id = ? ORDER BY tanggal DESC LIMIT 6'
);
$stmt->execute([$userId]);
$recentHistory = $stmt->fetchAll();

$hour = (int) date('H');
if ($hour < 11) {
    $greeting = 'Selamat pagi';
} elseif ($hour < 15) {
    $greeting = 'Selamat siang';
} elseif ($hour < 18) {
    $greeting = 'Selamat sore';
} else {
    $greeting = 'Selamat malam';
}

$firstName = explode(' ', (string) $user['nama'])[0];
$pageTitle = 'Absensi Hari Ini';
$pageSubtitle = 'Catat kehadiran Anda dengan satu klik. Notifikasi dikirim otomatis ke WhatsApp.';
$activeMenu = 'absensi';

require BASE_PATH . '/templates/karyawan_shell_start.php';
?>

<section class="attendance-hero">
    <div>
        <p class="attendance-hero__greeting">
            <?= icon('sparkles') ?> <?= e($greeting) ?>, <?= e($firstName) ?>
        </p>
        <h2>Siap memulai hari kerja?</h2>
        <div class="attendance-hero__meta">
            <span><?= e($user['jabatan']) ?></span>
            <span class="attendance-hero__dot"></span>
            <span>NIK <?= e($user['nik']) ?></span>
            <?php if ($todayAttendance !== null): ?>
                <span class="attendance-hero__dot"></span>
                <span class="hero-status"><?= e(status_label((string) $todayAttendance['status'])) ?><?= !empty($todayAttendance['jam_masuk']) ? ' · ' . e(jam((string) $todayAttendance['jam_masuk'])) : '' ?></span>
            <?php else: ?>
                <span class="attendance-hero__dot"></span>
                <span class="hero-status">Belum absen hari ini</span>
            <?php endif; ?>
        </div>

        <div class="attendance-clock">
            <span class="attendance-clock__time" data-clock><?= date('H:i:s') ?></span>
            <span class="attendance-clock__zone">WIB</span>
        </div>
        <p class="attendance-clock__date" data-clock-date><?= e(tanggal_id($today, true)) ?></p>
    </div>

    <div class="attendance-actions">
        <form method="post" action="<?= url('karyawan/index.php') ?>" data-single-submit>
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="masuk">
            <button type="submit" class="btn btn--absen-in btn--block" <?= $canCheckIn ? '' : 'disabled' ?>>
                <?= icon('check-circle') ?>
                <?= $canCheckIn ? 'ABSEN MASUK' : 'SUDAH ABSEN MASUK' ?>
            </button>
        </form>

        <form method="post" action="<?= url('karyawan/index.php') ?>" data-single-submit>
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="pulang">
            <button type="submit" class="btn btn--absen-out btn--block" <?= $canCheckOut ? '' : 'disabled' ?>>
                <?= icon('logout') ?>
                <?= $canCheckOut ? 'ABSEN PULANG' : (!empty($todayAttendance['jam_pulang']) ? 'SUDAH ABSEN PULANG' : 'ABSEN PULANG') ?>
            </button>
        </form>

        <p class="attendance-actions__note">
            <?php if (($todayAttendance['status'] ?? '') === 'IZIN'): ?>
                Anda tercatat <strong>IZIN</strong> hari ini.
            <?php elseif (($todayAttendance['status'] ?? '') === 'SAKIT'): ?>
                Anda tercatat <strong>SAKIT</strong> hari ini.
            <?php elseif ($canCheckIn): ?>
                Absen masuk hanya dapat dilakukan satu kali per hari.
            <?php elseif ($canCheckOut): ?>
                Jangan lupa absen pulang sebelum meninggalkan kantor.
            <?php else: ?>
                Semua absensi hari ini sudah lengkap. Terima kasih!
            <?php endif; ?>
        </p>
    </div>
</section>

<div class="grid grid--2-1 section-gap">
    <section class="card">
        <div class="card__head">
            <div>
                <h2 class="card__title">Status Absensi Hari Ini</h2>
                <p class="card__sub"><?= e(tanggal_id($today, true)) ?></p>
            </div>
            <span class="badge badge--<?= $todayAttendance ? status_class((string) $todayAttendance['status']) : 'neutral' ?>">
                <?= $todayAttendance ? e(status_label((string) $todayAttendance['status'])) : 'Belum ada data' ?>
            </span>
        </div>
        <div class="card__body">
            <div class="today-grid">
                <div class="today-item">
                    <span class="today-item__icon today-item__icon--green"><?= icon('check-circle') ?></span>
                    <span>
                        <span class="today-item__label">Jam Masuk</span>
                        <span class="today-item__value"><?= e(jam($todayAttendance['jam_masuk'] ?? null)) ?></span>
                        <span class="today-item__sub">Batas <?= e(config('jam_masuk_batas', '07:30')) ?> WIB</span>
                    </span>
                </div>
                <div class="today-item">
                    <span class="today-item__icon today-item__icon--rose"><?= icon('logout') ?></span>
                    <span>
                        <span class="today-item__label">Jam Pulang</span>
                        <span class="today-item__value"><?= e(jam($todayAttendance['jam_pulang'] ?? null)) ?></span>
                        <span class="today-item__sub"><?= !empty($todayAttendance['jam_pulang']) ? 'Absensi lengkap' : 'Belum absen pulang' ?></span>
                    </span>
                </div>
                <div class="today-item">
                    <span class="today-item__icon today-item__icon--amber"><?= icon('clock') ?></span>
                    <span>
                        <span class="today-item__label">Durasi Kerja</span>
                        <span class="today-item__value">
                            <?php if (!empty($todayAttendance['jam_masuk']) && !empty($todayAttendance['jam_pulang'])): ?>
                                <?= selisih_menit((string) $todayAttendance['jam_masuk'], (string) $todayAttendance['jam_pulang']) ?> <span style="font-size:10px;color:#64748b">menit</span>
                            <?php else: ?>
                                &mdash;
                            <?php endif; ?>
                        </span>
                        <span class="today-item__sub"><?= !empty($todayAttendance['keterangan']) ? e($todayAttendance['keterangan']) : 'Kehadiran harian' ?></span>
                    </span>
                </div>
            </div>

            <div class="divider"></div>

            <div class="info-list">
                <div class="info-row">
                    <span class="info-row__icon"><?= icon('user') ?></span>
                    <span><span class="info-row__label">Nama</span><span class="info-row__value"><?= e($user['nama']) ?></span></span>
                </div>
                <div class="info-row">
                    <span class="info-row__icon"><?= icon('id-card') ?></span>
                    <span><span class="info-row__label">NIK</span><span class="info-row__value"><?= e($user['nik']) ?></span></span>
                </div>
                <div class="info-row">
                    <span class="info-row__icon"><?= icon('phone') ?></span>
                    <span><span class="info-row__label">WhatsApp notifikasi</span><span class="info-row__value"><?= e(phone_display((string) $user['no_wa'])) ?></span></span>
                </div>
            </div>

            <?php if ($canCheckIn): ?>
                <div class="leave-box mt-2">
                    <div class="leave-box__text">
                        <strong>Tidak dapat hadir hari ini?</strong>
                        <span>Ajukan izin atau sakit sebelum melakukan absensi masuk.</span>
                    </div>
                    <div class="leave-box__actions">
                        <button type="button" class="btn btn--secondary btn--sm" data-open-modal="leaveModal" data-leave-status="IZIN"><?= icon('inbox') ?> Ajukan Izin</button>
                        <button type="button" class="btn btn--secondary btn--sm" data-open-modal="leaveModal" data-leave-status="SAKIT"><?= icon('info') ?> Ajukan Sakit</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <aside class="grid" style="align-content:start">
        <section class="card">
            <div class="card__head">
                <div>
                    <h2 class="card__title">Ringkasan Bulan Ini</h2>
                    <p class="card__sub"><?= e(tanggal_id($monthStart)) ?> &ndash; <?= e(tanggal_id($monthEnd)) ?></p>
                </div>
            </div>
            <div class="card__body">
                <div class="mini-stats" style="grid-template-columns:1fr 1fr">
                    <div class="mini-stat">
                        <div class="mini-stat__label">Hadir</div>
                        <div class="mini-stat__value" style="color:#047857"><?= $monthCounts['HADIR'] ?></div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat__label">Terlambat</div>
                        <div class="mini-stat__value" style="color:#b45309"><?= $monthCounts['TERLAMBAT'] ?></div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat__label">Izin</div>
                        <div class="mini-stat__value" style="color:#1d4ed8"><?= $monthCounts['IZIN'] ?></div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat__label">Sakit</div>
                        <div class="mini-stat__value" style="color:#6d28d9"><?= $monthCounts['SAKIT'] ?></div>
                    </div>
                </div>
                <a href="<?= url('karyawan/riwayat.php') ?>" class="btn btn--secondary btn--sm btn--block mt-2"><?= icon('clock') ?> Lihat Riwayat Lengkap</a>
            </div>
        </section>

        <section class="card">
            <div class="card__body">
                <div class="flex-between mb-2">
                    <strong class="text-sm">Notifikasi WhatsApp</strong>
                    <span class="badge badge--<?= wa_mode_active() ? 'success' : 'info' ?>"><?= wa_mode_active() ? 'Aktif' : 'Simulasi' ?></span>
                </div>
                <p class="text-sm text-muted" style="line-height:1.6">
                    Setiap absensi masuk dan pulang akan mengirim pesan konfirmasi ke nomor WhatsApp Anda.
                </p>
            </div>
        </section>
    </aside>
</div>

<section class="card section-gap">
    <div class="card__head">
        <div>
            <h2 class="card__title">Riwayat Terbaru</h2>
            <p class="card__sub">Enam catatan absensi terakhir Anda.</p>
        </div>
        <a href="<?= url('karyawan/riwayat.php') ?>" class="btn btn--secondary btn--sm">Lihat semua <?= icon('arrow-right') ?></a>
    </div>

    <?php if ($recentHistory === []): ?>
        <div class="empty-state">
            <div class="empty-state__icon"><?= icon('calendar') ?></div>
            <h3>Belum ada riwayat absensi</h3>
            <p>Riwayat akan muncul setelah Anda melakukan absensi masuk pertama.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Masuk</th>
                        <th>Pulang</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentHistory as $row): ?>
                        <tr>
                            <td>
                                <span style="font-weight:600"><?= e(tanggal_id((string) $row['tanggal'])) ?></span>
                                <div class="muted" style="font-size:10.5px"><?= e(hari_id((string) $row['tanggal'])) ?></div>
                            </td>
                            <td class="num"><?= e(jam($row['jam_masuk'])) ?></td>
                            <td class="num"><?= e(jam($row['jam_pulang'])) ?></td>
                            <td><span class="badge badge--<?= status_class((string) $row['status']) ?>"><?= e(status_label((string) $row['status'])) ?></span></td>
                            <td class="muted"><?= e($row['keterangan'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="modal" id="leaveModal" hidden>
    <div class="modal__backdrop"></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="leaveModalTitle">
        <form method="post" action="<?= url('karyawan/index.php') ?>" data-single-submit>
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="izin">
            <div class="modal__head">
                <span class="modal__icon modal__icon--warning"><?= icon('inbox') ?></span>
                <div class="modal__titles">
                    <h3 id="leaveModalTitle">Ajukan Izin / Sakit</h3>
                    <p>Pengajuan berlaku untuk hari ini dan akan dikirim ke WhatsApp admin.</p>
                </div>
                <button type="button" class="modal__close" data-close-modal aria-label="Tutup"><?= icon('close') ?></button>
            </div>
            <div class="modal__body">
                <div class="field mb-2">
                    <label for="leaveStatus">Status <span class="req">*</span></label>
                    <select id="leaveStatus" name="status" class="select" required>
                        <option value="IZIN">Izin</option>
                        <option value="SAKIT">Sakit</option>
                    </select>
                </div>
                <div class="field">
                    <label for="keterangan">Keterangan <span class="req">*</span></label>
                    <textarea id="keterangan" name="keterangan" class="textarea" rows="4" minlength="5" maxlength="255" placeholder="Jelaskan alasan izin atau sakit..." required data-counter="#leaveCounter"></textarea>
                    <div class="flex-between">
                        <span class="field__hint">Minimal 5 karakter.</span>
                        <span class="field__hint" id="leaveCounter">0 karakter</span>
                    </div>
                </div>
            </div>
            <div class="modal__foot">
                <button type="button" class="btn btn--secondary" data-close-modal>Batal</button>
                <button type="submit" class="btn btn--primary"><?= icon('send') ?> Kirim Pengajuan</button>
            </div>
        </form>
    </div>
</div>

<?php require BASE_PATH . '/templates/karyawan_shell_end.php'; ?>
