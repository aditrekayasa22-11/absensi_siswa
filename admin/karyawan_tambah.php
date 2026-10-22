<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$user = require_admin();
$pdo = Database::connection();

$errors = [];
$form = [
    'nik' => '',
    'nama' => '',
    'jabatan' => '',
    'no_wa' => '',
    'email' => '',
];
$sendWa = true;

if (is_post()) {
    Csrf::verifyOrRedirect('admin/karyawan_tambah.php');

    foreach (array_keys($form) as $key) {
        $form[$key] = input($key);
    }
    $form['email'] = strtolower($form['email']);
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $sendWa = isset($_POST['send_wa']);

    $validator = (new Validator(array_merge($form, [
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

    if ($form['no_wa'] !== '' && !WhatsAppService::isValidNumber($form['no_wa'])) {
        $errors['no_wa'] = 'Nomor WhatsApp tidak valid. Gunakan format 08xxxxxxxxxx atau 628xxxxxxxxxx.';
    }

    if ($password !== '' && !preg_match('/[A-Za-z]/', $password)) {
        $errors['password'] = 'Password harus mengandung minimal satu huruf.';
    }
    if ($password !== '' && !preg_match('/[0-9]/', $password) && !isset($errors['password'])) {
        $errors['password'] = 'Password harus mengandung minimal satu angka.';
    }

    if (!isset($errors['nik']) && $form['nik'] !== '') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE nik = ? LIMIT 1');
        $stmt->execute([$form['nik']]);
        if ($stmt->fetch()) {
            $errors['nik'] = 'NIK sudah digunakan oleh karyawan lain.';
        }
    }

    if (!isset($errors['email']) && $form['email'] !== '') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$form['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email sudah digunakan oleh karyawan lain.';
        }
    }

    if ($errors === []) {
        try {
            $normalizedWa = WhatsAppService::normalizeNumber($form['no_wa']);
            $statement = $pdo->prepare(
                'INSERT INTO users (nik, nama, jabatan, no_wa, email, password, role, aktif, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, "karyawan", 1, NOW(), NOW())'
            );
            $statement->execute([
                $form['nik'],
                $form['nama'],
                $form['jabatan'],
                $normalizedWa,
                $form['email'],
                password_hash($password, PASSWORD_DEFAULT),
            ]);

            $newId = (int) $pdo->lastInsertId();
            $stmt = $pdo->prepare('SELECT id, nik, nama, jabatan, no_wa, email FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$newId]);
            $employee = $stmt->fetch();

            flash('success', 'Karyawan ' . $form['nama'] . ' berhasil ditambahkan.');

            if ($sendWa && is_array($employee)) {
                $waResult = WhatsAppService::send(
                    (string) $employee['no_wa'],
                    MessageTemplates::register($employee),
                    'admin_create',
                    $newId
                );

                if ($waResult['simulated']) {
                    flash('info', 'Notifikasi WhatsApp dicatat dalam mode simulasi. Aktifkan Fonnte untuk pengiriman nyata.');
                } elseif ($waResult['ok']) {
                    flash('success', 'Notifikasi pendaftaran berhasil dikirim ke WhatsApp karyawan.');
                } else {
                    flash('warning', 'Data tersimpan, tetapi notifikasi WhatsApp gagal dikirim. Periksa log WhatsApp.');
                }
            }

            redirect('admin/karyawan.php');
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $errors['umum'] = 'NIK atau email sudah digunakan. Periksa kembali data karyawan.';
            } else {
                $errors['umum'] = (bool) config('debug', false)
                    ? 'Gagal menyimpan: ' . $exception->getMessage()
                    : 'Gagal menyimpan data karena gangguan sistem.';
            }
        }
    }
}

$pageTitle = 'Tambah Karyawan';
$pageSubtitle = 'Tambahkan akun karyawan baru ke dalam sistem absensi.';
$activeMenu = 'karyawan';
$formAction = url('admin/karyawan_tambah.php');
$isEdit = false;

require BASE_PATH . '/templates/admin_shell_start.php';
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">Manajemen Karyawan</p>
        <h1>Tambah Karyawan</h1>
        <p class="page-heading__sub">Lengkapi data karyawan. Password akan di-hash dan notifikasi WhatsApp dapat dikirim otomatis.</p>
    </div>
    <div class="page-heading__actions">
        <a href="<?= url('admin/karyawan.php') ?>" class="btn btn--secondary"><?= icon('arrow-left') ?> Kembali</a>
    </div>
</div>

<?php require BASE_PATH . '/admin/karyawan_form.php'; ?>

<?php require BASE_PATH . '/templates/admin_shell_end.php'; ?>
