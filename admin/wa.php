<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$user = require_admin();
$pdo = Database::connection();

if (is_post()) {
    Csrf::verifyOrRedirect('admin/wa.php');

    $target = input('target');
    $message = trim((string) ($_POST['message'] ?? ''));

    if (!WhatsAppService::isValidNumber($target)) {
        flash('error', 'Nomor tujuan tidak valid. Gunakan format 08xxxxxxxxxx atau 628xxxxxxxxxx.');
        redirect('admin/wa.php');
    }

    if ($message === '') {
        $message = MessageTemplates::test((string) $user['nama']);
    }

    if (mb_strlen($message) > 4000) {
        flash('error', 'Pesan terlalu panjang. Maksimal 4000 karakter.');
        redirect('admin/wa.php');
    }

    $result = WhatsAppService::send($target, $message, 'test', null);

    if ($result['simulated']) {
        flash('info', 'Pesan tercatat dalam mode simulasi. Aktifkan FONNTE_ENABLED dan isi FONNTE_TOKEN di .env agar benar-benar terkirim.');
    } elseif ($result['ok']) {
        flash('success', 'Pesan uji berhasil dikirim ke WhatsApp ' . phone_display($result['to']) . '.');
    } else {
        flash('error', 'Gagal mengirim pesan: ' . $result['message']);
    }

    redirect('admin/wa.php');
}

$statusFilter = strtolower(trim((string) ($_GET['status'] ?? '')));
if (!in_array($statusFilter, ['sent', 'failed', 'simulated'], true)) {
    $statusFilter = '';
}
$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;

$where = '1 = 1';
$params = [];

if ($statusFilter !== '') {
    $where .= ' AND w.status = ?';
    $params[] = $statusFilter;
}

if ($q !== '') {
    $where .= ' AND (w.penerima LIKE ? OR w.tipe LIKE ? OR u.nama LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$countStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM whatsapp_logs w LEFT JOIN users u ON u.id = w.user_id WHERE ' . $where
);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    'SELECT w.*, u.nama AS user_nama
     FROM whatsapp_logs w
     LEFT JOIN users u ON u.id = w.user_id
     WHERE ' . $where . '
     ORDER BY w.created_at DESC, w.id DESC
     LIMIT ? OFFSET ?'
);
foreach ($params as $index => $value) {
    $stmt->bindValue($index + 1, $value, PDO::PARAM_STR);
}
$stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

$statusCounts = ['sent' => 0, 'failed' => 0, 'simulated' => 0, 'total' => 0];
$stmt = $pdo->query('SELECT status, COUNT(*) AS total FROM whatsapp_logs GROUP BY status');
foreach ($stmt->fetchAll() as $row) {
    $statusCounts[(string) $row['status']] = (int) $row['total'];
    $statusCounts['total'] += (int) $row['total'];
}

$pageTitle = 'WhatsApp Center';
$pageSubtitle = 'Uji koneksi Fonnte dan pantau seluruh notifikasi WhatsApp yang dikirim sistem.';
$activeMenu = 'wa';

$defaultMessage = MessageTemplates::test((string) $user['nama']);
$filterQuery = array_filter([
    'status' => $statusFilter,
    'q' => $q,
], static fn ($value) => $value !== '');

require BASE_PATH . '/templates/admin_shell_start.php';
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">Integrasi Fonnte</p>
        <h1>WhatsApp Center</h1>
        <p class="page-heading__sub">Kirim pesan uji, lihat status koneksi, dan audit semua notifikasi yang dihasilkan aplikasi.</p>
    </div>
    <div class="page-heading__actions">
        <span class="badge badge--<?= wa_mode_active() ? 'success' : 'info' ?>">
            <?= wa_mode_active() ? 'Fonnte aktif' : 'Mode simulasi' ?>
        </span>
    </div>
</div>

<div class="wa-status-card <?= wa_mode_active() ? '' : 'wa-status-card--sim' ?>">
    <span class="wa-status-card__icon"><?= icon('whatsapp') ?></span>
    <div class="wa-status-card__text">
        <strong><?= wa_mode_active() ? 'Integrasi WhatsApp siap digunakan' : 'Integrasi WhatsApp belum diaktifkan' ?></strong>
        <span>
            <?= wa_mode_active()
                ? 'Pesan akan dikirim melalui API Fonnte. Token dibaca aman dari file .env.'
                : 'Pesan hanya dicatat sebagai simulasi. Isi FONNTE_TOKEN di .env lalu ubah FONNTE_ENABLED=true.' ?>
        </span>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <span class="badge badge--neutral"><?= icon('lock') ?> Token: <?= trim((string) config('fonnte.token')) !== '' ? 'terisi' : 'kosong' ?></span>
        <span class="badge badge--neutral"><?= icon('phone') ?> Admin: <?= e(phone_display((string) config('admin_wa', ''))) ?></span>
        <span class="badge badge--neutral"><?= icon('shield') ?> API key: <?= trim((string) config('api_key')) !== '' ? 'aktif' : 'kosong' ?></span>
    </div>
