<?php

/**
 * Koneksi database ERP (terpisah dari database aplikasi iTrust).
 *
 * Nilai dibaca dari environment (getenv / $_SERVER), sehingga di shared hosting
 * bisa disetel lewat `.htaccess` (SetEnv) atau file .env loader.
 * Default: itrust_erp @ localhost.
 */
$env = static function (string $key, ?string $default = null): ?string {
    $value = getenv($key);
    if ($value === false && isset($_SERVER[$key])) {
        $value = $_SERVER[$key];
    }
    return ($value === false || $value === '') ? $default : (string) $value;
};

return [
    'class' => \yii\db\Connection::class,
    'dsn' => 'mysql:host=' . $env('AUREUS_DB_HOST', 'localhost')
        . ';dbname=' . $env('AUREUS_DB', 'itrust_erp'),
    'username' => $env('AUREUS_DB_USER', 'root'),
    'password' => $env('AUREUS_DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'attributes' => [
        \PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'",
        \PDO::ATTR_TIMEOUT => 5,
    ],
];