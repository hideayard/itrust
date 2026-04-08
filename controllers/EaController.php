<?php

namespace app\controllers;

use app\helpers\TelegramHelper;
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
            $timestamp = Yii::$app->request->post('timestamp', time());

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

            // Find existing account or create new one
            // Search by user_id, account_id (assuming account_id is unique per user)
            $mt4Account = Mt4Account::find()
                ->where([
                    'user_id' => $user->id,
                    'account_id' => (string)$accountId
                ])
                ->one();

            $isNewRecord = false;
            if (!$mt4Account) {
                // Create new account record
                $mt4Account = new Mt4Account();
                $mt4Account->user_id = $user->id;
                $mt4Account->account_id = (string)$accountId;
                $mt4Account->status = Mt4Account::STATUS_ACTIVE;
                $mt4Account->currency = 'USD'; // Default currency
                $mt4Account->leverage = 100; // Default leverage
                $mt4Account->account_type = Mt4Account::ACCOUNT_TYPE_STANDARD;
                $isNewRecord = true;

                Yii::info("Creating new MT4 account record for user {$user->id}, account {$accountId}");
            }

            // Store previous values for comparison
            $previousBalance = $mt4Account->account_balance;
            $previousEquity = $mt4Account->account_equity;

            // Update account metrics
            $mt4Account->buy_order_count = (int)$buyOrderCount;
            $mt4Account->total_buy_lot = (float)$totalBuyLot;
            $mt4Account->sell_order_count = (int)$sellOrderCount;
            $mt4Account->total_sell_lot = (float)$totalSellLot;
            $mt4Account->total_profit = (float)$totalProfit;
            $mt4Account->total_profit_percentage = (float)$totalProfitPercentage;
            $mt4Account->account_balance = (float)$accountBalance;
            $mt4Account->account_equity = (float)$accountEquity;
            $mt4Account->floating_value = (float)$floatingValue;

            // Update timestamps
            $mt4Account->last_sync = date('Y-m-d H:i:s', $timestamp);

            // If it's a new record, set last_connected
            if ($isNewRecord) {
                $mt4Account->last_connected = date('Y-m-d H:i:s', $timestamp);
            }

            // Update status if equity > 0 (account is active)
            if ($accountEquity > 0) {
                $mt4Account->status = Mt4Account::STATUS_ACTIVE;
            } elseif ($accountEquity <= 0 && $mt4Account->status == Mt4Account::STATUS_ACTIVE) {
                // Check if account might be disconnected
                $mt4Account->status = Mt4Account::STATUS_DISCONNECTED;
            }

            // Save the record
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

                // Log successful sync
                Yii::info("MT4 account synced successfully: user_id={$user->id}, account_id={$accountId}, is_new={$isNewRecord}");

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
            TelegramHelper::sendSimpleError('Error in actionSyncAccount: ' . $e->getMessage() . "\n" . $e->getTraceAsString()
            );
            // TelegramHelper::sendSimpleMessage(
            //         [
            //             'text' => $summary,
            //             'parse_mode' => 'html'
            //         ],
            //         Yii::$app->params['group_id']
            //     );
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getCode()
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
}
