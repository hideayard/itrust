<?php

namespace app\controllers;

use Yii;
use DateTime;
use app\models\CloseOrder;
use app\models\CourseSession;
use app\models\CourseSessionSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\Courses;
use yii\helpers\ArrayHelper;
use app\models\Users;
use yii\web\Response;

class CreateOrderController extends Controller
{

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                    'generate' => ['post'],
                    'submit-section' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }


    // public $enableCsrfValidation = false; // Disable CSRF validation for this controller

    public function actionIndex()
    {
        echo "Working";
    }

    public function actionGenerate()
    {
        // $uniqueId = Yii::$app->user->id; // Replace with your unique identifier
        $uniqueId = Yii::$app->request->post('id');

        $licenseNumber = $this->generateLicenseNumber($uniqueId);
        // return $this->render('license', ['licenseNumber' => $licenseNumber]);
        echo $licenseNumber;
    }

    private function generateLicenseNumber($uniqueId)
    {
        $salt = 'B15m1ll4#'; // Use a secret salt for added security
        $hash = md5($uniqueId . $salt); // You can use other hashing algorithms like sha256
        return hexdec(substr($hash, 0, 8)) . " - ". $uniqueId; // Convert hash to a number and return the first 8 digits
    }
    
    public function actionClose()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $account = Yii::$app->request->post('id');

        if ($account) {

            $order = new CloseOrder();
            $order->order_account = $account;
            $order->order_cmd = "close_all";
            $order->order_status = 0;
            $order->order_date =  (new DateTime())->format('Y-m-d H:i:s');

            if (!$order->save()) {
                return ($order->errors)[0];
            }
            return ['success' => true, 'message' => "Close order command sent"];
        } else {
            return ['success' => false, 'message' => "failed to Close Order"];
        }
    }

    public function actionOutlook()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $account = Yii::$app->request->post('id');

        if ($account) {

            $order = new CloseOrder();
            $order->order_account = $account;
            $order->order_cmd = "outlook";
            $order->order_status = 0;
            $order->order_date =  (new DateTime())->format('Y-m-d H:i:s');

            if (!$order->save()) {
                return ($order->errors)[0];
            }
            return ['success' => true, 'message' => "Outlook command sent"];
        } else {
            return ['success' => false, 'message' => "failed to send command"];
        }
    }

    public function actionCancel()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $account = Yii::$app->request->post('id');

        if ($account) {

            $updateCount = CloseOrder::updateAll(
                ['order_status' => 1], // Update attributes
                ['order_account' => $account] // Condition: where id IN ($account)
            );

            if ($updateCount > 0) {
                return 1;//return ['success' => true, 'message' => "Updated $updateCount records."];
            } else {
                return 0;//return ['success' => false, 'message' => "No records updated."];
            }
        } else {
            return 0;//return ['success' => false, 'message' => "Invalid POST data."];
        }
    }

    public function actionGet()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $account = Yii::$app->request->post('id');

        if ($account) {

            $order = CloseOrder::findOne(['order_account' => $account, 'order_status' => 0]);

            if ($order !== null) {
                if($order->order_cmd == "outlook")
                {
                    return 1;
                }
                else if($order->order_cmd == "close_all")
                {
                    return 99;
                }
                else
                {
                    return 0;
                }
                // return ['success' => true, 'message' => "Success Getting Command", 'type' => $order->order_cmd];

            } else {
                // return ['success' => false, 'message' => "Close Order command not found"];
                return 0;

            }
        } else {
            // return ['success' => false, 'message' => "failed to Close Order"];
            return 0;
        }
        
    }

}
