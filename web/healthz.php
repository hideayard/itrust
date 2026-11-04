<?php
/**
 * TEMPORARY deployment diagnostic — DELETE THIS FILE once debugging is done.
 * Works even when the Yii app cannot boot. Never prints passwords.
 * Visit: https://<your-domain>/web/healthz.php
 */
header('Content-Type: text/plain; charset=utf-8');

echo "healthz\n";
echo "php_version = " . PHP_VERSION . "  (SAPI: " . PHP_SAPI . ")\n";
echo "php_ini     = " . (php_ini_loaded_file() ?: 'none') . "\n";
echo "display_errors = " . ini_get('display_errors') . ", error_reporting = " . error_reporting() . "\n";

$root = dirname(__DIR__);
echo "\n--- required files (relative to app root) ---\n";
$paths = [
    'config/db.php',
    'config/params.php',
    'config/web.php',
    'vendor/autoload.php',
    'vendor/composer/platform_check.php',
    'vendor/yiisoft/yii2/Yii.php',
    'web/index.php',
    'runtime',
    'runtime/logs',
];
foreach ($paths as $p) {
    $full = $root . '/' . $p;
    if (is_dir($full)) {
        $state = 'dir';
    } elseif (is_file($full)) {
        $state = 'file';
    } else {
        $state = 'MISSING';
    }
    $w = $state === 'MISSING' ? '' : (is_writable($full) ? ' writable' : ' NOT-writable');
    echo str_pad($p, 38) . $state . $w . "\n";
}

echo "\n--- vendor platform gate ---\n";
echo "this vendor requires PHP >= 8.4  ->  satisfied: "
    . (PHP_VERSION_ID >= 80400 ? 'YES' : 'NO  <-- this alone causes the 500') . "\n";

echo "\n--- database ---\n";
try {
    if (!is_file($root . '/config/db.php')) {
        throw new RuntimeException('config/db.php is MISSING on the server');
    }
    $cfg = require $root . '/config/db.php';
    $dsn = (string)($cfg['dsn'] ?? '');
    $pdo = new PDO($dsn, $cfg['username'] ?? '', $cfg['password'] ?? '', [
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "connect = OK, users.count = " . $pdo->query('select count(*) from users')->fetchColumn() . "\n";
} catch (Throwable $e) {
    echo "connect = FAILED: " . $e->getMessage() . "\n";
}

echo "\n--- outbound (Telegram) ---\n";
$ch = curl_init('https://api.telegram.org');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_CONNECTTIMEOUT => 5]);
curl_exec($ch);
echo curl_errno($ch) ? ('telegram = ERR ' . curl_errno($ch) . ' ' . curl_error($ch) . "\n") : "telegram = OK\n";

echo "\ndone\n";
