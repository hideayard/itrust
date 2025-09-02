<?php
namespace app\modules\trade\models;

use Yii;

/**
 * This is the model class for table "trade_dd".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int $account_id
 * @property float $dd_value
 * @property float $dd_percentage
 * @property float $equity
 * @property string $created_at
 * @property string|null $updated_at
 */
class TradeDD extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'trade_dd';
    }

    public function rules()
    {
        return [
            [['user_id', 'account_id'], 'integer'],
            [['account_id', 'dd_value', 'dd_percentage', 'equity'], 'required'],
            [['dd_value', 'dd_percentage', 'equity'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'account_id' => 'Account ID',
            'dd_value' => 'Drawdown Value',
            'dd_percentage' => 'Drawdown Percentage',
            'equity' => 'Equity',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Get maximum drawdown for an account (all time)
     */
    public static function getMaxDD($account_id)
    {
        return self::find()
            ->where(['account_id' => $account_id])
            ->select(['MAX(dd_value) as max_dd_value', 'MAX(dd_percentage) as max_dd_percentage'])
            ->asArray()
            ->one();
    }

    /**
     * Get maximum drawdown for an account (current week)
     */
    public static function getWeeklyMaxDD($account_id)
    {
        $startOfWeek = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d 23:59:59', strtotime('sunday this week'));

        return self::find()
            ->where(['account_id' => $account_id])
            ->andWhere(['between', 'created_at', $startOfWeek, $endOfWeek])
            ->select(['MAX(dd_value) as max_dd_value', 'MAX(dd_percentage) as max_dd_percentage'])
            ->asArray()
            ->one();
    }

    /**
     * Get combined drawdown statistics for an account
     */
    public static function getAccountStats($account_id)
    {
        $allTime = self::getMaxDD($account_id);
        $weekly = self::getWeeklyMaxDD($account_id);

        return [
            'all_time' => [
                'max_dd_value' => $allTime['max_dd_value'] ?? 0,
                'max_dd_percentage' => $allTime['max_dd_percentage'] ?? 0,
            ],
            'weekly' => [
                'max_dd_value' => $weekly['max_dd_value'] ?? 0,
                'max_dd_percentage' => $weekly['max_dd_percentage'] ?? 0,
            ],
        ];
    }
}