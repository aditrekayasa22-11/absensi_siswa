<?php

declare(strict_types=1);

return [
    'name' => Env::get('APP_NAME', 'PresensiKu'),
    'url' => rtrim((string) Env::get('APP_URL', ''), '/'),
    'timezone' => Env::get('APP_TIMEZONE', 'Asia/Jakarta'),
    'debug' => Env::bool('APP_DEBUG', false),
    'jam_masuk_batas' => Env::get('JAM_MASUK_BATAS', '07:30'),
    'admin_wa' => Env::get('ADMIN_WA', ''),
    'api_key' => Env::get('API_KEY', ''),

    'db' => [
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => Env::get('DB_PORT', '3306'),
        'name' => Env::get('DB_NAME', 'absensi_siswa'),
        'user' => Env::get('DB_USER', 'root'),
        'pass' => Env::get('DB_PASS', ''),
    ],

    'fonnte' => [
        'enabled' => Env::bool('FONNTE_ENABLED', false),
        'token' => Env::get('FONNTE_TOKEN', ''),
        'country_code' => Env::get(' ABnY2qqgPNjEnW33vvog', '62'),
        'endpoint' => 'https://api.fonnte.com/send',
    ],
];
