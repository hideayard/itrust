<?php

namespace app\controllers;

use app\helpers\JwtHelper;
use app\models\Mt4Account;
use app\models\Users;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use yii\web\UnauthorizedHttpException;

class Mt4AccountController extends Controller
{
    public $enableCsrfValidation = false;

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
            // return Users::findIdentityByAccessToken($token);
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

    /**
     * Action to get accounts by user_id or search by path containing text
     * GET parameters: 
     *   - user_id (optional) - filter by user ID
     *   - path (optional) - search text in path field (partial match)
     *   - search (optional) - search across multiple fields
     *   - status (optional) - filter by status
     *   - account_type (optional) - filter by account type
     */
    public function actionGetAccounts()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            // Get query parameters
            $userId = Yii::$app->request->get('user_id');
            $pathSearch = Yii::$app->request->get('path');
            $searchText = Yii::$app->request->get('search');
            $status = Yii::$app->request->get('status');
            $accountType = Yii::$app->request->get('account_type');

            // Pagination parameters
            $page = (int)Yii::$app->request->get('page', 1);
            $limit = (int)Yii::$app->request->get('limit', 20);
            $sortBy = Yii::$app->request->get('sort_by', 'created_at');
            $sortOrder = Yii::$app->request->get('sort_order', 'DESC');

            // Validate sort order
            $sortOrder = in_array(strtoupper($sortOrder), ['ASC', 'DESC']) ? strtoupper($sortOrder) : 'DESC';

            // Build query
            $query = Mt4Account::find();

            // Filter by user_id if provided
            if ($userId !== null) {
                // Check if user exists
                $user = Users::findOne($userId);
                if (!$user) {
                    throw new NotFoundHttpException('User not found');
                }
                $query->andWhere(['user_id' => $userId]);
            }

            // Search in path field (partial match)
            if (!empty($pathSearch)) {
                $query->andWhere(['like', 'path', $pathSearch]);
            }

            // General search across multiple fields
            if (!empty($searchText)) {
                $query->andWhere([
                    'or',
                    ['like', 'account_id', $searchText],
                    ['like', 'bot_name', $searchText],
                    ['like', 'server', $searchText],
                    ['like', 'broker', $searchText],
                    ['like', 'path', $searchText],
                    ['like', 'remark', $searchText],
                ]);
            }

            // Filter by status
            if (!empty($status)) {
                $statuses = is_array($status) ? $status : explode(',', $status);
                $query->andWhere(['in', 'status', $statuses]);
            }

            // Filter by account type
            if (!empty($accountType)) {
                $types = is_array($accountType) ? $accountType : explode(',', $accountType);
                $query->andWhere(['in', 'account_type', $types]);
            }

            // Get total count before pagination
            $totalCount = $query->count();

            // Apply sorting
            $query->orderBy([$sortBy => $sortOrder === 'ASC' ? SORT_ASC : SORT_DESC]);

            // Apply pagination
            $offset = ($page - 1) * $limit;
            $query->offset($offset)->limit($limit);

            // Get results
            $accounts = $query->all();

            // Format response data
            $accountData = [];
            foreach ($accounts as $account) {
                $accountData[] = $this->formatAccountData($account);
            }

