<?php

/**
 * Koneksi database ERP (terpisah dari database aplikasi iTrust).
 * Ubah lewat .env: AUREUS_DB_HOST / AUREUS_DB / AUREUS_DB_USER / AUREUS_DB_PASSWORD.
 */
return [
    'class' => \yii\db\Connection::class,
    'dsn' => 'mysql:host=' . (getenv('AUREUS_DB_HOST') ?: 'localhost')
        . ';dbname=' . (getenv('AUREUS_DB') ?: 'itrust_erp'),
    'username' => getenv('AUREUS_DB_USER') ?: 'root',
    'password' => getenv('AUREUS_DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
    'attributes' => [
        \PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'",
        \PDO::ATTR_TIMEOUT => 5,
    ],
];