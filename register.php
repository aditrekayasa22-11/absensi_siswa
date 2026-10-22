<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = Auth::user();
if ($user !== null) {
    redirect($user['role'] === 'admin' ? 'admin/index.php' : 'karyawan/index.php');
}

$errors = [];
$pageTitle = 'Daftar Karyawan';
$bodyClass = 'auth-body';

$data = [
    'nik' => '',
    'nama' => '',
    'jabatan' => '',
    'no_wa' => '',
    'email' => '',
];

if (is_post()) {
    Csrf::verifyOrRedirect('register.php');

    foreach (array_keys($data) as $key) {
        $data[$key] = input($key);
    }
    $data['email'] = strtolower($data['email']);
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    $validator = (new Validator(array_merge($data, [
        'password' => $password,
        'password_confirm' => $passwordConfirm,
    ])))
        ->required('nik', 'NIK')
        ->numeric('nik', 'NIK')
        ->min('nik', 6, 'NIK')
        ->max('nik', 20, 'NIK')
        ->required('nama', 'Nama lengkap')
        ->min('nama', 3, 'Nama lengkap')
        ->max('nama', 100, 'Nama lengkap')
        ->required('jabatan', 'Jabatan')
        ->min('jabatan', 2, 'Jabatan')
        ->max('jabatan', 100, 'Jabatan')
        ->required('no_wa', 'Nomor WhatsApp')
        ->required('email', 'Email')
        ->email('email', 'email')
        ->max('email', 120, 'Email')
        ->required('password', 'Password')
        ->min('password', 8, 'Password')
        ->max('password', 72, 'Password')
        ->required('password_confirm', 'Konfirmasi password')
        ->same('password_confirm', 'password', 'Konfirmasi password');

    $errors = $validator->errors();

    if ($data['no_wa'] !== '' && !WhatsAppService::isValidNumber($data['no_wa'])) {
        $errors['no_wa'] = 'Nomor WhatsApp tidak valid. Gunakan format 08xxxxxxxxxx atau 628xxxxxxxxxx.';
    }

    if ($password !== '' && !preg_match('/[A-Za-z]/', $password)) {
        $errors['password'] = 'Password harus mengandung minimal satu huruf.';
    }
    if ($password !== '' && !preg_match('/[0-9]/', $password) && !isset($errors['password'])) {
        $errors['password'] = 'Password harus mengandung minimal satu angka.';
    }

    if (!isset($errors['nik']) && $data['nik'] !== '') {
        $stmt = Database::connection()->prepare('SELECT id FROM users WHERE nik = ? LIMIT 1');
        $stmt->execute([$data['nik']]);
        if ($stmt->fetch()) {
            $errors['nik'] = 'NIK sudah terdaftar. Gunakan NIK lain.';
        }
    }

    if (!isset($errors['email']) && $data['email'] !== '') {
        $stmt = Database::connection()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email sudah terdaftar. Gunakan email lain atau masuk ke akun Anda.';
        }
    }

    if ($errors === []) {
        try {
            $pdo = Database::connection();
            $statement = $pdo->prepare(
                'INSERT INTO users (nik, nama, jabatan, no_wa, email, password, role, aktif, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, "karyawan", 1, NOW(), NOW())'
            );
            $statement->execute([
                $data['nik'],
                $data['nama'],
                $data['jabatan'],
                WhatsAppService::normalizeNumber($data['no_wa']),
                $data['email'],
                password_hash($password, PASSWORD_DEFAULT),
            ]);

            $newId = (int) $pdo->lastInsertId();
            $stmt = $pdo->prepare(
                'SELECT id, nik, nama, jabatan, no_wa, email, role FROM users WHERE id = ? LIMIT 1'
            );
            $stmt->execute([$newId]);
            $newUser = $stmt->fetch();

            if (is_array($newUser)) {
                $employeeResult = WhatsAppService::send(
                    (string) $newUser['no_wa'],
                    MessageTemplates::register($newUser),
                    'register',
                    $newId
                );
                $adminResult = WhatsAppService::notifyAdmin(
                    MessageTemplates::adminRegister($newUser),
                    'register'
                );

                flash('success', 'Registrasi berhasil! Silakan masuk menggunakan email dan password Anda.');

                if ($employeeResult['simulated'] || $adminResult['simulated']) {
                    flash('info', 'Notifikasi WhatsApp dicatat dalam mode simulasi. Aktifkan FONNTE_ENABLED dan isi FONNTE_TOKEN di .env untuk pengiriman nyata.');
                } elseif (!$employeeResult['ok'] || !$adminResult['ok']) {
                    flash('warning', 'Registrasi berhasil, tetapi salah satu notifikasi WhatsApp gagal dikirim. Periksa log WhatsApp pada dashboard admin.');
                } else {
                    flash('success', 'Notifikasi WhatsApp telah dikirim ke karyawan dan admin.');
                }
            }

            redirect('login.php');
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $errors['umum'] = 'NIK atau email sudah digunakan. Silakan periksa kembali data Anda.';
            } else {
                $errors['umum'] = (bool) config('debug', false)
                    ? 'Registrasi gagal: ' . $exception->getMessage()
                    : 'Registrasi gagal karena gangguan sistem. Silakan coba lagi.';
            }
        }
    }
}

