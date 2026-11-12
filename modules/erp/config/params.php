<?php

/**
 * Parameter modul ERP. Hanya dipakai bila kunci belum ada di params aplikasi iTrust.
 */
$origins = array_filter(array_map('trim', explode(',', (string) (
    getenv('AUREUS_CORS_ORIGINS') ?: 'https://itrust-erp.pages.dev,http://localhost:4321'
))));

return [
    'corsOrigins' => array_values($origins),
    'tokenTtlDays' => max(1, (int) (getenv('AUREUS_TOKEN_TTL_DAYS') ?: 90)),
];