            return [
                'status' => 'success',
                'data' => [
                    'accounts' => $accountData,
                    'pagination' => [
                        'total' => (int)$totalCount,
                        'page' => $page,
                        'limit' => $limit,
                        'total_pages' => ceil($totalCount / $limit),
                    ],
                    'filters' => [
                        'user_id' => $userId,
                        'path_search' => $pathSearch,
                        'search_text' => $searchText,
                        'status' => $status,
                        'account_type' => $accountType,
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionGetAccounts: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Action to get a single account by ID
     */
    public function actionGetAccount($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $account = Mt4Account::findOne($id);

            if (!$account) {
                throw new NotFoundHttpException('Account not found');
            }

            return [
                'status' => 'success',
                'data' => $this->formatAccountData($account)
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionGetAccount: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Action to get accounts by user with license (like your example)
     */
    public function actionGetAccountsByLicense()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $license = Yii::$app->request->get('license');
            $pathSearch = Yii::$app->request->get('path');
            $searchText = Yii::$app->request->get('search');

            if (empty($license)) {
                throw new BadRequestHttpException('License is required');
            }

            // Find user by license
            $user = Users::findOne(['user_license' => $license]);
            if (!$user) {
                throw new NotFoundHttpException('User not found for the provided license');
            }

            // Build query
            $query = Mt4Account::find()->where(['user_id' => $user->id]);

            // Search in path field
            if (!empty($pathSearch)) {
                $query->andWhere(['like', 'path', $pathSearch]);
            }

            // General search
            if (!empty($searchText)) {
                $query->andWhere([
                    'or',
                    ['like', 'account_id', $searchText],
                    ['like', 'bot_name', $searchText],
                    ['like', 'server', $searchText],
                    ['like', 'broker', $searchText],
                ]);
            }

            $accounts = $query->all();

            $accountData = [];
            foreach ($accounts as $account) {
                $accountData[] = $this->formatAccountData($account);
            }

            return [
                'status' => 'success',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                    ],
                    'accounts' => $accountData,
                    'total' => count($accountData)
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionGetAccountsByLicense: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Action to search accounts by path (advanced)
     */
    public function actionSearchByPath()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $pathQuery = Yii::$app->request->get('q');
            $exactMatch = Yii::$app->request->get('exact', false);
            $userId = Yii::$app->request->get('user_id');

            if (empty($pathQuery)) {
                throw new BadRequestHttpException('Search query is required');
            }

            $query = Mt4Account::find();

            // Filter by user if provided
            if ($userId) {
                $query->andWhere(['user_id' => $userId]);
            }

            // Path search
            if ($exactMatch) {
                $query->andWhere(['path' => $pathQuery]);
            } else {
                $query->andWhere(['like', 'path', $pathQuery]);
            }

            $accounts = $query->all();

            // Group results by path similarity
            $results = [];
            foreach ($accounts as $account) {
                $path = $account->path;
                if (!isset($results[$path])) {
                    $results[$path] = [
                        'path' => $path,
                        'count' => 0,
                        'accounts' => []
                    ];
                }
                $results[$path]['count']++;
                $results[$path]['accounts'][] = $this->formatAccountData($account);
            }

            return [
                'status' => 'success',
                'data' => [
                    'query' => $pathQuery,
                    'exact_match' => $exactMatch,
                    'total_results' => count($accounts),
                    'unique_paths' => count($results),
                    'results' => array_values($results)
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionSearchByPath: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Action to get accounts summary by user
     */
    public function actionGetAccountsSummary($userId = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $query = Mt4Account::find();

            if ($userId) {
                $query->where(['user_id' => $userId]);
            }

            $accounts = $query->all();

            $summary = [
                'total_accounts' => count($accounts),
                'total_balance' => 0,
                'total_equity' => 0,
                'total_profit' => 0,
                'total_floating' => 0,
                'by_status' => [],
                'by_type' => [],
                'profitable_accounts' => 0,
                'losing_accounts' => 0,
            ];

            foreach ($accounts as $account) {
                // Sum totals
                $summary['total_balance'] += $account->account_balance;
                $summary['total_equity'] += $account->account_equity;
                $summary['total_profit'] += $account->total_profit;
                $summary['total_floating'] += $account->floating_value;

                // Count by status
                if (!isset($summary['by_status'][$account->status])) {
                    $summary['by_status'][$account->status] = 0;
                }
                $summary['by_status'][$account->status]++;

                // Count by type
                if (!isset($summary['by_type'][$account->account_type])) {
                    $summary['by_type'][$account->account_type] = 0;
                }
                $summary['by_type'][$account->account_type]++;

                // Count profitable/losing
                if ($account->total_profit > 0) {
                    $summary['profitable_accounts']++;
                } elseif ($account->total_profit < 0) {
                    $summary['losing_accounts']++;
                }
            }

            // Calculate averages
            if ($summary['total_accounts'] > 0) {
                $summary['avg_balance'] = $summary['total_balance'] / $summary['total_accounts'];
                $summary['avg_profit'] = $summary['total_profit'] / $summary['total_accounts'];
            } else {
                $summary['avg_balance'] = 0;
                $summary['avg_profit'] = 0;
            }

            return [
                'status' => 'success',
                'data' => $summary
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionGetAccountsSummary: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Format account data for consistent response
     */
    private function formatAccountData($account)
    {
        // Parse path to get hierarchy info
        $pathIds = $account->path ? explode('.', $account->path) : [];
        $parentPath = count($pathIds) > 1 ? implode('.', array_slice($pathIds, 0, -1)) : null;

        return [
            'id' => $account->id,
            'user_id' => $account->user_id,
            'account_id' => $account->account_id,
            'bot_name' => $account->bot_name,
            'buy_order_count' => (int)$account->buy_order_count,
            'total_buy_lot' => (float)$account->total_buy_lot,
            'sell_order_count' => (int)$account->sell_order_count,
            'total_sell_lot' => (float)$account->total_sell_lot,
            'total_profit' => (float)$account->total_profit,
            'total_profit_percentage' => (float)$account->total_profit_percentage,
            'account_balance' => (float)$account->account_balance,
            'account_equity' => (float)$account->account_equity,
            'floating_value' => (float)$account->floating_value,
            'leverage' => (int)$account->leverage,
            'currency' => $account->currency,
            'server' => $account->server,
            'broker' => $account->broker,
            'account_type' => $account->account_type,
            'path' => $account->path,
            'status' => $account->status,
            'remark' => $account->remark,
            'last_connected' => $account->last_connected,
            'last_sync' => $account->last_sync,
            'created_at' => $account->created_at,
            'modified_at' => $account->modified_at,

            // Computed fields
            'total_orders' => $account->getTotalOrders(),
            'total_lots' => $account->getTotalLots(),
            'win_rate' => $account->getWinRate(),
            'is_profitable' => $account->isProfitable(),
            'is_connected' => $account->isConnected(),
            'last_connected_formatted' => $account->getLastConnectedFormatted(),

            // Path hierarchy info
            'hierarchy' => [
                'path_ids' => $pathIds,
                'parent_path' => $parentPath,
                'parent_user_id' => $pathIds[count($pathIds) - 2] ?? null,
                'root_user_id' => $pathIds[0] ?? null,
                'depth' => count($pathIds),
            ],

            // Formatted values for display
            'formatted' => [
                'balance' => Yii::$app->formatter->asCurrency($account->account_balance),
                'equity' => Yii::$app->formatter->asCurrency($account->account_equity),
                'profit' => $account->getFormattedProfit(),
                'floating' => $account->getFormattedFloating(),
                'profit_percentage' => $account->getProfitPercentageFormatted(),
                'status_badge' => $account->getStatusBadge(),
                'type_badge' => $account->getAccountTypeBadge(),
            ]
        ];
    }

    public function actionGetAccountsByPath()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $path = Yii::$app->request->get('path');
            $includeChildren = Yii::$app->request->get('include_children', false);
            $targetLevel = Yii::$app->request->get('level');
            $searchText = Yii::$app->request->get('search');

            if (empty($path)) {
                throw new BadRequestHttpException('Path parameter is required');
            }

            // Parse the path into user IDs
            $pathIds = explode('.', $path);

            // Validate that all parts are numeric
            foreach ($pathIds as $id) {
                if (!is_numeric($id)) {
                    throw new BadRequestHttpException('Path must contain only numeric values separated by dots');
                }
            }

            // Get the last ID in the path (the target user)
            $targetUserId = end($pathIds);

            // Build the path patterns for searching
            $pathPatterns = $this->buildPathPatterns($pathIds, $includeChildren, $targetLevel);

            // Build query
            $query = Mt4Account::find();

            // Apply path filters
            if (empty($pathPatterns)) {
                // If no patterns, just find accounts for the target user
                $query->andWhere(['user_id' => $targetUserId]);
            } else {
                // Apply all path patterns
                $conditions = ['or'];
                foreach ($pathPatterns as $pattern) {
                    $conditions[] = ['like', 'path', $pattern, false];
                }
                $query->andWhere($conditions);
            }

            // Apply search filter if provided
            if (!empty($searchText)) {
                $query->andWhere([
                    'or',
                    ['like', 'account_id', $searchText],
                    ['like', 'bot_name', $searchText],
                    ['like', 'server', $searchText],
                    ['like', 'broker', $searchText],
                ]);
            }

            // Get results
            $accounts = $query->all();

            // Organize accounts by hierarchy
            $hierarchicalData = $this->buildHierarchicalData($accounts, $pathIds);

            return [
                'status' => 'success',
                'data' => [
                    'request' => [
                        'path' => $path,
                        'parsed_ids' => $pathIds,
                        'target_user_id' => (int)$targetUserId,
                        'include_children' => $includeChildren,
                        'level' => $targetLevel,
                    ],
                    'summary' => [
                        'total_accounts' => count($accounts),
                        'direct_accounts' => count($hierarchicalData['direct']),
                        'child_accounts' => count($hierarchicalData['children']),
                        'child_users' => $hierarchicalData['child_users'],
                    ],
                    'accounts' => [
                        'direct' => $this->formatAccountsArray($hierarchicalData['direct']),
                        'children' => $this->formatAccountsArray($hierarchicalData['children']),
                    ],
                    'hierarchy' => $hierarchicalData['tree'],
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionGetAccountsByPath: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Action to get accounts by path with license (like your example)
     */
    public function actionGetAccountsByPathLicense()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $license = Yii::$app->request->get('license');
            $path = Yii::$app->request->get('path');
            $includeChildren = Yii::$app->request->get('include_children', false);

            if (empty($license)) {
                throw new BadRequestHttpException('License is required');
            }

            if (empty($path)) {
                throw new BadRequestHttpException('Path is required');
            }

            // Find user by license
            $user = Users::findOne(['user_license' => $license]);
            if (!$user) {
                throw new NotFoundHttpException('User not found for the provided license');
            }

            // Parse the path
            $pathIds = explode('.', $path);
            $targetUserId = end($pathIds);

            // Verify that the user has access to this path
            // The user's ID should be in the path somewhere
            if (!in_array($user->id, $pathIds)) {
                throw new UnauthorizedHttpException('You do not have access to this path');
            }

            // Build path patterns
            $pathPatterns = $this->buildPathPatterns($pathIds, $includeChildren);

            // Build query
            $query = Mt4Account::find();

            if (empty($pathPatterns)) {
                $query->andWhere(['user_id' => $targetUserId]);
            } else {
                $conditions = ['or'];
                foreach ($pathPatterns as $pattern) {
                    $conditions[] = ['like', 'path', $pattern, false];
                }
                $query->andWhere($conditions);
            }

            $accounts = $query->all();

            return [
                'status' => 'success',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                    ],
                    'path_info' => [
                        'requested_path' => $path,
                        'parsed_ids' => $pathIds,
                        'target_user' => (int)$targetUserId,
                    ],
                    'accounts' => $this->formatAccountsArray($accounts),
                    'total' => count($accounts)
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionGetAccountsByPathLicense: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Action to get hierarchy tree for a path
     */
    public function actionGetPathHierarchy()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $path = Yii::$app->request->get('path');

            if (empty($path)) {
                throw new BadRequestHttpException('Path is required');
            }

            $pathIds = explode('.', $path);

            // Get all accounts that belong to any user in this path
            $accounts = Mt4Account::find()
                ->where(['in', 'user_id', $pathIds])
                ->all();

            // Build hierarchy tree
            $tree = [];
            foreach ($pathIds as $index => $userId) {
                $level = $index + 1;
                $userAccounts = array_filter($accounts, function ($account) use ($userId) {
                    return $account->user_id == $userId;
                });

                $tree[] = [
                    'level' => $level,
                    'user_id' => (int)$userId,
                    'path_segment' => implode('.', array_slice($pathIds, 0, $level)),
                    'account_count' => count($userAccounts),
                    'accounts' => $this->formatAccountsArray($userAccounts),
                    'children' => $index < count($pathIds) - 1 ? [] : null,
                ];
            }

            return [
                'status' => 'success',
                'data' => [
                    'path' => $path,
                    'levels' => count($pathIds),
                    'hierarchy' => $tree
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionGetPathHierarchy: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Build path patterns for searching
     */
    private function buildPathPatterns($pathIds, $includeChildren, $targetLevel = null)
    {
        $patterns = [];

        if ($targetLevel !== null) {
            // Query specific level
            $level = (int)$targetLevel;
            if ($level > 0 && $level <= count($pathIds)) {
                $targetPath = implode('.', array_slice($pathIds, 0, $level));
                $patterns[] = $targetPath . '.';
                $patterns[] = $targetPath; // Exact match
            }
        } else {
            // Get the target user path
            $targetPath = implode('.', $pathIds);

            if ($includeChildren) {
                // Include all child paths
                $patterns[] = $targetPath . '.%'; // All children
                $patterns[] = $targetPath; // Exact match
            } else {
                // Only exact matches for this path
                $patterns[] = $targetPath;

                // Also match paths that end with this exact sequence
                // This helps find accounts where this user is a parent
                $patterns[] = '%.' . $targetPath;
                $patterns[] = '%.' . $targetPath . '.%';
            }
        }

        return $patterns;
    }

    /**
     * Build hierarchical data structure
     */
    private function buildHierarchicalData($accounts, $pathIds)
    {
        $targetPath = implode('.', $pathIds);
        $targetUserId = end($pathIds);

        $direct = [];
        $children = [];
        $childUsers = [];
        $tree = [];

        foreach ($accounts as $account) {
            $accountPath = $account->path;
            $accountPathIds = $accountPath ? explode('.', $accountPath) : [];
            $accountUserId = end($accountPathIds) ?: $account->user_id;

            if ($accountPath === $targetPath || $account->user_id == $targetUserId) {
                // Direct accounts for this user
                $direct[] = $account;
            } else if (strpos($accountPath, $targetPath . '.') === 0) {
                // Child accounts
                $children[] = $account;

                // Track unique child users
                if (!in_array($accountUserId, $childUsers)) {
                    $childUsers[] = $accountUserId;
                }

                // Build tree structure
                $this->addToTree($tree, explode('.', $accountPath), $account);
            }
        }

        return [
            'direct' => $direct,
            'children' => $children,
            'child_users' => $childUsers,
            'tree' => $tree,
        ];
    }

    /**
     * Add account to hierarchical tree
     */
    private function addToTree(&$tree, $pathIds, $account, $level = 0)
    {
        if ($level >= count($pathIds)) {
            return;
        }

        $currentId = $pathIds[$level];
        $currentPath = implode('.', array_slice($pathIds, 0, $level + 1));

        // Find or create node
        $found = false;
        foreach ($tree as &$node) {
            if ($node['user_id'] == $currentId) {
                $found = true;
                if ($level == count($pathIds) - 1) {
                    // This is the account owner
                    if (!isset($node['accounts'])) {
                        $node['accounts'] = [];
                    }
                    $node['accounts'][] = $this->formatAccountData($account);
                } else {
                    // Recurse to children
                    if (!isset($node['children'])) {
                        $node['children'] = [];
                    }
                    $this->addToTree($node['children'], $pathIds, $account, $level + 1);
                }
                break;
            }
        }

        if (!$found) {
            $newNode = [
                'user_id' => (int)$currentId,
                'path' => $currentPath,
                'level' => $level + 1,
            ];

            if ($level == count($pathIds) - 1) {
                $newNode['accounts'] = [$this->formatAccountData($account)];
                $newNode['children'] = [];
            } else {
                $newNode['accounts'] = [];
                $newNode['children'] = [];
                $this->addToTree($newNode['children'], $pathIds, $account, $level + 1);
            }

            $tree[] = $newNode;
        }
    }

    public function actionGetAccountsByUser($user_id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            // Get and validate JWT token
            $token = $this->getTokenFromRequest();

            if (!$token) {
                throw new UnauthorizedHttpException('No authorization token provided');
            }

            // Get secret key from params
            $secret = \Yii::$app->params['jwtSecret'] ?? 'your-default-secret-key';

            // Validate token
            $payload = JwtHelper::validate($token, $secret);

            if (!$payload) {
                throw new UnauthorizedHttpException('Invalid or expired token');
            }

            // Extract current user ID from payload
            $currentUserId = $this->extractUserIdFromPayload($payload);
            $currentUser = Users::findOne($currentUserId);

            if (!$currentUser) {
                throw new UnauthorizedHttpException('User not found');
            }

            // If no user_id provided, get current user's accounts
            if ($user_id === null) {
                $user_id = $currentUser->id;
            }

            // Check if target user exists
            $targetUser = Users::findOne($user_id);
            if (!$targetUser) {
                throw new NotFoundHttpException('User not found');
            }

            // Check access rights
            $canAccess = false;
            $accessibleUserIds = [];

            if ($currentUser->user_tipe == 'ADMIN') {
                // Admin can access all accounts
                $canAccess = true;
            } else {
                // Check if current user can access target user based on path hierarchy
                $currentUserAccount = Mt4Account::find()
                    ->where(['user_id' => $currentUser->id])
                    ->one();

                if ($currentUserAccount && $currentUserAccount->path) {
                    // Get all user_ids that are descendants of current user
                    $descendants = Mt4Account::find()
                        ->where(['like', 'path', $currentUserAccount->path . '.%', false])
                        ->orWhere(['path' => $currentUserAccount->path])
                        ->select('user_id')
                        ->distinct()
                        ->column();

                    $accessibleUserIds = $descendants;

                    // Current user can access if target user is themselves or descendant
                    if ($currentUser->id == $user_id || in_array($user_id, $accessibleUserIds)) {
                        $canAccess = true;
                    }

                    // Also check if current user is ancestor of target user
                    $targetUserAccount = Mt4Account::find()
                        ->where(['user_id' => $user_id])
                        ->one();

                    if ($targetUserAccount && $targetUserAccount->path) {
                        $pathParts = explode('.', $targetUserAccount->path);
                        // Check if current user's ID is in the path (meaning current user is ancestor)
                        if (in_array($currentUser->id, $pathParts)) {
                            $canAccess = true;
                        }
                    }
                } elseif ($currentUser->id == $user_id) {
                    // User can access their own accounts even without path
                    $canAccess = true;
                }
            }

            if (!$canAccess) {
                throw new ForbiddenHttpException('You do not have permission to view these accounts');
            }

            // Build query for accounts
            $accountsQuery = Mt4Account::find();

            if ($currentUser->user_tipe == 'ADMIN') {
                // Admin sees all accounts
                if ($user_id) {
                    $accountsQuery->where(['user_id' => $user_id]);
                }
            } else {
                // Non-admin: show own accounts and descendant accounts
                $currentUserAccount = Mt4Account::find()
                    ->where(['user_id' => $currentUser->id])
                    ->one();

                if ($currentUserAccount && $currentUserAccount->path) {
                    // Get all descendant user_ids including self
                    $descendantUsers = Mt4Account::find()
                        ->where(['like', 'path', $currentUserAccount->path . '.%', false])
                        ->orWhere(['path' => $currentUserAccount->path])
                        ->select('user_id')
                        ->distinct()
                        ->column();

                    $accountsQuery->where(['user_id' => $descendantUsers]);

                    // If specific user_id requested, filter further
                    if ($user_id && $user_id != $currentUser->id) {
                        $accountsQuery->andWhere(['user_id' => $user_id]);
                    }
                } else {
                    // User has no path, only show their own accounts
                    $accountsQuery->where(['user_id' => $currentUser->id]);
                }
            }

            // Get accounts with ordering
            $accounts = $accountsQuery
                ->orderBy(['created_at' => SORT_DESC])
                ->all();

            // Format response
            $accountData = [];
            $userIds = [];

            foreach ($accounts as $account) {
                $userIds[] = $account->user_id;
                $accountData[] = [
                    'id' => $account->id,
                    'user_id' => $account->user_id,
                    'account_id' => $account->account_id,
                    'bot_name' => $account->bot_name,
                    'path' => $account->path,
                    'buy_order_count' => (int)$account->buy_order_count,
                    'total_buy_lot' => (float)$account->total_buy_lot,
                    'sell_order_count' => (int)$account->sell_order_count,
                    'total_sell_lot' => (float)$account->total_sell_lot,
                    'total_profit' => (float)$account->total_profit,
                    'total_profit_percentage' => (float)$account->total_profit_percentage,
                    'account_balance' => (float)$account->account_balance,
                    'account_equity' => (float)$account->account_equity,
                    'floating_value' => (float)$account->floating_value,
                    'leverage' => $account->leverage,
                    'currency' => $account->currency,
                    'server' => $account->server,
                    'broker' => $account->broker,
                    'account_type' => $account->account_type,
                    'status' => $account->status,
                    'last_connected' => $account->last_connected,
                    'last_sync' => $account->last_sync,
                    'created_at' => $account->created_at,
                    'total_orders' => ($account->buy_order_count + $account->sell_order_count),
                    'total_lots' => ($account->total_buy_lot + $account->total_sell_lot),
                ];
            }

            // Get unique users for the response
            $users = Users::find()
                ->where(['id' => array_unique($userIds)])
                ->indexBy('id')
                ->all();

            // Build hierarchical summary
            $summary = [
                'total_accounts' => 0,
                'total_balance' => 0,
                'total_profit' => 0,
                'avg_profit_percentage' => 0,
                'active_accounts' => 0,
                'hierarchy' => []
            ];

            // Calculate summary
            foreach ($accounts as $account) {
                $summary['total_accounts']++;
                $summary['total_balance'] += (float)$account->account_balance;
                $summary['total_profit'] += (float)$account->total_profit;
                if ($account->status == 'active') {
                    $summary['active_accounts']++;
                }
            }

            if ($summary['total_accounts'] > 0) {
                $totalProfitPercentage = array_sum(array_column($accounts, 'total_profit_percentage'));
                $summary['avg_profit_percentage'] = round($totalProfitPercentage / $summary['total_accounts'], 2);
            }

            // Build hierarchical data structure
            $hierarchyData = $this->buildHierarchyData($accounts, $users);

            $responseData = [
                'status' => 'success',
                'data' => [
                    'summary' => [
                        'total_accounts' => (int)$summary['total_accounts'],
                        'total_balance' => (float)$summary['total_balance'],
                        'total_profit' => (float)$summary['total_profit'],
                        'avg_profit_percentage' => (float)$summary['avg_profit_percentage'],
                        'active_accounts' => (int)$summary['active_accounts'],
                    ],
                    'accounts' => $accountData,
                    'hierarchy' => $hierarchyData
                ]
            ];

            // Include target user info if specific user requested
            if ($user_id) {
                $responseData['data']['target_user'] = [
                    'id' => $targetUser->id,
                    'username' => $targetUser->user_name,
                    'email' => $targetUser->user_email,
                    'user_tipe' => $targetUser->user_tipe,
                ];
            }

            // Include current user info for context
            $responseData['data']['current_user'] = [
                'id' => $currentUser->id,
                'username' => $currentUser->user_name,
                'user_tipe' => $currentUser->user_tipe,
            ];

            return $responseData;
        } catch (UnauthorizedHttpException $e) {
            Yii::error('Authentication error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        } catch (ForbiddenHttpException $e) {
            Yii::error('Authorization error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        } catch (NotFoundHttpException $e) {
            Yii::error('Not found error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            Yii::error('Error getting accounts: ' . $e->getMessage());
            Yii::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'status' => 'error',
                'message' => 'An internal error occurred'
            ];
        }
    }

    /**
     * Extract token from request headers
     * @return string|null
     */
    private function getTokenFromRequest()
    {
        $headers = Yii::$app->request->headers;

        // Check for Bearer token in Authorization header
        $authHeader = $headers->get('Authorization');
        if ($authHeader && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            return $matches[1];
        }

        // Check for token in query parameter
        $token = Yii::$app->request->get('token');
        if ($token) {
            return $token;
        }

        // Check for token in POST parameter
        $token = Yii::$app->request->post('token');
        if ($token) {
            return $token;
        }

        return null;
    }

    /**
     * Extract user ID from JWT payload
     * @param object $payload
     * @return int|null
     */
    private function extractUserIdFromPayload($payload)
    {
        // Try different possible field names in the payload
        if (isset($payload->user_id)) {
            return $payload->user_id;
        }
        if (isset($payload->userId)) {
            return $payload->userId;
        }
        if (isset($payload->id)) {
            return $payload->id;
        }
        if (isset($payload->sub)) {
            return $payload->sub;
        }

        return null;
    }

    /**
     * Build hierarchical data structure based on paths
     * @param array $accounts
     * @param array $users
     * @return array
     */
    private function buildHierarchyData($accounts, $users)
    {
        $hierarchy = [];

        foreach ($accounts as $account) {
            if ($account->path) {
                $pathParts = explode('.', $account->path);
                $currentLevel = &$hierarchy;

                foreach ($pathParts as $part) {
                    if (!isset($currentLevel[$part])) {
                        $currentLevel[$part] = [
                            'user_id' => (int)$part,
                            'username' => isset($users[$part]) ? $users[$part]->user_name : 'Unknown',
                            'children' => [],
                            'accounts' => []
                        ];
                    }
                    $currentLevel = &$currentLevel[$part]['children'];
                }

                // Add account to the deepest level
                if (isset($currentLevel[$account->user_id])) {
                    $currentLevel[$account->user_id]['accounts'][] = [
                        'id' => $account->id,
                        'account_id' => $account->account_id,
                        'bot_name' => $account->bot_name,
                        'total_profit' => (float)$account->total_profit,
                        'account_balance' => (float)$account->account_balance,
                        'status' => $account->status,
                    ];
                }
            }
        }

        return array_values($hierarchy);
    }



    public function actionGetAccountsByUserPost()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            // Get POST parameters - THIS IS THE KEY PART
            $userId = Yii::$app->request->post('user_id');

            // Debug - log what we received
            Yii::info('Received POST data: ' . json_encode(Yii::$app->request->post()));
            Yii::info('user_id from POST: ' . $userId);

            // Validate required parameters
            if (empty($userId)) {
                return [
                    'status' => 'error',
                    'message' => 'user_id is required',
                    'debug' => [
                        'received_post' => Yii::$app->request->post(),
                        'received_raw' => file_get_contents('php://input')
                    ]
                ];
            }

            // Validate user exists
            $user = Users::findOne($userId);
            if (!$user) {
                throw new NotFoundHttpException('User not found');
            }

            // Get accounts
            $accounts = Mt4Account::find()
                ->where(['user_id' => $userId])
                ->orderBy(['created_at' => SORT_DESC])
                ->all();

            // Format response
            $accountData = [];
            foreach ($accounts as $account) {
                $accountData[] = [
                    'id' => $account->id,
                    'account_id' => $account->account_id,
                    'bot_name' => $account->bot_name,
                    'buy_order_count' => (int)$account->buy_order_count,
                    'total_buy_lot' => (float)$account->total_buy_lot,
                    'sell_order_count' => (int)$account->sell_order_count,
                    'total_sell_lot' => (float)$account->total_sell_lot,
                    'total_profit' => (float)$account->total_profit,
                    'account_balance' => (float)$account->account_balance,
                    'account_equity' => (float)$account->account_equity,
                    'floating_value' => (float)$account->floating_value,
                    'status' => $account->status,
                    'path' => $account->path,
                ];
            }

            return [
                'status' => 'success',
                'data' => [
                    'user_id' => (int)$userId,
                    'username' => $user->user_name ?? $user->username,
                    'total_accounts' => count($accountData),
                    'accounts' => $accountData,
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionGetAccountsByUser: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Format array of accounts
     */
    private function formatAccountsArray($accounts)
    {
        $formatted = [];
        foreach ($accounts as $account) {
            $formatted[] = $this->formatAccountData($account);
        }
        return $formatted;
    }
}
