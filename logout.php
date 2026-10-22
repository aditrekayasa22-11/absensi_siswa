<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (!is_post()) {
    redirect(Auth::check() ? (Auth::isAdmin() ? 'admin/index.php' : 'karyawan/index.php') : 'login.php');
}

Csrf::verifyOrRedirect('index.php');

Auth::logout();
flash('success', 'Anda telah keluar dari sistem. Sampai jumpa kembali!');
redirect('login.php');
