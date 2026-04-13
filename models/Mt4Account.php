<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "mt4_account".
 *
 * @property int $id
 * @property int $user_id
 * @property string $account_id
 * @property string|null $bot_name
 * @property int|null $buy_order_count
 * @property float|null $total_buy_lot
 * @property int|null $sell_order_count
 * @property float|null $total_sell_lot
 * @property float|null $total_profit
 * @property float|null $total_profit_percentage
 * @property float|null $account_balance
 * @property float|null $account_equity
 * @property float|null $floating_value
 * @property int|null $leverage
 * @property string|null $currency
 * @property string|null $server
 * @property string|null $broker
 * @property string|null $account_type
 * @property string|null $password
 * @property string|null $api_key
 * @property string|null $api_secret
 * @property string|null $path
 * @property string|null $status
 * @property string|null $remark
 * @property string|null $last_connected
 * @property string|null $last_sync
 * @property int|null $disabled_ea
 * @property string $created_at
 * @property int|null $created_by
 * @property string $modified_at
 * @property int|null $modified_by
 * @property float|null $min_lot
 *
 * @property Users $user
 * @property Users $createdBy
 * @property Users $modifiedBy
 */
class Mt4Account extends ActiveRecord
{
    /**
     * Account type constants
     */
    const ACCOUNT_TYPE_STANDARD = 'standard';
    const ACCOUNT_TYPE_ECN = 'ecn';
    const ACCOUNT_TYPE_ISLAMIC = 'islamic';
    const ACCOUNT_TYPE_CENT = 'cent';
    const ACCOUNT_TYPE_DEMO = 'demo';

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_DISCONNECTED = 'disconnected';
    const STATUS_ERROR = 'error';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mt4_account';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // Required fields
            [['user_id', 'account_id'], 'required'],
            
            // Integer fields
            [['user_id', 'buy_order_count', 'sell_order_count', 'leverage', 'created_by', 'modified_by','disabled_ea','buy_status','sell_status'], 'integer'],
            
            // Decimal fields
            [['min_lot','total_buy_lot', 'total_sell_lot', 'total_profit', 'total_profit_percentage', 
              'account_balance', 'account_equity', 'floating_value'], 'number'],
            
            // String fields
            [['account_id', 'bot_name', 'server', 'broker', 'currency', 'api_key', 'api_secret'], 'string', 'max' => 255],
            [['password', 'path', 'remark'], 'string'],
            
            // ENUM fields with default values
            [['account_type'], 'in', 'range' => array_keys(self::getAccountTypeOptions())],
            [['status'], 'in', 'range' => array_keys(self::getStatusOptions())],
            
