<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$lockFile = BASE_PATH . '/storage/installed.lock';
$installed = is_file($lockFile);
$success = false;
$errors = [];

$form = [
    'nama' => 'Administrator',
    'nik' => '999999',
    'email' => 'admin@presensiku.test',
    'no_wa' => (string) config('admin_wa', ''),
];
$withDemo = true;

$storageDir = BASE_PATH . '/storage';
if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0775, true);
}
if (!is_dir($storageDir . '/logs')) {
    @mkdir($storageDir . '/logs', 0775, true);
}

$checks = [
    ['label' => 'PHP 8.1 atau lebih baru', 'ok' => version_compare(PHP_VERSION, '8.1.0', '>='), 'detail' => 'Versi ' . PHP_VERSION],
    ['label' => 'Ekstensi PDO MySQL', 'ok' => extension_loaded('pdo_mysql'), 'detail' => 'Wajib'],
    ['label' => 'Ekstensi cURL', 'ok' => extension_loaded('curl'), 'detail' => 'Untuk Fonnte'],
    ['label' => 'Ekstensi mbstring', 'ok' => extension_loaded('mbstring'), 'detail' => 'Wajib'],
    ['label' => 'Folder storage dapat ditulis', 'ok' => is_writable($storageDir), 'detail' => 'Log & lock'],
    ['label' => 'File .env tersedia', 'ok' => is_file(BASE_PATH . '/.env'), 'detail' => 'Konfigurasi'],
];
$requirementsOk = !in_array(false, array_column($checks, 'ok'), true);

if (is_post() && !$installed) {
    Csrf::verifyOrRedirect('install.php');

    $form['nama'] = input('nama');
    $form['nik'] = input('nik');
    $form['email'] = strtolower(input('email'));
    $form['no_wa'] = input('no_wa');
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $withDemo = isset($_POST['demo']);

    $validator = (new Validator(array_merge($form, [
        'password' => $password,
        'password_confirm' => $passwordConfirm,
    ])))
        ->required('nama', 'Nama admin')
        ->min('nama', 3, 'Nama admin')
        ->required('nik', 'NIK admin')
        ->numeric('nik', 'NIK admin')
        ->min('nik', 6, 'NIK admin')
        ->max('nik', 20, 'NIK admin')
        ->required('email', 'Email admin')
        ->email('email', 'email admin')
        ->required('no_wa', 'Nomor WhatsApp admin')
        ->required('password', 'Password admin')
        ->min('password', 8, 'Password admin')
        ->max('password', 72, 'Password admin')
        ->required('password_confirm', 'Konfirmasi password')
        ->same('password_confirm', 'password', 'Konfirmasi password');

    $errors = $validator->errors();

    if ($form['no_wa'] !== '' && !WhatsAppService::isValidNumber($form['no_wa'])) {
        $errors['no_wa'] = 'Nomor WhatsApp tidak valid. Gunakan format 08xxxxxxxxxx atau 628xxxxxxxxxx.';
    }

    if (!$requirementsOk && $errors === []) {
        $errors['umum'] = 'Beberapa persyaratan sistem belum terpenuhi. Perbaiki terlebih dahulu.';
    }

    if ($errors === []) {
        try {
            $host = (string) config('db.host');
            $port = (string) config('db.port');
            $dbName = (string) config('db.name');

            if (preg_match('/^[A-Za-z0-9_]+$/', $dbName) !== 1) {
                throw new RuntimeException('Nama database pada .env hanya boleh berisi huruf, angka, dan garis bawah.');
            }

            $pdo = new PDO(
                "mysql:host={$host};port={$port};charset=utf8mb4",
                (string) config('db.user'),
                (string) config('db.pass'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            $schemaFile = BASE_PATH . '/database/schema.sql';
            $sql = (string) file_get_contents($schemaFile);
            $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;

            foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [] as $statement) {
                $statement = trim($statement);
                if ($statement !== '') {
                    $pdo->exec($statement);
                }
            }

            $checkAdmin = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $checkAdmin->execute([$form['email']]);
            $adminId = $checkAdmin->fetchColumn();

            $normalizedWa = WhatsAppService::normalizeNumber($form['no_wa']);
            $hash = password_hash($password, PASSWORD_DEFAULT);

            if ($adminId) {
                $statement = $pdo->prepare(
                    'UPDATE users SET nama = ?, nik = ?, no_wa = ?, password = ?, role = "admin", aktif = 1, updated_at = NOW()
                     WHERE id = ?'
                );
                $statement->execute([$form['nama'], $form['nik'], $normalizedWa, $hash, (int) $adminId]);
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO users (nik, nama, jabatan, no_wa, email, password, role, aktif, created_at, updated_at)
                     VALUES (?, ?, "Administrator Sistem", ?, ?, ?, "admin", 1, NOW(), NOW())'
                );
                $statement->execute([
                    $form['nik'],
                    $form['nama'],
                    $normalizedWa,
                    $form['email'],
                    $hash,
                ]);
            }

            if ($withDemo) {
                include BASE_PATH . '/database/demo_seed.php';
            }

            $writeLock = @file_put_contents(
                $lockFile,
                'Terpasang pada ' . date('Y-m-d H:i:s') . PHP_EOL
            );

            if ($writeLock === false) {
                flash('warning', 'Aplikasi berhasil dipasang, tetapi file pengunci tidak dapat dibuat. Hapus install.php secara manual.');
            }

            $success = true;
            $installed = true;
        } catch (Throwable $exception) {
            $errors['umum'] = (bool) config('debug', false)
                ? 'Instalasi gagal: ' . $exception->getMessage()
                : 'Instalasi gagal. Periksa konfigurasi database pada file .env dan pastikan MySQL sudah berjalan.';
        }
    }
}

