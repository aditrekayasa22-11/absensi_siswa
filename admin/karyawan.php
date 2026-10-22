<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$user = require_admin();
$pdo = Database::connection();

$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$where = 'role = "karyawan"';
$params = [];

if ($q !== '') {
    $where .= ' AND (nama LIKE ? OR nik LIKE ? OR email LIKE ? OR jabatan LIKE ?)';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like, $like];
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE ' . $where);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    'SELECT id, nik, nama, jabatan, no_wa, email, created_at
     FROM users
     WHERE ' . $where . '
     ORDER BY nama ASC
     LIMIT ? OFFSET ?'
);
foreach ($params as $index => $value) {
    $stmt->bindValue($index + 1, $value, PDO::PARAM_STR);
}
$stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$employees = $stmt->fetchAll();

$totalJabatan = (int) $pdo->query(
    'SELECT COUNT(DISTINCT jabatan) FROM users WHERE role = "karyawan"'
)->fetchColumn();
$baruBulanIni = (int) $pdo->query(
    'SELECT COUNT(*) FROM users WHERE role = "karyawan" AND created_at >= DATE_FORMAT(CURDATE(), "%Y-%m-01")'
)->fetchColumn();

$pageTitle = 'Data Karyawan';
$pageSubtitle = 'Kelola data karyawan, perbarui informasi, dan kirim notifikasi WhatsApp.';
$activeMenu = 'karyawan';

require BASE_PATH . '/templates/admin_shell_start.php';
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">Manajemen SDM</p>
        <h1>Data Karyawan</h1>
        <p class="page-heading__sub">Tambah, ubah, dan hapus data karyawan. Perubahan data dan penghapusan otomatis mengirim notifikasi WhatsApp.</p>
    </div>
    <div class="page-heading__actions">
        <a href="<?= url('admin/karyawan_tambah.php') ?>" class="btn btn--primary">
            <?= icon('plus') ?> Tambah Karyawan
        </a>
    </div>
</div>

<div class="mini-stats mb-2">
    <div class="mini-stat">
        <div class="mini-stat__label">Total Karyawan</div>
        <div class="mini-stat__value"><?= number_format($total, 0, ',', '.') ?> <span>orang</span></div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat__label">Jabatan</div>
        <div class="mini-stat__value"><?= $totalJabatan ?> <span>jenis</span></div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat__label">Karyawan Baru</div>
        <div class="mini-stat__value"><?= $baruBulanIni ?> <span>bulan ini</span></div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat__label">Status WhatsApp</div>
        <div class="mini-stat__value" style="font-size:14px;padding-top:6px">
            <span class="badge badge--<?= wa_mode_active() ? 'success' : 'info' ?>"><?= wa_mode_active() ? 'Aktif' : 'Simulasi' ?></span>
        </div>
    </div>
</div>

