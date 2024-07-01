<?php

namespace app\controllers;

use Yii;
use DateTime;
use app\models\Users;

use yii\web\Response;
use yii\web\Controller;
use app\models\CloseOrder;

class ItrustController extends Controller
{
    public $enableCsrfValidation = false; // Disable CSRF validation for this controller

    public function actionIndex()
    {
        echo "Working";
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
                return 1; //return ['success' => true, 'message' => "Updated $updateCount records."];
            } else {
                return 0; //return ['success' => false, 'message' => "No records updated."];
            }
        } else {
            return 0; //return ['success' => false, 'message' => "Invalid POST data."];
        }
    }

    public function actionGet()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $account = Yii::$app->request->post('id');

        if ($account) {

            $order = CloseOrder::findOne(['order_account' => $account, 'order_status' => 0]);

            if ($order !== null) {
                if ($order->order_cmd == "outlook") {
                    return 1;
                } else if ($order->order_cmd == "close_all") {
                    return 99;
                } else {
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

    public function actionLicense()
    {
        $currentDate = new DateTime();

        // $uniqueId = Yii::$app->user->id; // Replace with your unique identifier
        $account = Yii::$app->request->post('id');

        $user = Users::find()
            ->where(['user_account' => $account])
            ->one();

        if ($user) {
            $license_expired = new DateTime($user->user_license_expired);

            if ($user->user_license && ($license_expired > $currentDate)) {
                return $user->user_license;
            } else if ($license_expired < $currentDate) {
                return "expired at " . $user->user_license_expired;
            } else {
                $licenseNumber = $this->generateLicenseNumber($account);
                $user->user_license = $licenseNumber;
                if (!$user->save()) {
                    return 0;
                } else {
                    return $licenseNumber;
                }
            }
        } else {
            return -1;
        }
    }

    public function actionCheckLicense()
    {
        $account = Yii::$app->request->post('id');
        $license = Yii::$app->request->post('license');
        $currentDate = new DateTime();

        $user = Users::find()
            ->where(['user_account' => $account])
            ->andWhere(['user_license' => $license])
            ->one();

        if ($user) {
            $license_expired = new DateTime($user->user_license_expired);
            if ($license_expired > $currentDate) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return -1;
        }
    }

    private function generateLicenseNumber($uniqueId)
    {
        $salt = 'B15m1ll4#'; // Use a secret salt for added security
        $hash = md5($uniqueId . $salt); // You can use other hashing algorithms like sha256
        return hexdec(substr($hash, 0, 8)); // Convert hash to a number and return the first 8 digits
    }
}
