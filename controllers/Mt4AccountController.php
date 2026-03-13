<?php

namespace app\controllers;

use app\models\Mt4Account;
use app\models\User;
use app\models\Users;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use yii\web\UnauthorizedHttpException;

class Mt4AccountController extends Controller
{
    /**
     * Action to save or update MT4 account data
     * Client sends POST data: account_id, buy_order_count, total_buy_lot, 
     * sell_order_count, total_sell_lot, total_profit, account_balance, 
     * account_equity, floating_value
     */
    public function actionSaveAccount()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            // Get POST parameters
            $account_id = Yii::$app->request->post('account_id');
            $buy_order_count = Yii::$app->request->post('buy_order_count', 0);
            $total_buy_lot = Yii::$app->request->post('total_buy_lot', 0);
            $sell_order_count = Yii::$app->request->post('sell_order_count', 0);
            $total_sell_lot = Yii::$app->request->post('total_sell_lot', 0);
            $total_profit = Yii::$app->request->post('total_profit', 0);
            $account_balance = Yii::$app->request->post('account_balance', 0);
            $account_equity = Yii::$app->request->post('account_equity', 0);
            $floating_value = Yii::$app->request->post('floating_value', 0);
            
            // Optional parameters that might be sent
            $bot_name = Yii::$app->request->post('bot_name');
            $leverage = Yii::$app->request->post('leverage', 100);
            $currency = Yii::$app->request->post('currency', 'USD');
            $server = Yii::$app->request->post('server');
            $broker = Yii::$app->request->post('broker');
            $account_type = Yii::$app->request->post('account_type', 'standard');
            $status = Yii::$app->request->post('status', 'active');

            // Validate required parameters
            if (empty($account_id)) {
                throw new BadRequestHttpException('Account ID is required');
            }

            // Get user from auth token or session
            $user = $this->getCurrentUser();
            if (!$user) {
                throw new UnauthorizedHttpException('User not authenticated');
            }

            // Check if account already exists for this user
            $mt4Account = Mt4Account::find()
                ->where([
                    'user_id' => $user->id,
                    'account_id' => $account_id
                ])
                ->one();

            $isNewRecord = false;
            if (!$mt4Account) {
                // Create new record
                $mt4Account = new Mt4Account();
                $mt4Account->user_id = $user->id;
                $mt4Account->account_id = $account_id;
                $mt4Account->created_at = date('Y-m-d H:i:s');
                $mt4Account->created_by = $user->id;
                $isNewRecord = true;
            }

            // Always update these fields
            $mt4Account->buy_order_count = (int)$buy_order_count;
            $mt4Account->total_buy_lot = (float)$total_buy_lot;
            $mt4Account->sell_order_count = (int)$sell_order_count;
            $mt4Account->total_sell_lot = (float)$total_sell_lot;
            $mt4Account->account_balance = (float)$account_balance;
            $mt4Account->account_equity = (float)$account_equity;
            $mt4Account->last_sync = date('Y-m-d H:i:s');
            
            // Update total_profit and floating_value
            $mt4Account->total_profit = (float)$total_profit;
            $mt4Account->floating_value = (float)$floating_value;
            
            // Calculate profit percentage based on balance
            if ($mt4Account->account_balance > 0) {
                $mt4Account->total_profit_percentage = round(
                    ($mt4Account->total_profit / $mt4Account->account_balance) * 100, 
                    2
                );
            } else {
                $mt4Account->total_profit_percentage = 0;
            }

            // Update optional fields if provided
            if ($bot_name !== null) {
                $mt4Account->bot_name = $bot_name;
            }
            
            if ($leverage !== null) {
                $mt4Account->leverage = (int)$leverage;
            }
            
            if ($currency !== null) {
                $mt4Account->currency = $currency;
            }
            
            if ($server !== null) {
                $mt4Account->server = $server;
            }
            
            if ($broker !== null) {
                $mt4Account->broker = $broker;
            }
            
            if ($account_type !== null) {
                $mt4Account->account_type = $account_type;
            }
            
            if ($status !== null) {
                $mt4Account->status = $status;
            }

            // Update last_connected if status is active
            if ($status === 'active') {
                $mt4Account->last_connected = date('Y-m-d H:i:s');
            }

            // Modified by and timestamp
            $mt4Account->modified_by = $user->id;
            $mt4Account->modified_at = date('Y-m-d H:i:s');