require BASE_PATH . '/templates/head.php';
?>
<main class="auth-shell">
    <?php require BASE_PATH . '/templates/auth_visual.php'; ?>

    <section class="auth-panel">
        <div class="auth-panel__inner">
            <div class="auth-card auth-card--wide">
                <div class="auth-mobile-brand">
                    <a class="brand" href="<?= url('register.php') ?>">
                        <span class="brand__mark"><?= icon('calendar-check') ?></span>
                        <span class="brand__text">
                            <strong><?= e(config('app.name', 'PresensiKu')) ?></strong>
                            <small>Sistem Absensi Karyawan</small>
                        </span>
                    </a>
                </div>

                <div class="auth-mobile-ticker">
                    <?php require BASE_PATH . '/templates/auth_ticker.php'; ?>
                </div>

            <div class="auth-card__topbar">
                <a href="<?= url('login.php') ?>" class="auth-card__back"><?= icon('arrow-left') ?> Kembali ke login</a>
                <span class="auth-card__secure"><?= icon('shield') ?> Data terenkripsi</span>
            </div>

            <h2>Daftar sebagai karyawan</h2>
            <p class="auth-card__sub">Lengkapi data di bawah ini. Setelah terdaftar, Anda akan menerima notifikasi WhatsApp.</p>

            <?= render_flashes() ?>

            <?php if (isset($errors['umum'])): ?>
                <div class="alert alert--error" role="alert">
                    <span class="alert__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/></svg></span>
                    <span class="alert__text"><?= e($errors['umum']) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= url('register.php') ?>" class="auth-form" id="registerForm" data-single-submit novalidate>
                <?= Csrf::field() ?>

                <div class="form-grid">
                    <div class="field">
                        <label for="nik">NIK <span class="req">*</span></label>
                        <input type="text" id="nik" name="nik" class="input <?= isset($errors['nik']) ? 'is-invalid' : '' ?>"
                               value="<?= e($data['nik']) ?>" placeholder="Contoh: 1234567890" inputmode="numeric"
                               autocomplete="off" maxlength="20" required>
                        <?php if (isset($errors['nik'])): ?><span class="field__error"><?= e($errors['nik']) ?></span><?php else: ?><span class="field__hint">Hanya angka, 6&ndash;20 digit.</span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="nama">Nama lengkap <span class="req">*</span></label>
                        <input type="text" id="nama" name="nama" class="input <?= isset($errors['nama']) ? 'is-invalid' : '' ?>"
                               value="<?= e($data['nama']) ?>" placeholder="Nama sesuai identitas" autocomplete="name" maxlength="100" required>
                        <?php if (isset($errors['nama'])): ?><span class="field__error"><?= e($errors['nama']) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="jabatan">Jabatan <span class="req">*</span></label>
                        <input type="text" id="jabatan" name="jabatan" class="input <?= isset($errors['jabatan']) ? 'is-invalid' : '' ?>"
                               value="<?= e($data['jabatan']) ?>" placeholder="Contoh: Staff IT" maxlength="100" required>
                        <?php if (isset($errors['jabatan'])): ?><span class="field__error"><?= e($errors['jabatan']) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="no_wa">Nomor WhatsApp <span class="req">*</span></label>
                        <input type="tel" id="no_wa" name="no_wa" class="input <?= isset($errors['no_wa']) ? 'is-invalid' : '' ?>"
                               value="<?= e($data['no_wa']) ?>" placeholder="08xxxxxxxxxx" inputmode="tel" autocomplete="tel" required>
                        <?php if (isset($errors['no_wa'])): ?><span class="field__error"><?= e($errors['no_wa']) ?></span><?php else: ?><span class="field__hint">Notifikasi absensi akan dikirim ke nomor ini.</span><?php endif; ?>
                    </div>

                    <div class="field field--full">
                        <label for="email">Email <span class="req">*</span></label>
                        <input type="email" id="email" name="email" class="input <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                               value="<?= e($data['email']) ?>" placeholder="nama@perusahaan.com" autocomplete="email" maxlength="120" required>
                        <?php if (isset($errors['email'])): ?><span class="field__error"><?= e($errors['email']) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="password">Password <span class="req">*</span></label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="input <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                   placeholder="Minimal 8 karakter" autocomplete="new-password" minlength="8" required
                                   data-password-strength="#passwordStrength">
                            <button type="button" class="input-group__button" data-toggle-password="password" aria-label="Tampilkan password">
                                <?= icon('eye') ?>
                            </button>
                        </div>
                        <div class="strength" id="passwordStrength">
                            <div class="strength__bars"><span></span><span></span><span></span><span></span></div>
                            <span class="strength__label">Kekuatan</span>
                        </div>
                        <?php if (isset($errors['password'])): ?><span class="field__error"><?= e($errors['password']) ?></span><?php else: ?><span class="field__hint">Gunakan huruf dan angka, minimal 8 karakter.</span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="password_confirm">Konfirmasi password <span class="req">*</span></label>
                        <div class="input-group">
                            <input type="password" id="password_confirm" name="password_confirm" class="input <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                                   placeholder="Ulangi password" autocomplete="new-password" required>
                            <button type="button" class="input-group__button" data-toggle-password="password_confirm" aria-label="Tampilkan konfirmasi password">
                                <?= icon('eye') ?>
                            </button>
                        </div>
                        <?php if (isset($errors['password_confirm'])): ?><span class="field__error"><?= e($errors['password_confirm']) ?></span><?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn--primary btn--lg btn--block mt-3 auth-submit">
                    <span>Daftar &amp; Kirim Notifikasi</span> <?= icon('arrow-right') ?>
                </button>
            </form>

            <p class="auth-switch">Sudah punya akun? <a href="<?= url('login.php') ?>">Masuk di sini</a></p>

            <div class="auth-note">
                <?= icon('whatsapp') ?>
                <span>Setelah registrasi berhasil, sistem mengirim WhatsApp berisi data pendaftaran kepada Anda dan notifikasi karyawan baru kepada admin.</span>
            </div>
            </div>
        </div>
    </section>
</main>
<?php require BASE_PATH . '/templates/footer.php'; ?>