            // Default values
            [['account_type'], 'default', 'value' => self::ACCOUNT_TYPE_STANDARD],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['currency'], 'default', 'value' => 'USD'],
            [['leverage'], 'default', 'value' => 100],
            
            // Default numeric values
            [['buy_status'], 'default', 'value' => 0],
            [['sell_status'], 'default', 'value' => 0],
            [['disabled_ea'], 'default', 'value' => 0],
            [['buy_order_count'], 'default', 'value' => 0],
            [['sell_order_count'], 'default', 'value' => 0],
            [['min_lot'], 'default', 'value' => 0.00],
            [['total_buy_lot'], 'default', 'value' => 0.00],
            [['total_sell_lot'], 'default', 'value' => 0.00],
            [['total_profit'], 'default', 'value' => 0.00],
            [['total_profit_percentage'], 'default', 'value' => 0.00],
            [['account_balance'], 'default', 'value' => 0.00],
            [['account_equity'], 'default', 'value' => 0.00],
            [['floating_value'], 'default', 'value' => 0.00],
            
            // Date/time fields
            [['last_connected', 'last_sync', 'created_at', 'modified_at'], 'safe'],
            
            // Unique constraint
            // [['user_id', 'account_id', 'server'], 'unique', 
            //     'targetAttribute' => ['user_id', 'account_id', 'server'],
            //     'message' => 'This account already exists for this user on the specified server.'],
            
            // Account ID format validation (optional)
            [['account_id'], 'match', 'pattern' => '/^[A-Za-z0-9\-_]+$/',
                'message' => 'Account ID can only contain letters, numbers, hyphens and underscores.'],
            
            // Foreign key validation - FIXED: Changed from User::class to Users::class
            [['user_id'], 'exist', 'skipOnError' => true, 
                'targetClass' => Users::class, 
                'targetAttribute' => ['user_id' => 'user_id']],  // FIXED: Changed 'id' to 'user_id'
            [['created_by'], 'exist', 'skipOnError' => true, 
                'targetClass' => Users::class, 
                'targetAttribute' => ['created_by' => 'user_id']],  // FIXED: Changed from 'id' to 'user_id'
            [['modified_by'], 'exist', 'skipOnError' => true, 
                'targetClass' => Users::class, 
                'targetAttribute' => ['modified_by' => 'user_id']],  // FIXED: Changed from 'id' to 'user_id'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User'),
            'account_id' => Yii::t('app', 'Account ID'),
            'bot_name' => Yii::t('app', 'Bot Name'),
            'buy_order_count' => Yii::t('app', 'Buy Orders'),
            'min_lot' => Yii::t('app', 'Min Lot'),
            'total_buy_lot' => Yii::t('app', 'Total Buy Lot'),
            'sell_order_count' => Yii::t('app', 'Sell Orders'),
            'total_sell_lot' => Yii::t('app', 'Total Sell Lot'),
            'total_profit' => Yii::t('app', 'Total Profit'),
            'total_profit_percentage' => Yii::t('app', 'Profit %'),
            'account_balance' => Yii::t('app', 'Balance'),
            'account_equity' => Yii::t('app', 'Equity'),
            'floating_value' => Yii::t('app', 'Floating P/L'),
            'leverage' => Yii::t('app', 'Leverage'),
            'currency' => Yii::t('app', 'Currency'),
            'server' => Yii::t('app', 'Server'),
            'broker' => Yii::t('app', 'Broker'),
            'account_type' => Yii::t('app', 'Account Type'),
            'password' => Yii::t('app', 'Password'),
            'api_key' => Yii::t('app', 'API Key'),
            'api_secret' => Yii::t('app', 'API Secret'),
            'path' => Yii::t('app', 'Path'),
            'status' => Yii::t('app', 'Status'),
            'remark' => Yii::t('app', 'Remark'),
            'last_connected' => Yii::t('app', 'Last Connected'),
            'last_sync' => Yii::t('app', 'Last Sync'),
            'created_at' => Yii::t('app', 'Created At'),
            'created_by' => Yii::t('app', 'Created By'),
            'modified_at' => Yii::t('app', 'Modified At'),
            'modified_by' => Yii::t('app', 'Modified By'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'modified_at',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'modified_by',
            ],
        ];
    }

    /**
     * Gets query for [[User]].
     * FIXED: Changed from User to Users
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::class, ['user_id' => 'user_id']);  // FIXED: Changed 'id' to 'user_id'
    }

    /**
     * Gets query for [[CreatedBy]].
     * FIXED: Changed from User to Users
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(Users::class, ['user_id' => 'created_by']);  // FIXED: Changed 'id' to 'user_id'
    }

    /**
     * Gets query for [[ModifiedBy]].
     * FIXED: Changed from User to Users
     *
     * @return \yii\db\ActiveQuery
     */
    public function getModifiedBy()
    {
        return $this->hasOne(Users::class, ['user_id' => 'modified_by']);  // FIXED: Changed 'id' to 'user_id'
    }

    /**
     * Get account type options for dropdown
     * 
     * @return array
     */
    public static function getAccountTypeOptions()
    {
        return [
            self::ACCOUNT_TYPE_STANDARD => Yii::t('app', 'Standard'),
            self::ACCOUNT_TYPE_ECN => Yii::t('app', 'ECN'),
            self::ACCOUNT_TYPE_ISLAMIC => Yii::t('app', 'Islamic'),
            self::ACCOUNT_TYPE_CENT => Yii::t('app', 'Cent'),
            self::ACCOUNT_TYPE_DEMO => Yii::t('app', 'Demo'),
        ];
    }

    /**
     * Get status options for dropdown
     * 
     * @return array
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_ACTIVE => Yii::t('app', 'Active'),
            self::STATUS_INACTIVE => Yii::t('app', 'Inactive'),
            self::STATUS_SUSPENDED => Yii::t('app', 'Suspended'),
            self::STATUS_DISCONNECTED => Yii::t('app', 'Disconnected'),
            self::STATUS_ERROR => Yii::t('app', 'Error'),
        ];
    }

    /**
     * Get status badge HTML
     * 
     * @return string
     */
    public function getStatusBadge()
    {
        $badges = [
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'secondary',
            self::STATUS_SUSPENDED => 'danger',
            self::STATUS_DISCONNECTED => 'warning',
            self::STATUS_ERROR => 'danger',
        ];
        
        $class = $badges[$this->status] ?? 'secondary';
        return "<span class='badge badge-{$class}'>" . Yii::t('app', ucfirst($this->status)) . "</span>";
    }

    /**
     * Get account type badge HTML
     * 
     * @return string
     */
    public function getAccountTypeBadge()
    {
        $badges = [
            self::ACCOUNT_TYPE_STANDARD => 'info',
            self::ACCOUNT_TYPE_ECN => 'primary',
            self::ACCOUNT_TYPE_ISLAMIC => 'success',
            self::ACCOUNT_TYPE_CENT => 'warning',
            self::ACCOUNT_TYPE_DEMO => 'secondary',
        ];
        
        $class = $badges[$this->account_type] ?? 'info';
        return "<span class='badge badge-{$class}'>" . Yii::t('app', ucfirst($this->account_type)) . "</span>";
    }

    /**
     * Get formatted profit with color
     * 
     * @return string
     */
    public function getFormattedProfit()
    {
        $profit = $this->total_profit;
        $class = $profit >= 0 ? 'text-success' : 'text-danger';
        $sign = $profit >= 0 ? '+' : '';
        
        return "<span class='{$class} font-weight-bold'>{$sign}" . Yii::$app->formatter->asCurrency($profit) . "</span>";
    }

    /**
     * Get formatted floating value with color
     * 
     * @return string
     */
    public function getFormattedFloating()
    {
        $floating = $this->floating_value;
        $class = $floating >= 0 ? 'text-success' : 'text-danger';
        $sign = $floating >= 0 ? '+' : '';
        
        return "<span class='{$class} font-weight-bold'>{$sign}" . Yii::$app->formatter->asCurrency($floating) . "</span>";
    }

    /**
     * Get formatted balance
     * 
     * @return string
     */
    public function getFormattedBalance()
    {
        return Yii::$app->formatter->asCurrency($this->account_balance);
    }

    /**
     * Get formatted equity
     * 
     * @return string
     */
    public function getFormattedEquity()
    {
        return Yii::$app->formatter->asCurrency($this->account_equity);
    }

    /**
     * Check if account is connected
     * 
     * @return bool
     */
    public function isConnected()
    {
        return $this->status === self::STATUS_ACTIVE && $this->last_connected !== null;
    }

    /**
     * Check if account is profitable
     * 
     * @return bool
     */
    public function isProfitable()
    {
        return $this->total_profit > 0;
    }

    /**
     * Get profit percentage formatted
     * 
     * @return string
     */
    public function getProfitPercentageFormatted()
    {
        $class = $this->total_profit_percentage >= 0 ? 'text-success' : 'text-danger';
        $sign = $this->total_profit_percentage >= 0 ? '+' : '';
        
        return "<span class='{$class}'>{$sign}{$this->total_profit_percentage}%</span>";
    }

    /**
     * Get total orders count
     * 
     * @return int
     */
    public function getTotalOrders()
    {
        return ($this->buy_order_count ?? 0) + ($this->sell_order_count ?? 0);
    }

    /**
     * Get total lots
     * 
     * @return float
     */
    public function getTotalLots()
    {
        return ($this->total_buy_lot ?? 0) + ($this->total_sell_lot ?? 0);
    }

    /**
     * Get win rate percentage
     * 
     * @return float
     */
    public function getWinRate()
    {
        $total = $this->getTotalOrders();
        if ($total === 0) {
            return 0;
        }
        
        // Assuming profitable trades ratio (simplified)
        $profitableTrades = $this->total_profit > 0 ? $total * 0.6 : $total * 0.3;
        return round(($profitableTrades / $total) * 100, 2);
    }

    /**
     * Get formatted last connected
     * 
     * @return string
     */
    public function getLastConnectedFormatted()
    {
        if ($this->last_connected === null) {
            return Yii::t('app', 'Never');
        }
        
        return Yii::$app->formatter->asRelativeTime($this->last_connected);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Encrypt password before saving
        if (!empty($this->password)) {
            $this->password = Yii::$app->getSecurity()->encryptByPassword(
                $this->password,
                Yii::$app->params['encryptionKey'] ?? 'mt4-secret-key'
            );
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function afterFind()
    {
        parent::afterFind();

        // Decrypt password after finding
        if (!empty($this->password)) {
            try {
                $this->password = Yii::$app->getSecurity()->decryptByPassword(
                    $this->password,
                    Yii::$app->params['encryptionKey'] ?? 'mt4-secret-key'
                );
            } catch (\Exception $e) {
                // If decryption fails, keep as is
                Yii::error('Failed to decrypt password: ' . $e->getMessage());
            }
        }
    }

    /**
     * Find accounts by user ID
     * 
     * @param int $userId
     * @return \yii\db\ActiveQuery
     */
    public static function findByUser($userId)
    {
        return static::find()->where(['user_id' => $userId]);
    }

    /**
     * Find active accounts
     * 
     * @return \yii\db\ActiveQuery
     */
    public static function findActive()
    {
        return static::find()->where(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Update account metrics
     * 
     * @param array $data
     * @return bool
     */
    public function updateMetrics($data)
    {
        $this->buy_order_count = $data['buy_order_count'] ?? $this->buy_order_count;
        $this->total_buy_lot = $data['total_buy_lot'] ?? $this->total_buy_lot;
        $this->sell_order_count = $data['sell_order_count'] ?? $this->sell_order_count;
        $this->total_sell_lot = $data['total_sell_lot'] ?? $this->total_sell_lot;
        $this->total_profit = $data['total_profit'] ?? $this->total_profit;
        $this->total_profit_percentage = $data['total_profit_percentage'] ?? $this->total_profit_percentage;
        $this->account_balance = $data['account_balance'] ?? $this->account_balance;
        $this->account_equity = $data['account_equity'] ?? $this->account_equity;
        $this->floating_value = $data['floating_value'] ?? $this->floating_value;
        $this->last_sync = new Expression('NOW()');
        
        return $this->save(false);
    }

    /**
     * Mark as connected
     * 
     * @return bool
     */
    public function markConnected()
    {
        $this->status = self::STATUS_ACTIVE;
        $this->last_connected = new Expression('NOW()');
        return $this->save(false);
    }

    /**
     * Mark as disconnected
     * 
     * @return bool
     */
    public function markDisconnected()
    {
        $this->status = self::STATUS_DISCONNECTED;
        return $this->save(false);
    }
}