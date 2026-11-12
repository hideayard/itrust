<?php

$fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$httpHost = $_SERVER['HTTP_HOST'];

// if (preg_match("/$httpHost\/web/", $fullUrl)){
//     header("Location: " . str_replace("/web", "", $fullUrl));
//     exit;
// }

/*
 * Muat berkas .env di ROOT PROYEK (di luar document root web/), bila ada.
 * Nilai yang sudah disetel web server (SetEnv / vhost) tidak akan ditimpa.
 * Format: KEY=value, baris berawalan '#' diabaikan, tanda kutip di ujung dihapus.
 */
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (strlen($value) > 1 && ($value[0] === '"' || $value[0] === "'") && str_ends_with($value, $value[0])) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

$envValue = static function (string $key): ?string {
    $value = getenv($key);
    if ($value === false && isset($_SERVER[$key])) {
        $value = $_SERVER[$key];
    }
    return ($value === false || $value === '') ? null : (string) $value;
};

// Mode aplikasi: `YII_ENV=prod` mematikan debugger & gii serta log $_POST/$_SERVER.
defined('YII_ENV') or define('YII_ENV', $envValue('YII_ENV') ?? 'dev');
defined('YII_DEBUG') or define('YII_DEBUG', filter_var(
    $envValue('YII_DEBUG') ?? (YII_ENV !== 'prod'),
    FILTER_VALIDATE_BOOL
));

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
