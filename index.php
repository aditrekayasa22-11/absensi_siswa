<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = Auth::user();
if ($user !== null) {
    redirect($user['role'] === 'admin' ? 'admin/index.php' : 'karyawan/index.php');
}

redirect('login.php');
