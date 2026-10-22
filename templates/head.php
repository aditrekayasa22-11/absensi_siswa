<?php

/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'Beranda';
$bodyClass = $bodyClass ?? '';
$user = $user ?? null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e(config('app.name', 'PresensiKu')) ?> — sistem absensi karyawan dengan notifikasi WhatsApp.">
    <meta name="theme-color" content="#4f46e5">
    <title><?= e($pageTitle) ?> &middot; <?= e(config('app.name', 'PresensiKu')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/logo.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
</head>
<body class="<?= e($bodyClass) ?>">
