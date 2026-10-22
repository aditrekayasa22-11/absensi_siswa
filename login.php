<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = Auth::user();
if ($user !== null) {
    redirect($user['role'] === 'admin' ? 'admin/index.php' : 'karyawan/index.php');
}

$errors = [];
$email = '';
$pageTitle = 'Masuk';
$bodyClass = 'auth-body';

if (is_post()) {
    Csrf::verifyOrRedirect('login.php');

    $email = strtolower(input('email'));
    $password = (string) ($_POST['password'] ?? '');
    $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);

    if ($lockedUntil > time()) {
        $errors['auth'] = 'Terlalu banyak percobaan. Silakan coba lagi dalam ' . ($lockedUntil - time()) . ' detik.';
    } else {
        $validator = (new Validator(['email' => $email, 'password' => $password]))
            ->required('email', 'Email')
            ->email('email', 'email')
            ->required('password', 'Password');

        $errors = $validator->errors();

        if (!$validator->fails()) {
            $authenticated = Auth::attempt($email, $password);

            if ($authenticated !== null) {
                unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
                flash('success', 'Selamat datang, ' . $authenticated['nama'] . '.');
                redirect($authenticated['role'] === 'admin' ? 'admin/index.php' : 'karyawan/index.php');
            }

            $attempts = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
            $_SESSION['login_attempts'] = $attempts;

            if ($attempts >= 5) {
                $_SESSION['login_attempts'] = 0;
                $_SESSION['login_locked_until'] = time() + 30;
                $errors['auth'] = 'Terlalu banyak percobaan. Akun dikunci sementara selama 30 detik.';
            } else {
                $errors['auth'] = 'Email atau password salah. Sisa percobaan: ' . (5 - $attempts) . '.';
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
            <div class="auth-card">
                <div class="auth-mobile-brand">
                    <a class="brand" href="<?= url('login.php') ?>">
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
                    <span class="auth-card__back"><?= icon('user') ?> Portal Karyawan &amp; Admin</span>
                    <span class="auth-card__secure"><?= icon('shield') ?> Akses aman</span>
                </div>

                <h2>Selamat datang kembali</h2>
                <p class="auth-card__sub">Masuk untuk mencatat absensi atau mengelola data karyawan.</p>

                <?= render_flashes() ?>

                <?php if (isset($errors['auth'])): ?>
                    <div class="alert alert--error" role="alert">
                        <span class="alert__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/></svg></span>
                        <span class="alert__text"><?= e($errors['auth']) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= url('login.php') ?>" class="auth-form" data-single-submit novalidate>
                    <?= Csrf::field() ?>

                    <div class="field">
                        <label for="email">Email <span class="req">*</span></label>
                        <div class="input-group input-group--lead">
                            <span class="input-group__lead"><?= icon('mail') ?></span>
                            <input type="email" id="email" name="email" class="input <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($email) ?>" placeholder="nama@perusahaan.com" autocomplete="username" required autofocus>
                        </div>
                        <?php if (isset($errors['email'])): ?><span class="field__error"><?= e($errors['email']) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="password">Password <span class="req">*</span></label>
                        <div class="input-group input-group--lead">
                            <span class="input-group__lead"><?= icon('lock') ?></span>
                            <input type="password" id="password" name="password" class="input <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                   placeholder="Masukkan password" autocomplete="current-password" required>
                            <button type="button" class="input-group__button" data-toggle-password="password" aria-label="Tampilkan password">
                                <?= icon('eye') ?>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?><span class="field__error"><?= e($errors['password']) ?></span><?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn--primary btn--lg btn--block auth-submit">
                        <span>Masuk ke Dashboard</span> <?= icon('arrow-right') ?>
                    </button>
                </form>

                <div class="auth-trust">
                    <span><?= icon('shield') ?> Terenkripsi</span>
                    <span><?= icon('whatsapp') ?> Notifikasi WA</span>
                    <span><?= icon('clock') ?> Real-time</span>
                </div>

                <p class="auth-switch">Belum punya akun? <a href="<?= url('register.php') ?>">Daftar sebagai karyawan</a></p>

                <div class="auth-note">
                    <?= icon('shield') ?>
                    <span>Password disimpan dalam bentuk hash dan koneksi database menggunakan prepared statement. Jangan bagikan kredensial Anda kepada siapa pun.</span>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require BASE_PATH . '/templates/footer.php'; ?>