</div>

<div class="mini-stats section-gap">
    <div class="mini-stat">
        <div class="mini-stat__label">Total Pesan</div>
        <div class="mini-stat__value"><?= number_format($statusCounts['total'], 0, ',', '.') ?> <span>log</span></div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat__label">Terkirim</div>
        <div class="mini-stat__value" style="color:#047857"><?= number_format($statusCounts['sent'], 0, ',', '.') ?> <span>pesan</span></div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat__label">Simulasi</div>
        <div class="mini-stat__value" style="color:#0369a1"><?= number_format($statusCounts['simulated'], 0, ',', '.') ?> <span>pesan</span></div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat__label">Gagal</div>
        <div class="mini-stat__value" style="color:#be123c"><?= number_format($statusCounts['failed'], 0, ',', '.') ?> <span>pesan</span></div>
    </div>
</div>

<div class="grid grid--2-1 section-gap" style="align-items:start">
    <section class="card">
        <div class="card__head">
            <div>
                <h2 class="card__title">Tes Pengiriman Pesan</h2>
                <p class="card__sub">Gunakan fitur ini untuk memastikan token Fonnte dan nomor tujuan bekerja dengan baik.</p>
            </div>
            <span class="badge badge--<?= wa_mode_active() ? 'success' : 'info' ?>"><?= wa_mode_active() ? 'Live' : 'Simulasi' ?></span>
        </div>
        <div class="card__body">
            <form method="post" action="<?= url('admin/wa.php') ?>" data-single-submit>
                <?= Csrf::field() ?>

                <div class="form-grid">
                    <div class="field field--full">
                        <label for="target">Nomor tujuan <span class="req">*</span></label>
                        <input type="tel" id="target" name="target" class="input" value="<?= e((string) config('admin_wa', '')) ?>" placeholder="628xxxxxxxxxx" required>
                        <span class="field__hint">Gunakan format 08xxxxxxxxxx atau 628xxxxxxxxxx. Untuk uji awal, gunakan satu nomor Anda sendiri.</span>
                    </div>

                    <div class="field field--full">
                        <label for="message">Isi pesan</label>
                        <textarea id="message" name="message" class="textarea" rows="6" maxlength="4000" data-counter="#messageCounter"><?= e($defaultMessage) ?></textarea>
                        <div class="flex-between">
                            <span class="field__hint">Kosongkan untuk menggunakan pesan uji bawaan.</span>
                            <span class="field__hint" id="messageCounter">0 karakter</span>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn--primary"><?= icon('send') ?> Kirim Pesan Uji</button>
                </div>
            </form>
        </div>
    </section>

    <aside class="grid" style="align-content:start">
        <section class="card">
            <div class="card__head">
                <div>
                    <h2 class="card__title">Alur Integrasi</h2>
                    <p class="card__sub">PHP &rarr; HTTP Request &rarr; Fonnte &rarr; WhatsApp.</p>
                </div>
            </div>
            <div class="card__body">
                <div class="info-list">
                    <div class="info-row">
                        <span class="info-row__icon"><?= icon('send') ?></span>
                        <span><span class="info-row__label">1. Aplikasi PHP</span><span class="info-row__value">Menyiapkan pesan dan nomor tujuan.</span></span>
                    </div>
                    <div class="info-row">
                        <span class="info-row__icon"><?= icon('refresh') ?></span>
                        <span><span class="info-row__label">2. HTTP Request</span><span class="info-row__value">cURL POST ke api.fonnte.com/send.</span></span>
                    </div>
                    <div class="info-row">
                        <span class="info-row__icon"><?= icon('shield') ?></span>
                        <span><span class="info-row__label">3. Fonnte API</span><span class="info-row__value">Token diverifikasi dari header Authorization.</span></span>
                    </div>
                    <div class="info-row">
                        <span class="info-row__icon"><?= icon('whatsapp') ?></span>
                        <span><span class="info-row__label">4. WhatsApp</span><span class="info-row__value">Pesan diterima karyawan atau admin.</span></span>
                    </div>
                </div>

                <div class="alert alert--warning mt-2" style="margin-bottom:0">
                    <span class="alert__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg></span>
                    <span class="alert__text">Jangan membagikan token Fonnte atau API key. Simpan hanya di file <code>.env</code> yang tidak diunggah ke repository.</span>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card__head">
                <div>
                    <h2 class="card__title">API Pengiriman</h2>
                    <p class="card__sub">Endpoint khusus integrasi eksternal.</p>
                </div>
            </div>
            <div class="card__body">
                <div class="code-block">
                    <code>POST <?= e(url('api/send_wa.php')) ?>

