<?php

namespace app\controllers;

use app\models\Mt4Account;
use app\models\Mt4Group;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class PublicController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Remove CSRF validation for API requests
        unset($behaviors['authenticator']);

        // Add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Origin' => ['*'], // Or restrict to specific domains: ['http://yourdomain.com']
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
            ],
        ];

        return $behaviors;
    }

        /**
     * Get MT4 accounts and groups where remark = "public"
     * This endpoint does NOT require authentication.
     *
     * GET /public/get-mt4-accounts
     *
     * Accounts are included if:
     *  - the account itself has remark = "public", OR
     *  - the account's account_id belongs to an Mt4Group with remark = "public"
     *    (matched via the group's mt4_ids JSON array, as in Mt4AccountController::getGroupsData)
     *
     * @return array
     */
    public function actionGetMt4Accounts()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            // ---- 1. Accounts flagged public directly via account remark ----
            $publicAccounts = Mt4Account::find()
                ->where(['remark' => 'public'])
                ->orderBy(['created_at' => SORT_DESC])
                ->all();

            // ---- 2. Groups with remark = "public" ----
            $publicGroups = Mt4Group::find()
                ->where(['remark' => 'public'])
                ->andWhere(['status' => Mt4Group::STATUS_ACTIVE])
                ->all();

            $publicGroupIds = [];
            $groupData = [];

            foreach ($publicGroups as $group) {
                $groupMt4Ids = $group->getMt4IdsArray();

                if (empty($groupMt4Ids)) {
                    continue;
                }

                $publicGroupIds = array_merge($publicGroupIds, $groupMt4Ids);

                // Build group summary (mirrors Mt4AccountController::getGroupsData structure)
                $groupData[] = [
                    'id' => $group->id,
                    'name' => $group->name,
                    'desc' => $group->desc ?? '',
                    'remark' => $group->remark ?? '',
                    'mt4_ids' => $group->getMt4IdsArray(),
                    'total_accounts' => 0, // filled below once accounts are collected
                    'status' => $group->status,
                ];
            }

            $publicGroupIds = array_values(array_unique($publicGroupIds));

            // ---- 3. Accounts that belong to a public group (by account_id) ----
            $groupAccounts = [];
            if (!empty($publicGroupIds)) {
                $groupAccounts = Mt4Account::find()
                    ->where(['in', 'account_id', $publicGroupIds])
                    ->orderBy(['created_at' => SORT_DESC])
                    ->all();
            }

            // ---- 4. Merge and de-duplicate by account id ----
            $accountsById = [];
            foreach ($publicAccounts as $account) {
                $accountsById[$account->id] = $account;
            }
            foreach ($groupAccounts as $account) {
                if (!isset($accountsById[$account->id])) {
                    $accountsById[$account->id] = $account;
                }
            }

            // Refresh group total_accounts counts
            foreach ($groupData as &$group) {
                $count = 0;
                foreach ($group['mt4_ids'] as $gmt4Id) {
                    foreach ($accountsById as $account) {
                        if ((string)$account->account_id === (string)$gmt4Id) {
                            $count++;
                            break;
                        }
                    }
                }
                $group['total_accounts'] = $count;
            }
            unset($group);

            // Format account data
            $accountData = [];
            foreach ($accountsById as $account) {
                $accountData[] = $this->formatAccountData($account);
            }

            return [
                'status' => 'success',
                'data' => [
                    'total_accounts' => count($accountData),
                    'accounts' => array_values($accountData),
                    'total_groups' => count($groupData),
                    'groups' => $groupData,
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Error in actionGetMt4Accounts: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
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
}
