<?php

namespace app\controllers;

use Yii;
use DateTime;
use app\models\Users;
use app\models\Drawdown;
use app\models\Withdraw;

use yii\web\Response;
use yii\web\Controller;
use app\models\CloseOrder;

class EaController extends Controller
{
    public $enableCsrfValidation = false; // Disable CSRF validation for this controller

    public function actionIndex()
    {
        Yii::debug('debug message'); // Use Yii's logging
        return 'index';
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

            $order = CloseOrder::find()
                ->where(['order_account' => $account, 'order_status' => 0])
                ->orderBy(['order_date' => SORT_DESC])
                ->one();


            if ($order !== null) {
                if ($order->order_cmd == "outlook") {
                    return 1;
                } else if ($order->order_cmd == "cr_off") {
                    return 10;
                } else if ($order->order_cmd == "cr_on") {
                    return 11;
                } else if ($order->order_cmd == "close_all") {
                    return 99;
                } else if (str_contains($order->order_cmd, 'OP')) {
                    return $order->order_cmd;
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
        $result = -1; //$account ." - " . $license;
        $user = Users::find()
            ->where(['user_account' => "$account"])
            ->andWhere(['user_license' => "$license"])
            ->one();
        // $result =$user;//$account ." - " . $license;

        if ($user) {
            $license_expired = new DateTime($user->user_license_expired);
            if ($license_expired > $currentDate) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return $result;
        }
    }

    public function actionCheckLicenseV2()
    {
        $license = Yii::$app->request->post('license');
        $currentDate = new DateTime();
        $result = -1; //$account ." - " . $license;
        $user = Users::findOne(['user_license' => "$license"]);

        if ($user) {
            $license_expired = new DateTime($user->user_license_expired);
            if ($license_expired > $currentDate) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return $result;
        }
    }


    public function actionCheckLicenseTime()
    {
        $license = Yii::$app->request->post('license');
        $user = Users::findOne(['user_license' => $license]);

        if ($user && $user->user_license_expired) {
            // Calculate seconds remaining from current time
            $currentTime = time();
            $expiryTime = strtotime($user->user_license_expired);
            $secondsRemaining = $expiryTime - $currentTime;

            // Return only positive values, 0 if expired
            return $secondsRemaining > 0 ? $secondsRemaining : 0;
        } else {
            return 0;
        }
    }

    private function generateLicenseNumber($uniqueId)
    {
        $salt = 'B15m1ll4#'; // Use a secret salt for added security
        $hash = md5($uniqueId . $salt); // You can use other hashing algorithms like sha256
        return hexdec(substr($hash, 0, 8)); // Convert hash to a number and return the first 8 digits
    }

    public function actionCheckLicenseTimeV2()
    {
        $request = Yii::$app->request;
        $license = $request->post('license', $request->get('license'));
        $user = Users::findOne(['user_license' => $license]);

        $response = [
            'license' => $license,
            'remaining_time' => 0,
            'expired_date' => null
        ];

        if ($user && $user->user_license_expired) {
            // Calculate seconds remaining from current time
            $currentTime = time();
            $expiryTime = strtotime($user->user_license_expired);
            $secondsRemaining = $expiryTime - $currentTime;

            // Ensure non-negative remaining time
            $response['remaining_time'] = $secondsRemaining > 0 ? $secondsRemaining : 0;
            $response['expired_date'] = $user->user_license_expired;
        }

        // Return JSON response using Yii's method
        return $this->asJson($response);
    }


    /**
     * Save or update trade DD data
     * @return array
     */
    public function actionSaveDd()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $license = Yii::$app->request->post('license');
            $account = Yii::$app->request->post('account');
            $wk_dd = Yii::$app->request->post('wk_dd', 0);
            $wk_percentage_dd = Yii::$app->request->post('wk_percentage_dd', 0);
            $wk_date = Yii::$app->request->post('wk_date');
            $wk_equity = Yii::$app->request->post('wk_equity', 0);
            $all_dd = Yii::$app->request->post('all_dd', 0);
            $all_percentage_dd = Yii::$app->request->post('all_percentage_dd', 0);
            $all_date = Yii::$app->request->post('all_date');
            $all_equity = Yii::$app->request->post('all_equity', 0);

            // Validate required parameters
            if (empty($license) || empty($account)) {
                throw new \yii\web\BadRequestHttpException('License and account are required');
            }

            // Find user by license
            $user = Users::findOne(['user_license' => $license]);
            if (!$user) {
                throw new \yii\web\NotFoundHttpException('User not found for the provided license');
            }

            // Check if record already exists for this account and license
            $tradeDd = Drawdown::find()
                ->where(['account' => $account, 'license' => $license])
                ->one();

            if (!$tradeDd) {
                // Create new record
                $tradeDd = new Drawdown();
                $tradeDd->user_id = $user->id;
                $tradeDd->license = $license;
                $tradeDd->account = $account;
            }

            // Function to convert date format from Y.m.d HH:MM to Y-m-d H:i:s
            $convertDate = function ($dateString) {
                if (empty($dateString)) {
                    return null;
                }

                // Replace dots with dashes and ensure proper format
                $converted = str_replace('.', '-', $dateString);

                // Create DateTime object from the converted format
                $dateTime = DateTime::createFromFormat('Y-m-d H:i', $converted);

                if ($dateTime === false) {
                    // If first format fails, try alternative parsing
                    try {
                        $dateTime = new DateTime($converted);
                    } catch (\Exception $e) {
                        Yii::warning("Failed to parse date: $dateString. Error: " . $e->getMessage());
                        return null;
                    }
                }

                return $dateTime ? $dateTime->format('Y-m-d H:i:s') : null;
            };

            // Update values
            $tradeDd->wk_dd = (float)$wk_dd;
            $tradeDd->wk_percentage_dd = (float)$wk_percentage_dd;
            $tradeDd->wk_date = $convertDate($wk_date);
            $tradeDd->wk_equity = (float)$wk_equity;
            $tradeDd->all_dd = (float)$all_dd;
            $tradeDd->all_percentage_dd = (float)$all_percentage_dd;
            $tradeDd->all_date = $convertDate($all_date);
            $tradeDd->all_equity = (float)$all_equity;

            if ($tradeDd->save()) {
                return [
                    'status' => 'success',
                    'message' => 'Trade DD data saved successfully',
                    'data' => [
                        'id' => $tradeDd->id,
                        'account' => $tradeDd->account,
                        'wk_date' => $tradeDd->wk_date,
                        'all_date' => $tradeDd->all_date
                    ]
                ];
            } else {
                Yii::error('Failed to save trade DD data: ' . json_encode($tradeDd->errors));
                throw new \yii\web\ServerErrorHttpException('Failed to save trade DD data: ' . json_encode($tradeDd->errors));
            }
        } catch (\Exception $e) {
            Yii::error('Error in actionSaveDd: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get trade DD data by license
     * @return array
     */
    public function actionGetDd()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $request = Yii::$app->request;
            $license = $request->post('license', $request->get('license'));

            // Validate required parameter
            if (empty($license)) {
                throw new \yii\web\BadRequestHttpException('License is required');
            }

            // Find all records for this license
            $tradeDdRecords = Drawdown::find()
                ->where(['license' => $license])
                ->orderBy(['created_at' => SORT_DESC])
                ->all();

            if (empty($tradeDdRecords)) {
                return [
                    'status' => 'success',
                    'message' => 'No DD records found for this license',
                    'data' => []
                ];
            }

            // Format response data
            $data = [];
            foreach ($tradeDdRecords as $record) {
                $data[] = [
                    'id' => $record->id,
                    'account' => $record->account,
                    'wk_dd' => $record->wk_dd,
                    'wk_percentage_dd' => $record->wk_percentage_dd,
                    'wk_date' => $record->wk_date,
                    'wk_equity' => $record->wk_equity,
                    'all_dd' => $record->all_dd,
                    'all_percentage_dd' => $record->all_percentage_dd,
                    'all_date' => $record->all_date,
                    'all_equity' => $record->all_equity,
                    'created_at' => $record->created_at
                ];
            }

            return [
                'status' => 'success',
                'message' => 'DD records retrieved successfully',
                'data' => $data
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionGetDd: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function actionSaveWd()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $license = Yii::$app->request->post('license');
            $account = Yii::$app->request->post('account');
            $wd_value = Yii::$app->request->post('wd_value');

            // Validate required parameters
            if (empty($license) || empty($account) || empty($wd_value)) {
                throw new \yii\web\BadRequestHttpException('License, account, and withdraw value are required');
            }

            // Validate wd_value is numeric and positive
            if (!is_numeric($wd_value) || $wd_value <= 0) {
                throw new \yii\web\BadRequestHttpException('Withdraw value must be a positive number');
            }

            // Find user by license
            $user = Users::findOne(['user_license' => $license]);
            if (!$user) {
                throw new \yii\web\NotFoundHttpException('User not found for the provided license');
            }

            // Create new withdraw record
            $withdraw = new Withdraw();
            $withdraw->user_id = $user->id;
            $withdraw->license = $license;
            $withdraw->account = $account;
            $withdraw->wd_value = $wd_value;

            if ($withdraw->save()) {
                return [
                    'status' => 'success',
                    'message' => 'Withdraw data saved successfully',
                    'data' => [
                        'id' => $withdraw->id,
                        'account' => $withdraw->account,
                        'wd_value' => $withdraw->wd_value,
                        'created_at' => $withdraw->created_at
                    ]
                ];
            } else {
                Yii::error('Failed to save withdraw data: ' . json_encode($withdraw->errors));
                throw new \yii\web\ServerErrorHttpException('Failed to save withdraw data: ' . json_encode($withdraw->errors));
            }
        } catch (\Exception $e) {
            Yii::error('Error in actionSaveWd: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get withdraw data by license
     * @return array
     */
    public function actionGetWd()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $request = Yii::$app->request;
            $license = $request->post('license', $request->get('license'));

            // Validate required parameter
            if (empty($license)) {
                throw new \yii\web\BadRequestHttpException('License is required');
            }

            // Find all withdraw records for this license
            $withdrawRecords = Withdraw::find()
                ->where(['license' => $license])
                ->orderBy(['created_at' => SORT_DESC])
                ->all();

            if (empty($withdrawRecords)) {
                return [
                    'status' => 'success',
                    'message' => 'No withdraw records found for this license',
                    'data' => []
                ];
            }

            // Format response data
            $data = [];
            foreach ($withdrawRecords as $record) {
                $data[] = [
                    'id' => $record->id,
                    'account' => $record->account,
                    'wd_value' => (float)$record->wd_value,
                    'created_at' => $record->created_at
                ];
            }

            return [
                'status' => 'success',
                'message' => 'Withdraw records retrieved successfully',
                'data' => $data
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionGetWd: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get total withdraw value by license
     * @return array
     */
    public function actionGetTotalWd()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $license = Yii::$app->request->post('license');

            // Validate required parameter
            if (empty($license)) {
                throw new \yii\web\BadRequestHttpException('License is required');
            }

            // Calculate total withdraw value for this license
            $totalWithdraw = Withdraw::find()
                ->where(['license' => $license])
                ->sum('wd_value');

            // If no records found, total will be null, convert to 0
            $totalWithdraw = $totalWithdraw ? (float)$totalWithdraw : 0.0;

            return [
                'status' => 'success',
                'message' => 'Total withdraw value retrieved successfully',
                'data' => [
                    'license' => $license,
                    'total_wd_value' => $totalWithdraw
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionGetTotalWd: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function actionTestPost()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        $data = $request->getBodyParams(); //Yii::$app->request->post('data');
        return $data;
    }
}
