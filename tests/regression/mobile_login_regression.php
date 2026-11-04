<?php
/**
 * Minimal regression test for POST /mobile/login (MobileController::actionLogin()).
 *
 * Runs against the application's configured database (config/web.php) and asserts:
 *   1. valid credentials            -> success === true + a valid JWT
 *   2. wrong password               -> success === false / INVALID_CREDENTIALS
 *   3. notification throws          -> login still succeeds
 *      (regression: a post-auth side effect must never fail the login)
 *   4. jwtSecret missing            -> fail fast with SERVER_CONFIG_ERROR
 *
 * Usage:  php tests/regression/mobile_login_regression.php
 *
 * NOTE: this uses the live application DB, so run it against a non-production
 * database (it creates and removes a throwaway user).
 */

use app\controllers\MobileController;
use app\helpers\JwtHelper;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';

$_SERVER['REQUEST_METHOD']    = 'POST';
$_SERVER['REQUEST_URI']       = '/mobile/login';
$_SERVER['SCRIPT_NAME']       = '/index.php';
$_SERVER['SCRIPT_FILENAME']   = $root . '/web/index.php';
$_SERVER['PHP_SELF']          = '/index.php';
$_SERVER['HTTP_HOST']         = 'localhost';
$_SERVER['REMOTE_ADDR']       = '127.0.0.1';
$_SERVER['SERVER_PROTOCOL']   = 'HTTP/1.1';
$_SERVER['SERVER_NAME']       = 'localhost';
$_SERVER['SERVER_PORT']       = '80';

new \yii\web\Application(require $root . '/config/web.php');

Yii::$app->user->enableSession = false;

$failures = 0;
function check($condition, $label)
{
    global $failures;
    echo ($condition ? '  ok   ' : '  FAIL ') . $label . "\n";
    if (!$condition) {
        $failures++;
    }
}

/** Deterministic, network-free controller. */
function controllerWithoutNotification()
{
    return new class('mobile', Yii::$app) extends MobileController {
        public $notified = false;
        protected function sendLoginNotification($username, $clientIp)
        {
            $this->notified = true;
        }
    };
}

/** Notification deliberately throws, to prove the login result is unaffected. */
function controllerWithFailingNotification()
{
    return new class('mobile', Yii::$app) extends MobileController {
        protected function sendLoginNotification($username, $clientIp)
        {
            throw new \RuntimeException('simulated notification failure');
        }
    };
}

function runLogin(MobileController $controller, $username, $password)
{
    Yii::$app->user->logout();
    Yii::$app->request->setBodyParams(['username' => $username, 'password' => $password]);
    return $controller->run('login');
}

$username = 'zz_regression_login';
$password = 'Regr355!on';
$userId   = 900002;

$db = Yii::$app->db;
$db->createCommand()->delete('users', ['user_name' => $username])->execute();
$db->createCommand()->insert('users', [
    'user_id'     => $userId,
    'user_name'   => $username,
    'user_nama'   => 'Regression User',
    'user_pass'   => Yii::$app->getSecurity()->generatePasswordHash($password),
    'user_email'  => 'regression@example.com',
    'user_tipe'   => 'CUSTOMER',
    'user_foto'   => '',
    'user_status' => 1,
    'is_deleted'  => 0,
])->execute();

try {
    echo "\n1) valid credentials -> success + valid JWT\n";
    $controller = controllerWithoutNotification();
    $res = runLogin($controller, $username, $password);
    check(isset($res['success']) && $res['success'] === true, 'success === true');
    check(!empty($res['token']), 'token present');
    check(count(explode('.', (string)($res['token'] ?? ''))) === 3, 'token has 3 parts');
    check($controller->notified === true, 'notification was attempted');

    $decoded = null;
    try {
        $decoded = JwtHelper::validate($res['token'], Yii::$app->params['jwtSecret']);
    } catch (\Throwable $e) {
    }
    check(is_array($decoded) && ($decoded['data']['id'] ?? null) == $userId, 'JWT validates and carries the user id');

    echo "\n2) wrong password -> success:false / INVALID_CREDENTIALS\n";
    $res = runLogin(controllerWithoutNotification(), $username, 'wrong-password');
    check($res['success'] === false, 'success === false');
    check(($res['error_code'] ?? null) === 'INVALID_CREDENTIALS', 'error_code === INVALID_CREDENTIALS');
    check($res['message'] === 'Invalid username or password', 'message is the auth-failure message');
    check($res['token'] === null, 'no token');

    echo "\n3) notification throws -> login still succeeds (regression)\n";
    $res = runLogin(controllerWithFailingNotification(), $username, $password);
    check($res['success'] === true, 'success === true despite notification failure');
    check(!empty($res['token']), 'token still issued');

    echo "\n4) missing jwtSecret -> fail fast / SERVER_CONFIG_ERROR\n";
    $savedSecret = Yii::$app->params['jwtSecret'] ?? null;
    unset(Yii::$app->params['jwtSecret']);
    $res = runLogin(controllerWithoutNotification(), $username, $password);
    check($res['success'] === false, 'success === false');
    check(($res['error_code'] ?? null) === 'SERVER_CONFIG_ERROR', 'error_code === SERVER_CONFIG_ERROR');
    if ($savedSecret !== null) {
        Yii::$app->params['jwtSecret'] = $savedSecret;
    }
} finally {
    $db->createCommand()->delete('users', ['user_name' => $username])->execute();
}

echo "\n" . ($failures === 0 ? 'ALL CHECKS PASSED' : $failures . ' CHECK(S) FAILED') . "\n";
exit($failures === 0 ? 0 : 1);