            // Save the record
            if ($mt4Account->save()) {
                return [
                    'status' => 'success',
                    'message' => $isNewRecord ? 'Account created successfully' : 'Account updated successfully',
                    'data' => [
                        'id' => $mt4Account->id,
                        'account_id' => $mt4Account->account_id,
                        'user_id' => $mt4Account->user_id,
                        'bot_name' => $mt4Account->bot_name,
                        'buy_order_count' => $mt4Account->buy_order_count,
                        'total_buy_lot' => $mt4Account->total_buy_lot,
                        'sell_order_count' => $mt4Account->sell_order_count,
                        'total_sell_lot' => $mt4Account->total_sell_lot,
                        'total_profit' => $mt4Account->total_profit,
                        'total_profit_percentage' => $mt4Account->total_profit_percentage,
                        'account_balance' => $mt4Account->account_balance,
                        'account_equity' => $mt4Account->account_equity,
                        'floating_value' => $mt4Account->floating_value,
                        'status' => $mt4Account->status,
                        'last_sync' => $mt4Account->last_sync,
                        'is_new' => $isNewRecord
                    ]
                ];
            } else {
                Yii::error('Failed to save MT4 account: ' . json_encode($mt4Account->errors));
                throw new ServerErrorHttpException('Failed to save account data: ' . json_encode($mt4Account->errors));
            }

        } catch (\Exception $e) {
            Yii::error('Error in actionSaveAccount: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Alternative version with license authentication (similar to your example)
     */
    public function actionSaveAccountByLicense()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            // Get POST parameters including license
            $license = Yii::$app->request->post('license');
            $account_id = Yii::$app->request->post('account_id');
            $buy_order_count = Yii::$app->request->post('buy_order_count', 0);
            $total_buy_lot = Yii::$app->request->post('total_buy_lot', 0);
            $sell_order_count = Yii::$app->request->post('sell_order_count', 0);
            $total_sell_lot = Yii::$app->request->post('total_sell_lot', 0);
            $total_profit = Yii::$app->request->post('total_profit', 0);
            $account_balance = Yii::$app->request->post('account_balance', 0);
            $account_equity = Yii::$app->request->post('account_equity', 0);
            $floating_value = Yii::$app->request->post('floating_value', 0);
            
            // Optional parameters
            $bot_name = Yii::$app->request->post('bot_name');
            $server = Yii::$app->request->post('server');
            $broker = Yii::$app->request->post('broker');

            // Validate required parameters
            if (empty($license) || empty($account_id)) {
                throw new BadRequestHttpException('License and account_id are required');
            }

            // Find user by license
            $user = Users::findOne(['user_license' => $license]);
            if (!$user) {
                throw new NotFoundHttpException('User not found for the provided license');
            }

            // Check if account already exists for this user
            $mt4Account = Mt4Account::find()
                ->where([
                    'user_id' => $user->id,
                    'account_id' => $account_id
                ])
                ->one();

            $isNewRecord = false;
            if (!$mt4Account) {
                // Create new record
                $mt4Account = new Mt4Account();
                $mt4Account->user_id = $user->id;
                $mt4Account->account_id = $account_id;
                $mt4Account->created_by = $user->id;
                $isNewRecord = true;
            }

            // Update metrics
            $mt4Account->buy_order_count = (int)$buy_order_count;
            $mt4Account->total_buy_lot = (float)$total_buy_lot;
            $mt4Account->sell_order_count = (int)$sell_order_count;
            $mt4Account->total_sell_lot = (float)$total_sell_lot;
            $mt4Account->total_profit = (float)$total_profit;
            $mt4Account->account_balance = (float)$account_balance;
            $mt4Account->account_equity = (float)$account_equity;
            $mt4Account->floating_value = (float)$floating_value;
            
            // Calculate profit percentage
            if ($mt4Account->account_balance > 0) {
                $mt4Account->total_profit_percentage = round(
                    ($mt4Account->total_profit / $mt4Account->account_balance) * 100, 
                    2
                );
            }

            // Update optional fields
            if ($bot_name) {
                $mt4Account->bot_name = $bot_name;
            }
            if ($server) {
                $mt4Account->server = $server;
            }
            if ($broker) {
                $mt4Account->broker = $broker;
            }

            // Update sync timestamps
            $mt4Account->last_sync = date('Y-m-d H:i:s');
            $mt4Account->modified_by = $user->id;
            $mt4Account->status = Mt4Account::STATUS_ACTIVE;
            $mt4Account->last_connected = date('Y-m-d H:i:s');

            if ($mt4Account->save()) {
                return [
                    'status' => 'success',
                    'message' => $isNewRecord ? 'Account created successfully' : 'Account updated successfully',
                    'data' => [
                        'id' => $mt4Account->id,
                        'account_id' => $mt4Account->account_id,
                        'bot_name' => $mt4Account->bot_name,
                        'buy_order_count' => $mt4Account->buy_order_count,
                        'total_buy_lot' => $mt4Account->total_buy_lot,
                        'sell_order_count' => $mt4Account->sell_order_count,
                        'total_sell_lot' => $mt4Account->total_sell_lot,
                        'total_profit' => $mt4Account->total_profit,
                        'profit_percentage' => $mt4Account->total_profit_percentage,
                        'account_balance' => $mt4Account->account_balance,
                        'account_equity' => $mt4Account->account_equity,
                        'floating_value' => $mt4Account->floating_value,
                        'last_sync' => $mt4Account->last_sync
                    ]
                ];
            } else {
                Yii::error('Failed to save MT4 account: ' . json_encode($mt4Account->errors));
                throw new ServerErrorHttpException('Failed to save account data');
            }

        } catch (\Exception $e) {
            Yii::error('Error in actionSaveAccountByLicense: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get current authenticated user
     * Implement based on your authentication method
     */
    private function getCurrentUser()
    {
        // Option 1: Get from session
        if (!Yii::$app->user->isGuest) {
            return Yii::$app->user->identity;
        }
        
        // Option 2: Get from auth token (Bearer token)
        $authHeader = Yii::$app->request->headers->get('Authorization');
        if ($authHeader && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            $token = $matches[1];
            // Find user by token - implement based on your auth system
            // return User::findIdentityByAccessToken($token);
        }
        
        // Option 3: Get from API key
        $apiKey = Yii::$app->request->headers->get('X-API-Key');
        if ($apiKey) {
            // Find user by API key
            return Users::findOne(['api_key' => $apiKey]);
        }
        
        return null;
    }

    /**
     * Batch save multiple accounts at once
     */
    public function actionBatchSaveAccounts()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $accounts = Yii::$app->request->post('accounts', []);
            
            if (empty($accounts)) {
                throw new BadRequestHttpException('Accounts data is required');
            }

            $user = $this->getCurrentUser();
            if (!$user) {
                throw new UnauthorizedHttpException('User not authenticated');
            }

            $results = [];
            $errors = [];

            foreach ($accounts as $accountData) {
                try {
                    // Extract data for each account
                    $account_id = $accountData['account_id'] ?? null;
                    
                    if (!$account_id) {
                        $errors[] = ['error' => 'Account ID missing', 'data' => $accountData];
                        continue;
                    }

                    // Find or create account
                    $mt4Account = Mt4Account::find()
                        ->where(['user_id' => $user->id, 'account_id' => $account_id])
                        ->one();

                    $isNew = false;
                    if (!$mt4Account) {
                        $mt4Account = new Mt4Account();
                        $mt4Account->user_id = $user->id;
                        $mt4Account->account_id = $account_id;
                        $mt4Account->created_by = $user->id;
                        $isNew = true;
                    }

                    // Update fields
                    $mt4Account->buy_order_count = (int)($accountData['buy_order_count'] ?? 0);
                    $mt4Account->total_buy_lot = (float)($accountData['total_buy_lot'] ?? 0);
                    $mt4Account->sell_order_count = (int)($accountData['sell_order_count'] ?? 0);
                    $mt4Account->total_sell_lot = (float)($accountData['total_sell_lot'] ?? 0);
                    $mt4Account->total_profit = (float)($accountData['total_profit'] ?? 0);
                    $mt4Account->account_balance = (float)($accountData['account_balance'] ?? 0);
                    $mt4Account->account_equity = (float)($accountData['account_equity'] ?? 0);
                    $mt4Account->floating_value = (float)($accountData['floating_value'] ?? 0);
                    
                    // Optional fields
                    if (isset($accountData['bot_name'])) {
                        $mt4Account->bot_name = $accountData['bot_name'];
                    }
                    if (isset($accountData['server'])) {
                        $mt4Account->server = $accountData['server'];
                    }
                    if (isset($accountData['broker'])) {
                        $mt4Account->broker = $accountData['broker'];
                    }

                    // Calculate profit percentage
                    if ($mt4Account->account_balance > 0) {
                        $mt4Account->total_profit_percentage = round(
                            ($mt4Account->total_profit / $mt4Account->account_balance) * 100, 
                            2
                        );
                    }

                    $mt4Account->last_sync = date('Y-m-d H:i:s');
                    $mt4Account->modified_by = $user->id;
                    $mt4Account->status = Mt4Account::STATUS_ACTIVE;
                    $mt4Account->last_connected = date('Y-m-d H:i:s');

                    if ($mt4Account->save()) {
                        $results[] = [
                            'account_id' => $account_id,
                            'status' => 'success',
                            'is_new' => $isNew
                        ];
                    } else {
                        $errors[] = [
                            'account_id' => $account_id,
                            'errors' => $mt4Account->errors
                        ];
                    }

                } catch (\Exception $e) {
                    $errors[] = [
                        'account_id' => $accountData['account_id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            return [
                'status' => empty($errors) ? 'success' : 'partial',
                'message' => count($results) . ' accounts processed, ' . count($errors) . ' errors',
                'data' => [
                    'successful' => $results,
                    'errors' => $errors
                ]
            ];

        } catch (\Exception $e) {
            Yii::error('Error in actionBatchSaveAccounts: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}