<section class="card">
    <div class="table-toolbar">
        <div class="table-toolbar__left">
            <span class="table-toolbar__title">Daftar karyawan</span>
            <span class="table-toolbar__hint"><?= number_format($total, 0, ',', '.') ?> data ditemukan</span>
        </div>
        <form method="get" action="<?= url('admin/karyawan.php') ?>" class="table-toolbar__right">
            <div class="search-box">
                <?= icon('search') ?>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama, NIK, email, jabatan...">
            </div>
            <button type="submit" class="btn btn--secondary btn--sm"><?= icon('search') ?> Cari</button>
            <?php if ($q !== ''): ?>
                <a href="<?= url('admin/karyawan.php') ?>" class="btn btn--ghost btn--sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($employees === []): ?>
        <div class="empty-state">
            <div class="empty-state__icon"><?= icon('users') ?></div>
            <h3><?= $q !== '' ? 'Karyawan tidak ditemukan' : 'Belum ada data karyawan' ?></h3>
            <p><?= $q !== '' ? 'Coba gunakan kata kunci lain atau reset pencarian.' : 'Tambahkan karyawan pertama atau minta karyawan melakukan registrasi.' ?></p>
            <a href="<?= url('admin/karyawan_tambah.php') ?>" class="btn btn--primary btn--sm mt-2"><?= icon('plus') ?> Tambah Karyawan</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>NIK</th>
                        <th>Jabatan</th>
                        <th>WhatsApp</th>
                        <th>Terdaftar</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td>
                                <div class="person">
                                    <span class="avatar avatar--sm <?= avatar_tone($employee['nik']) ?>"><?= e(initials((string) $employee['nama'])) ?></span>
                                    <span class="person__text">
                                        <span class="person__name"><?= e($employee['nama']) ?></span>
                                        <span class="person__meta"><?= e($employee['email']) ?></span>
                                    </span>
                                </div>
                            </td>
                            <td class="num"><?= e($employee['nik']) ?></td>
                            <td><?= e($employee['jabatan']) ?></td>
                            <td>
                                <a href="https://wa.me/<?= e($employee['no_wa']) ?>" target="_blank" rel="noopener" class="text-sm">
                                    <?= e(phone_display((string) $employee['no_wa'])) ?>
                                </a>
                            </td>
                            <td class="muted"><?= e(tanggal_id(date('Y-m-d', strtotime((string) $employee['created_at'])))) ?></td>
                            <td>
                                <div class="row-actions" style="justify-content:flex-end">
                                    <a href="<?= url('admin/karyawan_edit.php?id=' . (int) $employee['id']) ?>" class="action-btn" title="Edit data">
                                        <?= icon('edit') ?>
                                    </a>
                                    <button type="button" class="action-btn action-btn--danger"
                                            data-open-modal="deleteModal"
                                            data-delete-user
                                            data-user-id="<?= (int) $employee['id'] ?>"
                                            data-user-name="<?= e($employee['nama']) ?>"
                                            data-user-nik="<?= e($employee['nik']) ?>"
                                            data-action="<?= url('admin/karyawan_hapus.php') ?>"
                                            title="Hapus data">
                                        <?= icon('trash') ?>
                                    </button>
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
                        <a href="<?= url('admin/karyawan.php?' . http_build_query(['q' => $q, 'page' => $page - 1])) ?>" class="btn btn--secondary btn--sm"><?= icon('arrow-left') ?> Sebelumnya</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= url('admin/karyawan.php?' . http_build_query(['q' => $q, 'page' => $page + 1])) ?>" class="btn btn--secondary btn--sm">Berikutnya <?= icon('arrow-right') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<div class="modal" id="deleteModal" hidden>
    <div class="modal__backdrop"></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
        <form method="post" id="deleteForm" action="<?= url('admin/karyawan_hapus.php') ?>" data-single-submit>
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="">
            <div class="modal__head">
                <span class="modal__icon"><?= icon('trash') ?></span>
                <div class="modal__titles">
                    <h3 id="deleteModalTitle">Hapus karyawan?</h3>
                    <p>Tindakan ini tidak dapat dibatalkan. Data absensi terkait juga akan dihapus.</p>
                </div>
                <button type="button" class="modal__close" data-close-modal aria-label="Tutup"><?= icon('close') ?></button>
            </div>
            <div class="modal__body">
                <div class="modal__highlight">
                    <span class="avatar <?= avatar_tone('delete') ?>"><?= icon('user') ?></span>
                    <span>
                        <strong data-delete-name>-</strong>
                        <span>NIK <span data-delete-nik>-</span></span>
                    </span>
                </div>
                <div class="alert alert--warning" style="margin-bottom:0">
                    <span class="alert__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg></span>
                    <span class="alert__text">Sistem akan mengambil data karyawan, mengirim notifikasi WhatsApp penghapusan, lalu menghapus data dari database.</span>
                </div>
            </div>
            <div class="modal__foot">
                <button type="button" class="btn btn--secondary" data-close-modal>Batal</button>
                <button type="submit" class="btn btn--danger"><?= icon('trash') ?> Ya, Hapus &amp; Kirim WA</button>
            </div>
        </form>
    </div>
</div>

<?php require BASE_PATH . '/templates/admin_shell_end.php'; ?>
