<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "account_orders".
 *
 * @property int $id
 * @property string $account_id
 * @property int $ticket
 * @property string $symbol
 * @property int $type
 * @property string|null $type_desc
 * @property float $lots
 * @property float $open_price
 * @property float $close_price
 * @property float $profit
 * @property float $swap
 * @property float $commission
 * @property int $open_time
 * @property int $close_time
 * @property int $magic
 * @property string|null $comment
 * @property string $status
 * @property string $synced_at
 * @property string $created_at
 * @property string $updated_at
 */
class AccountOrders extends ActiveRecord
{
    /**
     * Order type constants
     */
    const TYPE_BUY = 0;
    const TYPE_SELL = 1;
    const TYPE_BUY_LIMIT = 2;
    const TYPE_SELL_LIMIT = 3;
    const TYPE_BUY_STOP = 4;
    const TYPE_SELL_STOP = 5;

    /**
     * Status constants
     */
    const STATUS_OPEN = 'open';      
    const STATUS_MODIFIED = 'modified';      
    const STATUS_CLOSED = 'closed';
    const STATUS_DELETED = 'deleted';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'account_orders';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['account_id', 'ticket', 'symbol', 'type', 'lots', 'open_price', 'close_price', 'open_time', 'close_time'], 'required'],
            [['ticket', 'type', 'magic', 'open_time', 'close_time'], 'integer'],
            [['lots', 'open_price', 'close_price', 'profit', 'swap', 'commission'], 'number'],
            [['comment'], 'string'],
            [['account_id', 'symbol'], 'string', 'max' => 50],
            [['type_desc'], 'string', 'max' => 50],
            [['status'], 'string', 'max' => 20],
            [['account_id', 'ticket'], 'unique', 'targetAttribute' => ['account_id', 'ticket']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Account ID',
            'ticket' => 'Ticket',
            'symbol' => 'Symbol',
            'type' => 'Type',
            'type_desc' => 'Type Description',
            'lots' => 'Lots',
            'open_price' => 'Open Price',
            'close_price' => 'Close Price',
            'profit' => 'Profit',
            'swap' => 'Swap',
            'commission' => 'Commission',
            'open_time' => 'Open Time',
            'close_time' => 'Close Time',
            'magic' => 'Magic Number',
            'comment' => 'Comment',
            'status' => 'Status',
            'synced_at' => 'Synced At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Get order type description
     * 
     * @param int $type
     * @return string
     */
    public static function getOrderTypeDescription($type)
    {
        $types = [
            self::TYPE_BUY => 'Buy',
            self::TYPE_SELL => 'Sell',
            self::TYPE_BUY_LIMIT => 'Buy Limit',
            self::TYPE_SELL_LIMIT => 'Sell Limit',
            self::TYPE_BUY_STOP => 'Buy Stop',
            self::TYPE_SELL_STOP => 'Sell Stop',
        ];

        return $types[$type] ?? 'Unknown';
    }

    /**
     * Get order type badge HTML
     * 
     * @return string
     */
    public function getTypeBadge()
    {
        $badges = [
            self::TYPE_BUY => 'success',
            self::TYPE_SELL => 'danger',
            self::TYPE_BUY_LIMIT => 'info',
            self::TYPE_SELL_LIMIT => 'warning',
            self::TYPE_BUY_STOP => 'primary',
            self::TYPE_SELL_STOP => 'secondary',
        ];

        $class = $badges[$this->type] ?? 'secondary';
        $typeName = $this->type_desc ?? self::getOrderTypeDescription($this->type);

        return "<span class='badge badge-{$class}'>{$typeName}</span>";
    }

    /**
     * Get formatted profit with color
     * 
     * @return string
     */
    public function getFormattedProfit()
    {
        $class = $this->profit >= 0 ? 'text-success' : 'text-danger';
        $sign = $this->profit >= 0 ? '+' : '';

        return "<span class='{$class} font-weight-bold'>{$sign}" . number_format($this->profit, 2) . "</span>";
    }

    /**
     * Check if order exists by ticket and account
     * 
     * @param string $accountId
     * @param int $ticket
     * @return bool
     */
    public static function exists($accountId, $ticket)
    {
        return self::find()
            ->where(['account_id' => $accountId, 'ticket' => $ticket])
            ->exists();
    }

    /**
     * Find orders by account
     * 
     * @param string $accountId
     * @return \yii\db\ActiveQuery
     */
    public static function findByAccount($accountId)
    {
        return self::find()->where(['account_id' => $accountId]);
    }

    /**
     * Get total profit by account
     * 
     * @param string $accountId
     * @return float
     */
    public static function getTotalProfitByAccount($accountId)
    {
        return self::find()
            ->where(['account_id' => $accountId, 'status' => self::STATUS_CLOSED])
            ->sum('profit') ?? 0;
    }

    /**
     * Get total profit by symbol
     * 
     * @param string $accountId
     * @param string $symbol
     * @return float
     */
    public static function getProfitBySymbol($accountId, $symbol)
    {
        return self::find()
            ->where(['account_id' => $accountId, 'symbol' => $symbol, 'status' => self::STATUS_CLOSED])
            ->sum('profit') ?? 0;
    }

    /**
     * Get win rate for an account
     * 
     * @param string $accountId
     * @return array
     */
    public static function getWinRate($accountId)
    {
        $total = self::find()
            ->where(['account_id' => $accountId, 'status' => self::STATUS_CLOSED])
            ->count();

        if ($total == 0) {
            return ['total' => 0, 'wins' => 0, 'win_rate' => 0];
        }

        $wins = self::find()
            ->where(['account_id' => $accountId, 'status' => self::STATUS_CLOSED])
            ->andWhere(['>', 'profit', 0])
            ->count();

        return [
            'total' => $total,
            'wins' => $wins,
            'win_rate' => round(($wins / $total) * 100, 2)
        ];
    }
}