X-API-KEY: &bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;

{
  "target": "628xxxxxxxxxx",
  "message": "Pesan uji"
}</code>
                </div>
                <p class="field__hint mt-1">API key diatur pada <code>API_KEY</code> di file .env. Endpoint menolak permintaan tanpa key yang sesuai.</p>
            </div>
        </section>
    </aside>
</div>

<section class="card section-gap">
    <div class="table-toolbar">
        <div class="table-toolbar__left">
            <span class="table-toolbar__title">Log WhatsApp</span>
            <span class="table-toolbar__hint"><?= number_format($total, 0, ',', '.') ?> pesan</span>
        </div>
        <div class="table-toolbar__right">
            <form method="get" action="<?= url('admin/wa.php') ?>" style="display:flex;gap:7px;flex-wrap:wrap">
                <select name="status" class="select" style="min-height:38px;font-size:12.5px;min-width:130px">
                    <option value="">Semua status</option>
                    <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?>>Terkirim</option>
                    <option value="simulated" <?= $statusFilter === 'simulated' ? 'selected' : '' ?>>Simulasi</option>
                    <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Gagal</option>
                </select>
                <div class="search-box" style="min-width:180px">
                    <?= icon('search') ?>
                    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari penerima / tipe...">
                </div>
                <button type="submit" class="btn btn--secondary btn--sm"><?= icon('filter') ?> Filter</button>
                <?php if ($statusFilter !== '' || $q !== ''): ?>
                    <a href="<?= url('admin/wa.php') ?>" class="btn btn--ghost btn--sm">Reset</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if ($logs === []): ?>
        <div class="empty-state">
            <div class="empty-state__icon"><?= icon('whatsapp') ?></div>
            <h3>Belum ada log WhatsApp</h3>
            <p>Notifikasi registrasi, perubahan data, penghapusan, dan absensi akan tercatat di sini.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Penerima</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Pesan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="nowrap">
                                <span class="num" style="font-weight:600"><?= e(date('d/m/Y', strtotime((string) $log['created_at']))) ?></span>
                                <div class="muted" style="font-size:10.5px"><?= e(date('H:i', strtotime((string) $log['created_at']))) ?> WIB</div>
                            </td>
                            <td>
                                <div class="person">
                                    <span class="avatar avatar--sm <?= avatar_tone($log['penerima']) ?>"><?= icon('whatsapp') ?></span>
                                    <span class="person__text">
                                        <span class="person__name" style="font-size:11.5px"><?= e($log['user_nama'] ?: phone_display((string) $log['penerima'])) ?></span>
                                        <span class="person__meta"><?= e(phone_display((string) $log['penerima'])) ?></span>
                                    </span>
                                </div>
                            </td>
                            <td><span class="badge badge--neutral badge--plain"><?= e(wa_type_label((string) $log['tipe'])) ?></span></td>
                            <td>
                                <span class="badge badge--<?= wa_status_class((string) $log['status']) ?>" title="<?= e((string) ($log['respons'] ?? '')) ?>">
                                    <?= e(wa_status_label((string) $log['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="wa-log-message">
                                    <?= e(mb_substr((string) $log['pesan'], 0, 150)) ?>
                                    <?php if (mb_strlen((string) $log['pesan']) > 150): ?>...<?php endif; ?>
                                    <details>
                                        <summary>Lihat pesan lengkap</summary>
                                        <pre><?= e((string) $log['pesan']) ?></pre>
                                        <?php if (!empty($log['respons'])): ?>
                                            <pre style="color:#64748b;border-top:1px solid #e5e9f2;padding-top:6px;margin-top:6px"><strong>Respons:</strong> <?= e((string) $log['respons']) ?></pre>
                                        <?php endif; ?>
                                    </details>
                                </div>
                            </td>
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
                        <a href="<?= url('admin/wa.php?' . http_build_query(array_merge($filterQuery, ['page' => $page - 1]))) ?>" class="btn btn--secondary btn--sm"><?= icon('arrow-left') ?> Sebelumnya</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= url('admin/wa.php?' . http_build_query(array_merge($filterQuery, ['page' => $page + 1]))) ?>" class="btn btn--secondary btn--sm">Berikutnya <?= icon('arrow-right') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/templates/admin_shell_end.php'; ?>
