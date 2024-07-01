<?php

$fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$httpHost = $_SERVER['HTTP_HOST'];

// if (preg_match("/$httpHost\/web/", $fullUrl)){
//     header("Location: " . str_replace("/web", "", $fullUrl));
//     exit;
// }
//tes
// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

// require __DIR__ . '/../vendor/autoload.php';
// require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
