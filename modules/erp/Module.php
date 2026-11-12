<?php

namespace erp;

use Yii;
use yii\log\FileTarget;
use yii\web\Response;
use yii\web\User;

/**
 * Modul ERP iTrustERP di dalam aplikasi iTrust.
 *
 * Semua kode ERP berada di namespace `erp\*` dan memakai koneksi database
 * sendiri (`AUREUS_DB`, default `itrust_erp`). Konfigurasi di sini hanya
 * berlaku untuk request yang masuk ke modul (URL /erp/api/v1/...), sehingga
 * halaman iTrust tidak terpengaruh.
 */
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'erp\controllers';

    public function init()
    {
        parent::init();

        // 1) Database ERP (terpisah dari database iTrust)
        Yii::$app->set('db', require __DIR__ . '/config/db.php');

        // 2) Komponen hak akses ERP
        Yii::$app->set('access', ['class' => \erp\components\Access::class]);

        // 3) Identitas stateless (token bearer ERP), tanpa session iTrust
        Yii::$app->set('user', [
            'class' => User::class,
            'identityClass' => \erp\models\User::class,
            'enableSession' => false,
            'enableAutoLogin' => false,
            'loginUrl' => null,
        ]);

        // 4) Respons & error JSON khusus API ERP
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->set('errorHandler', ['class' => \erp\components\ApiErrorHandler::class]);

        // 4b) Terima body application/json (aplikasi iTrust belum mendaftarkannya)
        Yii::$app->request->parsers['application/json'] = \yii\web\JsonParser::class;

        // 5) Parameter ERP — tidak menimpa parameter iTrust yang sudah ada
        Yii::$app->params['erpApiPrefix'] = '/erp/api/v1';
        foreach ((array) require __DIR__ . '/config/params.php' as $key => $value) {
            if (!array_key_exists($key, Yii::$app->params)) {
                Yii::$app->params[$key] = $value;
            }
        }

        // 6) Log ERP terpisah dari log iTrust
        $log = Yii::$app->get('log', false);
        if ($log !== null) {
            $log->targets = [new FileTarget([
                'logFile' => '@erp/runtime/logs/app.log',
                'levels' => ['error', 'warning'],
                'logVars' => [],
            ])];
        }

        @mkdir(Yii::getAlias('@erp/runtime/logs'), 0775, true);
    }
}