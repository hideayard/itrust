<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'ro',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'assetManager' => [
            'bundles' => [
                'yii\bootstrap\BootstrapAsset' => [
                    'css' => [],
                    'js' => [],
                ],
            ],
        ],
        'request' => [
            'cookieValidationKey' => 'bp-XQRAbA9PenFf2ghbKdUpWKbAjTY_p',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\Users',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'mail.itrust-tech.id', //smtp.gmail.com',
                'username' => 'admin@rochat.id',
                'password' => 'Bismillah@2021',
                'port' => '587',
                'encryption' => 'tls',
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // Myfxbook API endpoints
                'POST mobile/save-scrape-data' => 'mobile/save-scrape-data',
                'GET mobile/get-scrape-data' => 'mobile/get-scrape-data',
                'GET mobile/get-latest-events' => 'mobile/get-latest-events',
                'GET mobile/get-high-impact-events' => 'mobile/get-high-impact-events',
                'GET mobile/get-technical-analysis' => 'mobile/get-technical-analysis',
                'GET mobile/get-interest-rates' => 'mobile/get-interest-rates',
                'GET mobile/get-statistics' => 'mobile/get-statistics',

                'course/<id:\d+>' => 'site/detailcourse',
                'enroll/<id:\d+>/<id_section:\d+>' => 'site/enroll',
                'courses/sections/<id:\d+>' => 'courses/sections',
                'bot/webhook' => 'bot/webhook',

                'mobile/login' => 'mobile/login',
                'mobile/user-devices' => 'mobile/user-devices',
                'mobile/device-telemetry' => 'mobile/device-telemetry',
                'mobile/device-telemetry-by-id' => 'mobile/device-telemetry-by-id',
                'mobile/validate-token' => 'mobile/validate-token',

                // Telemetry Device API Routes
                'telemetry-device' => 'telemetry-device/index',
                'telemetry-device/create' => 'telemetry-device/create',
                'telemetry-device/<id:\d+>' => 'telemetry-device/view',
                'telemetry-device/<id:\d+>/update' => 'telemetry-device/update',
                'telemetry-device/<id:\d+>/delete' => 'telemetry-device/delete',
                'telemetry-device/<id:\d+>/activate' => 'telemetry-device/activate',
                'telemetry-device/<id:\d+>/telemetry' => 'telemetry-device/telemetry',
                'telemetry-device/<id:\d+>/stats' => 'telemetry-device/stats',
                'telemetry-device/search' => 'telemetry-device/search',
                'telemetry-device/user/<user_id:\d+>' => 'telemetry-device/by-user',
                'telemetry-device/bulk-assign' => 'telemetry-device/bulk-assign',
                // 'confirm/<email:\s+>/<token:\s+>' => 'site/confirm',

                // Scraped data endpoints
                'scraped-data/save' => 'scraped-data/save',
                'scraped-data/logs' => 'scraped-data/logs',
                'scraped-data/statistics' => 'scraped-data/statistics',
                'scraped-data/test' => 'scraped-data/test',

            ],
        ],
    ],
    'modules' => [
        'gridview' =>  [
            'class' => '\kartik\grid\Module'
            // enter optional module parameters below - only if you need to  
            // use your own export download action or custom translation 
            // message source
            // 'downloadAction' => 'gridview/export/download',
            // 'i18n' => []
        ],
        'datecontrol' =>  [
            'class' => 'kartik\datecontrol\Module',


            // format settings for displaying each date attribute
            'displaySettings' => [
                'date' => 'php:Y-m-d',
                'time' => 'H:i:s A',
                'datetime' => 'd-M-y H:i:s A',
            ],

            // format settings for saving each date attribute
            'saveSettings' => [
                'date' => 'php:Y-m-d',
                'time' => 'H:i:s',
                'datetime' => 'y-M-d H:i:s',
            ],
            // 'convertFormat' => false,


            // automatically use kartik\widgets for each of the above formats
            'autoWidget' => true,
            'ajaxConversion' => false,
            'autoWidgetSettings' => [
                'date' => [
                    'pluginOptions' => [
                        'autoclose' => true,
                        'todayHighlight' => true,
                        'todayBtn' => true,
                    ],
                ],
            ],


        ]
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'generators' => [
            'kartikgii-crud' => ['class' => 'warrence\kartikgii\crud\Generator'],
        ],
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
