<?php

/**
 * Parameter modul ERP. Hanya dipakai bila kunci belum ada di params aplikasi iTrust.
 *
 * AUREUS_CORS_ORIGINS menerima BEBERAPA URL sekaligus. Pemisah boleh koma,
 * titik-koma, spasi, atau baris baru — semuanya dinormalisasi:
 *
 *   AUREUS_CORS_ORIGINS=https://itrust-erp.pages.dev,https://erp.itrust-tech.id
 *   AUREUS_CORS_ORIGINS="https://a.com https://b.com http://localhost:4321"
 *
 * Nilai `*` berarti semua origin diizinkan (dipakai hanya bila benar-benar perlu).
 */
$raw = (string) (getenv('AUREUS_CORS_ORIGINS') ?: '');
if ($raw === '') {
    $raw = 'https://itrust-erp.pages.dev,https://erp.rimondaglobaldigital.com,http://localhost:4321';
}

$origins = [];
foreach (preg_split('/[\s,;]+/', $raw) ?: [] as $origin) {
    $origin = trim($origin);
    if ($origin === '') {
        continue;
    }
    $origin = rtrim($origin, '/');
    $origins[strtolower($origin)] = $origin; // dedupe, pertahankan bentuk asli
}

return [
    'corsOrigins' => array_values($origins),
    'tokenTtlDays' => max(1, (int) (getenv('AUREUS_TOKEN_TTL_DAYS') ?: 90)),
];