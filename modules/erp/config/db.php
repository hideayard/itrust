<?php

/**
 * Koneksi database ERP (terpisah dari database aplikasi iTrust).
 *
 * Kredensial TIDAK ditulis di berkas ini. Urutan pembacaan:
 *   1. config/db.local.php  (berkas lokal/server, di-gitignore) — opsional
 *   2. environment: AUREUS_DB_HOST / AUREUS_DB / AUREUS_DB_USER / AUREUS_DB_PASSWORD
 *   3. default: itrust_erp @ localhost
 */
$local = __DIR__ . '/db.local.php';
if (is_file($local)) {
    return require $local;
}

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