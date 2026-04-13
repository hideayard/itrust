<?php

namespace app\controllers;

use app\helpers\TelegramHelper;
use app\models\AccountOrders;
use app\models\CloseOrder;
use app\models\Drawdown;
use app\models\Mt4Account;
use app\models\Users;
use app\models\Withdraw;
use DateTime;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class EaController extends Controller
{
    public $enableCsrfValidation = false; // Disable CSRF validation for this controller

    /**
     * Disable CSRF validation for this action (important for external API calls)
     */
    public function beforeAction($action)
    {
        if ($action->id === 'sync-account') {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

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
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $request = Yii::$app->request;
            $accountID = $request->post('id', $request->get('id'));

            // Validate required parameter
            if (empty($accountID)) {
                throw new \yii\web\BadRequestHttpException('account id is required');
            }

            $order = CloseOrder::find()
                ->where(['order_account' => $accountID, 'order_status' => 0])
                ->orderBy(['order_date' => SORT_DESC])
                ->one();

            $data = "";
            if ($order !== null) {
                if ($order->order_cmd == "outlook") {
                    $data =  1;
                } else if ($order->order_cmd == "cr_off") {
                    $data =  10;
                } else if ($order->order_cmd == "cr_on") {
                    $data =  11;
                } else if ($order->order_cmd == "close_all") {
                    $data =  99;
                } else {
                    $data =  $order->order_cmd;
                }
            } else {
                $data =  0;
            }

            return [
                'status' => 'success',
                'message' => 'get Command successfully',
                'data' => $data
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionGet command: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

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
                } else if (str_contains($order->order_cmd, 'OP') || ($order->order_cmd == "CLOSE_ALL") || ($order->order_cmd ==  "CLOSE_BUY") || ($order->order_cmd ==  "CLOSE_SELL") || ($order->order_cmd ==  "BUY") || ($order->order_cmd ==  "SELL")) {
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
    public function actionSaveDdBackup()
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
            $disabled_ea = Yii::$app->request->post('disabled_ea', 0); // New parameter

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

            $isNewRecord = false;
            if (!$tradeDd) {
                // Create new record
                $tradeDd = new Drawdown();
                $tradeDd->user_id = $user->id;
                $tradeDd->license = $license;
                $tradeDd->account = $account;
                $isNewRecord = true;
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

            // Convert date strings
            $wk_date_converted = $convertDate($wk_date);
            $all_date_converted = $convertDate($all_date);

            // Always update weekly data
            $tradeDd->wk_dd = (float)$wk_dd;
            $tradeDd->wk_percentage_dd = (float)$wk_percentage_dd;
            $tradeDd->wk_date = $wk_date_converted;
            $tradeDd->wk_equity = (float)$wk_equity;

            // Update disabled_ea status (assuming your Drawdown model has this field)
            $tradeDd->disabled_ea = (int)$disabled_ea; // Store as integer (0 or 1)

            // For all-time data: only update if new all_dd is greater than existing
            // OR if it's a new record
            if ($isNewRecord || (float)$all_dd > (float)$tradeDd->all_dd) {
                $tradeDd->all_dd = (float)$all_dd;
                $tradeDd->all_percentage_dd = (float)$all_percentage_dd;
                $tradeDd->all_date = $all_date_converted;
                $tradeDd->all_equity = (float)$all_equity;
            } else {
                // Keep existing all-time data unchanged
                // Optionally log that we're keeping the existing higher drawdown
                Yii::info("Keeping existing all_dd ({$tradeDd->all_dd}) as new value ($all_dd) is lower or equal");
            }

            if ($tradeDd->save()) {
                return [
                    'status' => 'success',
                    'message' => 'Trade DD data saved successfully',
                    'data' => [
                        'id' => $tradeDd->id,
                        'account' => $tradeDd->account,
                        'wk_date' => $tradeDd->wk_date,
                        'all_date' => $tradeDd->all_date,
                        'wk_dd' => $tradeDd->wk_dd,
                        'all_dd' => $tradeDd->all_dd,
                        'disabled_ea' => $tradeDd->disabled_ea, // Include in response
                        'all_dd_updated' => !$isNewRecord && (float)$all_dd > (float)$tradeDd->getOldAttribute('all_dd')
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

    public function actionSyncAccount()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            // Get POST parameters
            $bot_name = Yii::$app->request->post('bot_name');
            $broker = Yii::$app->request->post('broker_name');
            $license = Yii::$app->request->post('license');
            $accountId = Yii::$app->request->post('account_id');

            // Get account metrics
            $buyOrderCount = Yii::$app->request->post('buy_order_count', 0);
            $totalBuyLot = Yii::$app->request->post('total_buy_lot', 0);
            $sellOrderCount = Yii::$app->request->post('sell_order_count', 0);
            $totalSellLot = Yii::$app->request->post('total_sell_lot', 0);
            $totalProfit = Yii::$app->request->post('total_profit', 0);
            $accountBalance = Yii::$app->request->post('account_balance', 0);
            $accountEquity = Yii::$app->request->post('account_equity', 0);
            $floatingValue = Yii::$app->request->post('floating_value', 0);
            // $timestamp = Yii::$app->request->post('timestamp', time());

            // Validate required parameters
            if (empty($license)) {
                throw new BadRequestHttpException('License is required');
            }

            if (empty($accountId)) {
                throw new BadRequestHttpException('Account ID is required');
            }

            // Find user by license
            $user = Users::findOne(['user_license' => $license]);
            if (!$user) {
                throw new NotFoundHttpException('User not found for the provided license');
            }

            // Calculate profit percentage if balance > 0
            $totalProfitPercentage = 0;
            if ($accountBalance > 0 && $totalProfit != 0) {
                $totalProfitPercentage = ($totalProfit / ($accountBalance - $totalProfit)) * 100;
                if ($totalProfitPercentage > 999999) $totalProfitPercentage = 999999;
                if ($totalProfitPercentage < -999999) $totalProfitPercentage = -999999;
            }

            // Try to find existing account first
            $mt4Account = Mt4Account::find()
                ->where([
                    'user_id' => $user->user_id,
                    'account_id' => (string)$accountId
                ])
                ->one();

            $isNewRecord = false;

            if ($mt4Account) {
                // Account exists - just update it
                $isNewRecord = false;
                Yii::info("Updating existing MT4 account for user {$user->user_id}, account {$accountId}");
            } else {
                // Account doesn't exist - create new
                $mt4Account = new Mt4Account();
                $mt4Account->bot_name = $bot_name;
                $mt4Account->broker = $broker;
                $mt4Account->user_id = $user->user_id;
                $mt4Account->account_id = (string)$accountId;
                $mt4Account->status = Mt4Account::STATUS_ACTIVE;
                $mt4Account->currency = 'USD';
                $mt4Account->leverage = 100;
                $mt4Account->account_type = Mt4Account::ACCOUNT_TYPE_STANDARD;
                $mt4Account->last_connected = date('Y-m-d H:i:s');//date('Y-m-d H:i:s', $timestamp);
                $mt4Account->last_sync = date('Y-m-d H:i:s');//date('Y-m-d H:i:s', $timestamp);
                $isNewRecord = true;

                Yii::info("Creating new MT4 account record for user {$user->user_id}, account {$accountId}");
            }

            // Store previous values for comparison
            $previousBalance = $mt4Account->account_balance;
            $previousEquity = $mt4Account->account_equity;

            // Update account metrics (for both existing and new)
            $mt4Account->bot_name = $bot_name;
            $mt4Account->broker = $broker;
            $mt4Account->buy_order_count = (int)$buyOrderCount;
            $mt4Account->total_buy_lot = (float)$totalBuyLot;
            $mt4Account->sell_order_count = (int)$sellOrderCount;
            $mt4Account->total_sell_lot = (float)$totalSellLot;
            $mt4Account->total_profit = (float)$totalProfit;
            $mt4Account->total_profit_percentage = (float)$totalProfitPercentage;
            $mt4Account->account_balance = (float)$accountBalance;
            $mt4Account->account_equity = (float)$accountEquity;
            $mt4Account->floating_value = (float)$floatingValue;
            $mt4Account->last_connected = date('Y-m-d H:i:s');//date('Y-m-d H:i:s', $timestamp);
            $mt4Account->last_sync = date('Y-m-d H:i:s');//date('Y-m-d H:i:s', $timestamp);

            // Update status based on equity
            if ($accountEquity > 0) {
                $mt4Account->status = Mt4Account::STATUS_ACTIVE;
            } elseif ($accountEquity <= 0 && $mt4Account->status == Mt4Account::STATUS_ACTIVE) {
                $mt4Account->status = Mt4Account::STATUS_DISCONNECTED;
            }

            // Save the record (validation will run)
            if ($mt4Account->save()) {
                // Prepare response data
                $responseData = [
                    'id' => $mt4Account->id,
                    'account_id' => $mt4Account->account_id,
                    'user_id' => $mt4Account->user_id,
                    'status' => $mt4Account->status,
                    'is_new_record' => $isNewRecord,
                    'metrics' => [
                        'buy_order_count' => $mt4Account->buy_order_count,
                        'total_buy_lot' => $mt4Account->total_buy_lot,
                        'sell_order_count' => $mt4Account->sell_order_count,
                        'total_sell_lot' => $mt4Account->total_sell_lot,
                        'total_orders' => $mt4Account->getTotalOrders(),
                        'total_lots' => $mt4Account->getTotalLots(),
                        'total_profit' => $mt4Account->total_profit,
                        'total_profit_percentage' => $mt4Account->total_profit_percentage,
                        'account_balance' => $mt4Account->account_balance,
                        'account_equity' => $mt4Account->account_equity,
                        'floating_value' => $mt4Account->floating_value,
                    ],
                    'changes' => [
                        'balance_changed' => ($previousBalance != $accountBalance),
                        'balance_previous' => $previousBalance,
                        'balance_new' => $accountBalance,
                        'equity_changed' => ($previousEquity != $accountEquity),
                        'equity_previous' => $previousEquity,
                        'equity_new' => $accountEquity,
                    ],
                    'last_sync' => $mt4Account->last_sync,
                ];

                Yii::info("MT4 account synced successfully: user_id={$user->user_id}, account_id={$accountId}, is_new={$isNewRecord}");

                return [
                    'status' => 'success',
                    'message' => $isNewRecord ? 'MT4 account created and synced successfully' : 'MT4 account status updated successfully',
                    'data' => $responseData
                ];
            } else {
                $errors = $mt4Account->getErrors();
                Yii::error('Failed to save MT4 account data: ' . json_encode($errors));
                throw new ServerErrorHttpException('Failed to save MT4 account data: ' . json_encode($errors));
            }
        } catch (\Exception $e) {
            Yii::error('Error in actionSyncAccount: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }

    public function actionTestError()
    {
        TelegramHelper::sendSimpleError("Test send error");
    }

    public function actionTestMessage()
    {
        TelegramHelper::sendSimpleMessage("Test send simple message");
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
                    'disabled_ea' => $record->disabled_ea,
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
                        'wd_date' => $withdraw->wd_date,
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
            $request = Yii::$app->request;
            $license = $request->post('license', $request->get('license'));

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

    /**
     * Sync closed orders from MT4
     * Accepts single order or batch array
     */
    public function actionSyncOrders()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            // Get the raw POST body
            $rawInput = Yii::$app->request->getRawBody();

            // Log for debugging
            Yii::info('Raw input length: ' . strlen($rawInput));
            Yii::info('Raw input first 500 chars: ' . substr($rawInput, 0, 500));

            // Check if we have any data
            if (empty($rawInput)) {
                throw new \yii\web\BadRequestHttpException('No data received');
            }

            // Decode JSON
            $data = json_decode($rawInput, true);

            // Check for JSON errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                Yii::error('JSON decode error: ' . json_last_error_msg());
                Yii::error('Raw input: ' . $rawInput);
                throw new \yii\web\BadRequestHttpException('Invalid JSON data: ' . json_last_error_msg());
            }

            // Check if data is empty after decode
            if (empty($data)) {
                throw new \yii\web\BadRequestHttpException('No data received after JSON decode');
            }

            // Handle batch or single order
            if (isset($data[0]) && is_array($data[0])) {
                // It's a batch (array of orders)
                return $this->handleBatchSync($data);
            } else {
                // It's a single order
                return $this->handleSingleOrderSync($data);
            }
        } catch (\Exception $e) {
            Yii::error('Error in actionSyncOrders: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function handleSingleOrderSync($data)
    {
        // Log received data
        Yii::info('Handling single order: ' . json_encode($data));

        // Validate required fields
        $required = ['account_id', 'ticket', 'symbol', 'type', 'lots', 'open_price'];
        $missing = [];

        foreach ($required as $field) {
            if (!isset($data[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new \yii\web\BadRequestHttpException('Missing required fields: ' . implode(', ', $missing));
        }

        // Check if order already exists
        $order = AccountOrders::find()
            ->where([
                'account_id' => (string)$data['account_id'],
                'ticket' => (int)$data['ticket']
            ])
            ->one();

        $isNew = false;
        $statusChanged = false;
        $previousStatus = null;

        if ($order) {
            // Order exists
            $isNew = false;
            $previousStatus = $order->status;
            Yii::info("Updating existing order: Ticket {$data['ticket']}, Current Status: {$previousStatus}");
        } else {
            // Order doesn't exist - create new
            $order = new AccountOrders();
            $isNew = true;
            Yii::info("Creating new order: Ticket {$data['ticket']}");
        }

        // Populate/Update the order (always update these fields)
        $order->account_id = (string)$data['account_id'];
        $order->ticket = (int)$data['ticket'];
        $order->symbol = $data['symbol'];
        $order->type = (int)$data['type'];
        $order->type_desc = isset($data['type_desc']) ? $data['type_desc'] : AccountOrders::getOrderTypeDescription($data['type']);
        $order->lots = (float)$data['lots'];
        $order->open_price = (float)$data['open_price'];
        $order->open_time = (int)$data['open_time'];
        $order->magic = isset($data['magic']) ? (int)$data['magic'] : 0;
        $order->comment = isset($data['comment']) ? $data['comment'] : null;

        // Set close-related fields (may be 0 or null for open orders)
        $order->close_price = isset($data['close_price']) ? (float)$data['close_price'] : 0;
        $order->profit = isset($data['profit']) ? (float)$data['profit'] : 0;
        $order->swap = isset($data['swap']) ? (float)$data['swap'] : 0;
        $order->commission = isset($data['commission']) ? (float)$data['commission'] : 0;
        $order->close_time = isset($data['close_time']) ? (int)$data['close_time'] : 0;

        // Handle SL and TP if provided
        if (isset($data['stop_loss'])) {
            $order->stop_loss = (float)$data['stop_loss'];
        }
        if (isset($data['take_profit'])) {
            $order->take_profit = (float)$data['take_profit'];
        }

        // CRITICAL FIX: Set status based on event_type
        if (isset($data['event_type'])) {
            // Handle based on event_type from MT4
            $eventType = $data['event_type'];
            Yii::info("Event type detected: {$eventType} for ticket {$data['ticket']}");

            switch ($eventType) {
                case 'ORDER_OPEN':
                    $order->status = AccountOrders::STATUS_OPEN;
                    Yii::info("Setting status to OPEN for ticket {$data['ticket']}");
                    break;
                case 'ORDER_MODIFY':
                    $order->status = AccountOrders::STATUS_MODIFIED;
                    Yii::info("Setting status to MODIFIED for ticket {$data['ticket']}");
                    // Store modification details if provided
                    if (isset($data['changes'])) {
                        $order->modification_details = $data['changes'];
                    }
                    break;
                case 'ORDER_CLOSE':
                    $order->status = AccountOrders::STATUS_CLOSED;
                    Yii::info("Setting status to CLOSED for ticket {$data['ticket']}");
                    break;
                default:
                    // Unknown event type, try to auto-detect
                    Yii::warning("Unknown event type: {$eventType} for ticket {$data['ticket']}, auto-detecting");
                    if (isset($data['close_time']) && $data['close_time'] > 0) {
                        $order->status = AccountOrders::STATUS_CLOSED;
                    } else {
                        $order->status = AccountOrders::STATUS_OPEN;
                    }
            }
        } else {
            // No event_type provided, auto-detect based on data
            Yii::info("No event_type provided for ticket {$data['ticket']}, auto-detecting");

            if (isset($data['close_time']) && $data['close_time'] > 0) {
                $order->status = AccountOrders::STATUS_CLOSED;
                Yii::info("Auto-detected CLOSED status for ticket {$data['ticket']}");
            } elseif (!$isNew && $this->hasOrderChanged($order, $data)) {
                $order->status = AccountOrders::STATUS_MODIFIED;
                Yii::info("Auto-detected MODIFIED status for ticket {$data['ticket']}");
            } else {
                $order->status = AccountOrders::STATUS_OPEN;
                Yii::info("Auto-detected OPEN status for ticket {$data['ticket']}");
            }
        }

        // Ensure status is never empty (fallback)
        if (empty($order->status)) {
            $order->status = AccountOrders::STATUS_OPEN;
            Yii::warning("Status was empty, set to OPEN for ticket {$data['ticket']}");
        }

        $order->synced_at = date('Y-m-d H:i:s');

        // Log final status before save
        Yii::info("Final status for ticket {$data['ticket']}: {$order->status}");

        if ($order->save()) {
            // Log status change
            if ($previousStatus && $previousStatus != $order->status) {
                Yii::info("Status changed: Ticket {$data['ticket']} from {$previousStatus} to {$order->status}");
            }

            return [
                'status' => 'success',
                'message' => $isNew ? 'Order created successfully' : 'Order updated successfully',
                'data' => [
                    'id' => $order->id,
                    'ticket' => $order->ticket,
                    'account_id' => $order->account_id,
                    'status' => $order->status,
                    'is_new' => $isNew,
                    'was_modified' => (!$isNew && $order->status == AccountOrders::STATUS_MODIFIED),
                    'was_closed' => ($order->status == AccountOrders::STATUS_CLOSED)
                ]
            ];
        } else {
            Yii::error('Failed to save order: ' . json_encode($order->getErrors()));
            throw new \yii\web\ServerErrorHttpException('Failed to save order: ' . json_encode($order->getErrors()));
        }
    }

    private function handleBatchSync($orders)
    {
        Yii::info('Handling batch sync. Total orders: ' . count($orders));

        if (empty($orders)) {
            throw new \yii\web\BadRequestHttpException('No orders provided for batch sync');
        }

        $stats = [
            'total' => count($orders),
            'new' => 0,
            'updated' => 0,
            'modified' => 0,
            'closed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        foreach ($orders as $orderData) {
            try {
                // Validate required fields for each order
                $required = ['account_id', 'ticket', 'symbol', 'type', 'lots', 'open_price', 'open_time'];
                $missing = [];

                foreach ($required as $field) {
                    if (!isset($orderData[$field])) {
                        $missing[] = $field;
                    }
                }

                if (!empty($missing)) {
                    $stats['failed']++;
                    $stats['errors'][] = "Order ticket {$orderData['ticket']} missing fields: " . implode(', ', $missing);
                    continue;
                }

                // Check if order exists
                $order = AccountOrders::find()
                    ->where([
                        'account_id' => (string)$orderData['account_id'],
                        'ticket' => (int)$orderData['ticket']
                    ])
                    ->one();

                $isNew = false;
                $previousStatus = null;
                $status = null;

                if ($order) {
                    // Order exists
                    $isNew = false;
                    $previousStatus = $order->status;
                    Yii::info("Updating existing order in batch: Ticket {$orderData['ticket']}, Current Status: {$previousStatus}");
                } else {
                    // Order doesn't exist - create new
                    $order = new AccountOrders();
                    $isNew = true;
                    Yii::info("Creating new order in batch: Ticket {$orderData['ticket']}");
                }

                // CRITICAL FIX: Set status based on event_type (same as single order sync)
                if (isset($orderData['event_type'])) {
                    // Handle based on event_type from MT4
                    $eventType = $orderData['event_type'];
                    Yii::info("Event type detected in batch: {$eventType} for ticket {$orderData['ticket']}");

                    switch ($eventType) {
                        case 'ORDER_OPEN':
                            $status = AccountOrders::STATUS_OPEN;
                            Yii::info("Setting status to OPEN for ticket {$orderData['ticket']}");
                            break;
                        case 'ORDER_MODIFY':
                            $status = AccountOrders::STATUS_MODIFIED;
                            Yii::info("Setting status to MODIFIED for ticket {$orderData['ticket']}");
                            break;
                        case 'ORDER_CLOSE':
                            $status = AccountOrders::STATUS_CLOSED;
                            Yii::info("Setting status to CLOSED for ticket {$orderData['ticket']}");
                            break;
                        default:
                            // Unknown event type, try to auto-detect
                            Yii::warning("Unknown event type: {$eventType} for ticket {$orderData['ticket']}, auto-detecting");
                            if (isset($orderData['close_time']) && $orderData['close_time'] > 0) {
                                $status = AccountOrders::STATUS_CLOSED;
                            } else {
                                $status = AccountOrders::STATUS_OPEN;
                            }
                    }
                } else {
                    // No event_type provided, auto-detect based on data (same logic as single order sync)
                    Yii::info("No event_type provided for ticket {$orderData['ticket']} in batch, auto-detecting");

                    if (isset($orderData['close_time']) && $orderData['close_time'] > 0) {
                        $status = AccountOrders::STATUS_CLOSED;
                        Yii::info("Auto-detected CLOSED status for ticket {$orderData['ticket']}");
                    } elseif (!$isNew && $this->hasOrderChanged($order, $orderData)) {
                        $status = AccountOrders::STATUS_MODIFIED;
                        Yii::info("Auto-detected MODIFIED status for ticket {$orderData['ticket']}");
                    } else {
                        $status = AccountOrders::STATUS_OPEN;
                        Yii::info("Auto-detected OPEN status for ticket {$orderData['ticket']}");
                    }
                }

                // Ensure status is never empty (fallback)
                if (empty($status)) {
                    $status = AccountOrders::STATUS_OPEN;
                    Yii::warning("Status was empty, set to OPEN for ticket {$orderData['ticket']}");
                }

                // Update statistics based on status
                if ($isNew) {
                    $stats['new']++;
                } else {
                    switch ($status) {
                        case AccountOrders::STATUS_MODIFIED:
                            $stats['modified']++;
                            break;
                        case AccountOrders::STATUS_CLOSED:
                            $stats['closed']++;
                            break;
                        default:
                            $stats['updated']++;
                            break;
                    }
                }

                // Populate/Update the order (always update these fields)
                $order->account_id = (string)$orderData['account_id'];
                $order->ticket = (int)$orderData['ticket'];
                $order->symbol = $orderData['symbol'];
                $order->type = (int)$orderData['type'];
                $order->type_desc = isset($orderData['type_desc']) ? $orderData['type_desc'] : AccountOrders::getOrderTypeDescription($orderData['type']);
                $order->lots = (float)$orderData['lots'];
                $order->open_price = (float)$orderData['open_price'];
                $order->open_time = (int)$orderData['open_time'];
                $order->magic = isset($orderData['magic']) ? (int)$orderData['magic'] : 0;
                $order->comment = isset($orderData['comment']) ? $orderData['comment'] : null;

                // Set close-related fields (may be 0 or null for open orders)
                $order->close_price = isset($orderData['close_price']) ? (float)$orderData['close_price'] : 0;
                $order->profit = isset($orderData['profit']) ? (float)$orderData['profit'] : 0;
                $order->swap = isset($orderData['swap']) ? (float)$orderData['swap'] : 0;
                $order->commission = isset($orderData['commission']) ? (float)$orderData['commission'] : 0;
                $order->close_time = isset($orderData['close_time']) ? (int)$orderData['close_time'] : 0;

                // Handle SL and TP if provided
                if (isset($orderData['stop_loss'])) {
                    $order->stop_loss = (float)$orderData['stop_loss'];
                }
                if (isset($orderData['take_profit'])) {
                    $order->take_profit = (float)$orderData['take_profit'];
                }

                // Store modification details if provided
                if (isset($orderData['changes']) && $status == AccountOrders::STATUS_MODIFIED) {
                    $order->modification_details = $orderData['changes'];
                }

                $order->status = $status;
                $order->synced_at = date('Y-m-d H:i:s');

                // Log final status before save
                Yii::info("Final status for ticket {$orderData['ticket']} in batch: {$order->status}");

                if ($order->save()) {
                    // Log status change
                    if ($previousStatus && $previousStatus != $order->status) {
                        Yii::info("Status changed in batch: Ticket {$orderData['ticket']} from {$previousStatus} to {$order->status}");
                    }

                    Yii::info("Order processed in batch: Ticket {$orderData['ticket']}, Action: " . ($isNew ? "NEW" : "UPDATE") . ", Status: {$status}");
                } else {
                    // Revert statistics on save failure
                    if ($isNew) {
                        $stats['new']--;
                    } else {
                        switch ($status) {
                            case AccountOrders::STATUS_MODIFIED:
                                $stats['modified']--;
                                break;
                            case AccountOrders::STATUS_CLOSED:
                                $stats['closed']--;
                                break;
                            default:
                                $stats['updated']--;
                                break;
                        }
                    }
                    $stats['failed']++;
                    $stats['errors'][] = "Failed to save order {$orderData['ticket']}: " . json_encode($order->getErrors());
                }
            } catch (\Exception $e) {
                $stats['failed']++;
                $stats['errors'][] = "Error processing order {$orderData['ticket']}: " . $e->getMessage();
            }
        }

        // Log summary
        Yii::info("Batch sync completed - Total: {$stats['total']}, New: {$stats['new']}, Updated: {$stats['updated']}, Modified: {$stats['modified']}, Closed: {$stats['closed']}, Failed: {$stats['failed']}");

        if (!empty($stats['errors'])) {
            Yii::warning("Batch sync errors: " . json_encode($stats['errors']));
        }

        return [
            'status' => 'success',
            'message' => "Batch sync completed",
            'stats' => $stats
        ];
    }

    /**
     * Helper method to check if order has changed
     */
    private function hasOrderChanged($order, $newData)
    {
        // Check critical fields for changes
        if ($order->lots != (float)($newData['lots'] ?? $order->lots)) return true;
        if ($order->close_price != (float)($newData['close_price'] ?? $order->close_price)) return true;
        if ($order->profit != (float)($newData['profit'] ?? $order->profit)) return true;

        // Check SL/TP if they exist in new data
        if (isset($newData['stop_loss']) && $order->stop_loss != (float)$newData['stop_loss']) return true;
        if (isset($newData['take_profit']) && $order->take_profit != (float)$newData['take_profit']) return true;

        return false;
    }


    /**
     * Get orders for an account
     */
    public function actionGetOrders()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $accountId = Yii::$app->request->get('account_id');
            $limit = Yii::$app->request->get('limit', 100);
            $offset = Yii::$app->request->get('offset', 0);
            $fromDate = Yii::$app->request->get('from_date');
            $toDate = Yii::$app->request->get('to_date');

            if (empty($accountId)) {
                throw new \yii\web\BadRequestHttpException('Account ID is required');
            }

            $query = AccountOrders::find()
                ->where(['account_id' => $accountId])
                ->orderBy(['close_time' => SORT_DESC]);

            // Apply date filters
            if ($fromDate) {
                $query->andWhere(['>=', 'close_time', strtotime($fromDate)]);
            }
            if ($toDate) {
                $query->andWhere(['<=', 'close_time', strtotime($toDate)]);
            }

            $total = $query->count();
            $orders = $query->limit($limit)->offset($offset)->all();

            // Get statistics
            $totalProfit = AccountOrders::getTotalProfitByAccount($accountId);
            $winRate = AccountOrders::getWinRate($accountId);

            return [
                'status' => 'success',
                'data' => [
                    'orders' => $orders,
                    'pagination' => [
                        'total' => $total,
                        'limit' => $limit,
                        'offset' => $offset
                    ],
                    'statistics' => [
                        'total_profit' => $totalProfit,
                        'total_orders' => $winRate['total'],
                        'winning_orders' => $winRate['wins'],
                        'win_rate' => $winRate['win_rate']
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionGetOrders: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get order statistics for dashboard
     */
    public function actionOrderStats()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $accountId = Yii::$app->request->get('account_id');

            if (empty($accountId)) {
                throw new \yii\web\BadRequestHttpException('Account ID is required');
            }

            // Get statistics by symbol
            $symbolStats = AccountOrders::find()
                ->select(['symbol', 'SUM(profit) as total_profit', 'COUNT(*) as total_orders', 'SUM(lots) as total_lots'])
                ->where(['account_id' => $accountId, 'status' => AccountOrders::STATUS_CLOSED])
                ->groupBy('symbol')
                ->asArray()
                ->all();

            // Get monthly profit
            $monthlyProfit = AccountOrders::find()
                ->select(['FROM_UNIXTIME(close_time, "%Y-%m") as month', 'SUM(profit) as total_profit', 'COUNT(*) as order_count'])
                ->where(['account_id' => $accountId, 'status' => AccountOrders::STATUS_CLOSED])
                ->groupBy('month')
                ->orderBy('month DESC')
                ->limit(12)
                ->asArray()
                ->all();

            // Get profit distribution
            $profitDistribution = [
                'profitable' => AccountOrders::find()
                    ->where(['account_id' => $accountId, 'status' => AccountOrders::STATUS_CLOSED])
                    ->andWhere(['>', 'profit', 0])
                    ->count(),
                'loss' => AccountOrders::find()
                    ->where(['account_id' => $accountId, 'status' => AccountOrders::STATUS_CLOSED])
                    ->andWhere(['<', 'profit', 0])
                    ->count(),
                'breakeven' => AccountOrders::find()
                    ->where(['account_id' => $accountId, 'status' => AccountOrders::STATUS_CLOSED])
                    ->andWhere(['profit' => 0])
                    ->count(),
            ];

            return [
                'status' => 'success',
                'data' => [
                    'by_symbol' => $symbolStats,
                    'monthly_profit' => $monthlyProfit,
                    'profit_distribution' => $profitDistribution
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionOrderStats: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
