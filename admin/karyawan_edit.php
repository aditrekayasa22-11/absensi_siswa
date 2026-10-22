<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$user = require_admin();
$pdo = Database::connection();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    flash('error', 'ID karyawan tidak valid.');
    redirect('admin/karyawan.php');
}

$stmt = $pdo->prepare(
    'SELECT id, nik, nama, jabatan, no_wa, email, created_at
     FROM users WHERE id = ? AND role = "karyawan" LIMIT 1'
);
$stmt->execute([$id]);
$current = $stmt->fetch();

if (!$current) {
    flash('error', 'Data karyawan tidak ditemukan.');
    redirect('admin/karyawan.php');
}

$errors = [];
$form = [
    'nik' => (string) $current['nik'],
    'nama' => (string) $current['nama'],
    'jabatan' => (string) $current['jabatan'],
    'no_wa' => (string) $current['no_wa'],
    'email' => (string) $current['email'],
];

if (is_post()) {
    Csrf::verifyOrRedirect('admin/karyawan_edit.php?id=' . $id);

    foreach (array_keys($form) as $key) {
        $form[$key] = input($key);
    }
    $form['email'] = strtolower($form['email']);

    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    $validator = (new Validator($form))
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
        ->max('email', 120, 'Email');

    $errors = $validator->errors();

    if ($form['no_wa'] !== '' && !WhatsAppService::isValidNumber($form['no_wa'])) {
        $errors['no_wa'] = 'Nomor WhatsApp tidak valid. Gunakan format 08xxxxxxxxxx atau 628xxxxxxxxxx.';
    }

    if ($password !== '') {
        if (mb_strlen($password) < 8) {
            $errors['password'] = 'Password minimal 8 karakter.';
        } elseif (mb_strlen($password) > 72) {
            $errors['password'] = 'Password maksimal 72 karakter.';
        } elseif (!preg_match('/[A-Za-z]/', $password)) {
            $errors['password'] = 'Password harus mengandung minimal satu huruf.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password harus mengandung minimal satu angka.';
        }

        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Konfirmasi password tidak sama.';
        }
    }

    if (!isset($errors['nik']) && $form['nik'] !== '') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE nik = ? AND id <> ? LIMIT 1');
        $stmt->execute([$form['nik'], $id]);
        if ($stmt->fetch()) {
            $errors['nik'] = 'NIK sudah digunakan oleh karyawan lain.';
        }
    }

    if (!isset($errors['email']) && $form['email'] !== '') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $stmt->execute([$form['email'], $id]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email sudah digunakan oleh karyawan lain.';
        }
    }

    if ($errors === []) {
        try {
            $normalizedWa = WhatsAppService::normalizeNumber($form['no_wa']);

            if ($password !== '') {
                $statement = $pdo->prepare(
                    'UPDATE users SET nik = ?, nama = ?, jabatan = ?, no_wa = ?, email = ?, password = ?, updated_at = NOW()
                     WHERE id = ? AND role = "karyawan"'
                );
                $statement->execute([
                    $form['nik'],
                    $form['nama'],
                    $form['jabatan'],
                    $normalizedWa,
                    $form['email'],
                    password_hash($password, PASSWORD_DEFAULT),
                    $id,
                ]);
            } else {
                $statement = $pdo->prepare(
                    'UPDATE users SET nik = ?, nama = ?, jabatan = ?, no_wa = ?, email = ?, updated_at = NOW()
                     WHERE id = ? AND role = "karyawan"'
                );
                $statement->execute([
                    $form['nik'],
                    $form['nama'],
                    $form['jabatan'],
                    $normalizedWa,
                    $form['email'],
                    $id,
                ]);
            }

            $stmt = $pdo->prepare(
                'SELECT id, nik, nama, jabatan, no_wa, email FROM users WHERE id = ? AND role = "karyawan" LIMIT 1'
            );
            $stmt->execute([$id]);
            $updated = $stmt->fetch();

            flash('success', 'Data karyawan berhasil diperbarui.');

            if (is_array($updated)) {
                $waResult = WhatsAppService::send(
                    (string) $updated['no_wa'],
                    MessageTemplates::updated($updated),
                    'update',
                    $id
                );

                if ($waResult['simulated']) {
                    flash('info', 'Notifikasi WhatsApp perubahan data dicatat dalam mode simulasi.');
                } elseif ($waResult['ok']) {
                    flash('success', 'Notifikasi perubahan data berhasil dikirim ke WhatsApp karyawan.');
                } else {
                    flash('warning', 'Data diperbarui, tetapi notifikasi WhatsApp gagal dikirim. Periksa log WhatsApp.');
                }
            }

            redirect('admin/karyawan.php');
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $errors['umum'] = 'NIK atau email sudah digunakan. Periksa kembali data karyawan.';
            } else {
                $errors['umum'] = (bool) config('debug', false)
                    ? 'Gagal memperbarui: ' . $exception->getMessage()
                    : 'Gagal memperbarui data karena gangguan sistem.';
            }
        }
    }
}

$pageTitle = 'Edit Karyawan';
$pageSubtitle = 'Perbarui data karyawan dan kirim notifikasi perubahan via WhatsApp.';
$activeMenu = 'karyawan';
$formAction = url('admin/karyawan_edit.php?id=' . $id);
$isEdit = true;

require BASE_PATH . '/templates/admin_shell_start.php';
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">Manajemen Karyawan</p>
        <h1>Edit Data Karyawan</h1>
        <p class="page-heading__sub">Perubahan akan disimpan ke database, lalu notifikasi WhatsApp berisi data terbaru dikirim ke karyawan.</p>
    </div>
    <div class="page-heading__actions">
        <a href="<?= url('admin/karyawan.php') ?>" class="btn btn--secondary"><?= icon('arrow-left') ?> Kembali</a>
    </div>
</div>

<?php require BASE_PATH . '/admin/karyawan_form.php'; ?>

<?php require BASE_PATH . '/templates/admin_shell_end.php'; ?>
