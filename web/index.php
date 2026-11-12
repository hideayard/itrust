<?php

$fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$httpHost = $_SERVER['HTTP_HOST'];

// if (preg_match("/$httpHost\/web/", $fullUrl)){
//     header("Location: " . str_replace("/web", "", $fullUrl));
//     exit;
// }
// Mode aplikasi mengikuti environment, sehingga file ini tidak perlu diubah saat deploy:
//   hosting : tambahkan ke .htaccess →  SetEnv YII_ENV prod
//   lokal   : biarkan kosong (default dev)
// Saat YII_ENV=prod, debugger & gii tidak dimuat dan log tidak lagi merekam
// $_POST/$_SERVER setiap request.
defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV') ?: 'dev');
defined('YII_DEBUG') or define('YII_DEBUG', filter_var(
    getenv('YII_DEBUG') ?: (YII_ENV !== 'prod'),
    FILTER_VALIDATE_BOOL
));

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
