<?php

namespace app\controllers;

use Yii;
use DateTime;
use app\models\CloseOrder;

use yii\web\Controller;
use yii\web\Response;

class CloseOrderController extends Controller
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
                return ['success' => true, 'message' => "Updated $updateCount records."];
            } else {
                return ['success' => false, 'message' => "No records updated."];
            }
        } else {
            return ['success' => false, 'message' => "Invalid POST data."];
        }
    }

    public function actionGet()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $account = Yii::$app->request->post('id');

        if ($account) {

            $order = CloseOrder::findOne(['order_account' => $account, 'order_status' => 0]);

            if ($order !== null) {
                return ['success' => true, 'message' => "Success Getting Command"];

            } else {
                return ['success' => false, 'message' => "Close Order command not found"];

            }
        } else {
            return ['success' => false, 'message' => "failed to Close Order"];
        }
        
    }

}