$pageTitle = 'Instalasi';
$bodyClass = 'setup-body';
require BASE_PATH . '/templates/head.php';
?>
<div class="setup-wrap">
    <div class="setup-head">
        <a class="brand" href="<?= url('install.php') ?>">
            <span class="brand__mark"><?= icon('calendar-check') ?></span>
            <span class="brand__text">
                <strong><?= e(config('app.name', 'PresensiKu')) ?></strong>
                <small>Sistem Absensi Karyawan</small>
            </span>
        </a>
        <h1>Instalasi Aplikasi</h1>
        <p>Siapkan database, akun admin, dan tabel yang dibutuhkan dalam satu langkah.</p>
    </div>

    <?= render_flashes() ?>

    <?php if ($success): ?>
        <div class="setup-card">
            <div class="setup-card__body">
                <div class="setup-success">
                    <div class="setup-success__icon"><?= icon('check-circle') ?></div>
                    <h2>Instalasi berhasil!</h2>
                    <p>Aplikasi sudah siap digunakan. Database <strong><?= e((string) config('db.name')) ?></strong> dan akun administrator telah dibuat.</p>

                    <div class="credential-box">
                        <div><span>Email admin</span><strong><?= e($form['email']) ?></strong></div>
                        <div><span>Nama admin</span><strong><?= e($form['nama']) ?></strong></div>
                        <div><span>Total karyawan demo</span><strong><?= $withDemo ? '5 karyawan' : 'Tidak ada' ?></strong></div>
                    </div>

                    <div class="alert alert--warning" style="text-align:left">
                        <span class="alert__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg></span>
                        <span class="alert__text">
                            Demi keamanan, hapus file <code>install.php</code> setelah ini. Untuk mengubah konfigurasi Fonnte,
                            edit file <code>.env</code> &mdash; token tidak boleh dibagikan atau diunggah ke repository publik.
                        </span>
                    </div>

                    <?php if ($withDemo): ?>
                        <p class="text-sm text-muted mt-2">Akun demo karyawan: <strong>budi@presensiku.test</strong> &middot; password <strong>Karyawan#123</strong></p>
                    <?php endif; ?>

                    <a href="<?= url('login.php') ?>" class="btn btn--primary btn--lg mt-3">
                        <?= icon('logout') ?> Masuk ke Aplikasi
                    </a>
                </div>
            </div>
        </div>
    <?php elseif ($installed): ?>
        <div class="setup-card">
            <div class="setup-card__body">
                <div class="setup-success">
                    <div class="setup-success__icon"><?= icon('shield') ?></div>
                    <h2>Aplikasi sudah terpasang</h2>
                    <p>File pengunci <code>storage/installed.lock</code> terdeteksi. Installer dinonaktifkan untuk mencegah perubahan data yang tidak sah.</p>
                    <p class="text-sm text-muted mt-2">Jika benar-benar perlu mengulang instalasi, hapus file <code>storage/installed.lock</code> lalu muat ulang halaman ini.</p>
                    <a href="<?= url('login.php') ?>" class="btn btn--primary btn--lg mt-3"><?= icon('arrow-right') ?> Buka Halaman Login</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="setup-card">
            <div class="setup-card__head">
                <span class="modal__icon modal__icon--primary"><?= icon('sparkles') ?></span>
                <div>
                    <h2>Persiapan instalasi</h2>
                    <p>Pastikan MySQL XAMPP sudah berjalan sebelum melanjutkan.</p>
                </div>
            </div>
            <div class="setup-card__body">
                <div class="setup-checks">
                    <?php foreach ($checks as $check): ?>
                        <div class="setup-check <?= $check['ok'] ? '' : 'setup-check--fail' ?>">
                            <?= icon($check['ok'] ? 'check-circle' : 'x') ?>
                            <span><?= e($check['label']) ?> <small class="text-muted">(<?= e($check['detail']) ?>)</small></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (isset($errors['umum'])): ?>
                    <div class="alert alert--error" role="alert">
                        <span class="alert__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/></svg></span>
                        <span class="alert__text"><?= e($errors['umum']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="alert alert--info">
                    <span class="alert__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg></span>
                    <span class="alert__text">
                        Database <strong><?= e((string) config('db.name')) ?></strong> pada host
                        <strong><?= e((string) config('db.host')) ?></strong> akan dibuat otomatis bila belum ada.
                        Konfigurasi dapat diubah di file <code>.env</code>.
                    </span>
                </div>

                <form method="post" action="<?= url('install.php') ?>" data-single-submit novalidate>
                    <?= Csrf::field() ?>

                    <div class="form-grid">
                        <div class="field">
                            <label for="nama">Nama administrator <span class="req">*</span></label>
                            <input type="text" class="input <?= isset($errors['nama']) ? 'is-invalid' : '' ?>" id="nama" name="nama" value="<?= e($form['nama']) ?>" required>
                            <?php if (isset($errors['nama'])): ?><span class="field__error"><?= e($errors['nama']) ?></span><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="nik">NIK administrator <span class="req">*</span></label>
                            <input type="text" class="input <?= isset($errors['nik']) ? 'is-invalid' : '' ?>" id="nik" name="nik" value="<?= e($form['nik']) ?>" inputmode="numeric" required>
                            <?php if (isset($errors['nik'])): ?><span class="field__error"><?= e($errors['nik']) ?></span><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="email">Email administrator <span class="req">*</span></label>
                            <input type="email" class="input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e($form['email']) ?>" required>
                            <?php if (isset($errors['email'])): ?><span class="field__error"><?= e($errors['email']) ?></span><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="no_wa">WhatsApp administrator <span class="req">*</span></label>
                            <input type="tel" class="input <?= isset($errors['no_wa']) ? 'is-invalid' : '' ?>" id="no_wa" name="no_wa" value="<?= e($form['no_wa']) ?>" placeholder="628xxxxxxxxxx" required>
                            <?php if (isset($errors['no_wa'])): ?><span class="field__error"><?= e($errors['no_wa']) ?></span><?php else: ?><span class="field__hint">Menerima notifikasi karyawan baru, absensi, dan perubahan data.</span><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="password">Password administrator <span class="req">*</span></label>
                            <div class="input-group">
                                <input type="password" class="input <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" minlength="8" required data-password-strength="#installStrength">
                                <button type="button" class="input-group__button" data-toggle-password="password" aria-label="Tampilkan password"><?= icon('eye') ?></button>
                            </div>
                            <div class="strength" id="installStrength">
                                <div class="strength__bars"><span></span><span></span><span></span><span></span></div>
                                <span class="strength__label">Kekuatan</span>
                            </div>
                            <?php if (isset($errors['password'])): ?><span class="field__error"><?= e($errors['password']) ?></span><?php else: ?><span class="field__hint">Minimal 8 karakter, sebaiknya kombinasi huruf dan angka.</span><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="password_confirm">Konfirmasi password <span class="req">*</span></label>
                            <div class="input-group">
                                <input type="password" class="input <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm" required>
                                <button type="button" class="input-group__button" data-toggle-password="password_confirm" aria-label="Tampilkan konfirmasi password"><?= icon('eye') ?></button>
                            </div>
                            <?php if (isset($errors['password_confirm'])): ?><span class="field__error"><?= e($errors['password_confirm']) ?></span><?php endif; ?>
                        </div>
                    </div>

                    <label class="setup-check" style="margin-top:16px;cursor:pointer">
                        <input type="checkbox" name="demo" value="1" <?= $withDemo ? 'checked' : '' ?>>
                        <span>Tambahkan 5 karyawan contoh beserta riwayat absensi 14 hari (cocok untuk mencoba fitur).</span>
                    </label>

                    <button type="submit" class="btn btn--primary btn--lg btn--block mt-3" <?= $requirementsOk ? '' : 'disabled' ?>>
                        <?= icon('shield') ?> Jalankan Instalasi
                    </button>
                </form>

                <div class="auth-note">
                    <?= icon('lock') ?>
                    <span>Data admin disimpan dengan password_hash(). Semua query menggunakan prepared statement. Token Fonnte hanya dibaca dari file .env dan tidak pernah ditampilkan di halaman.</span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <p class="text-center text-sm text-muted mt-3">
        <?= e(config('app.name', 'PresensiKu')) ?> &middot; PHP <?= e(PHP_VERSION) ?> &middot; MySQL &middot; Fonnte API
    </p>
</div>
<?php require BASE_PATH . '/templates/footer.php'; ?>
