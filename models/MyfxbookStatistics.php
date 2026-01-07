<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "myfxbook_statistics".
 *
 * @property int $id
 * @property string $date
 * @property int $total_scrapes
 * @property int $total_events
 * @property int $total_patterns
 * @property int $total_rates
 * @property float $avg_buy_signal
 * @property float $avg_sell_signal
 * @property string $created_at
 * @property string $updated_at
 */
class MyfxbookStatistics extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'myfxbook_statistics';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date'], 'required'],
            [['date', 'created_at', 'updated_at'], 'safe'],
            [['total_scrapes', 'total_events', 'total_patterns', 'total_rates'], 'integer'],
            [['avg_buy_signal', 'avg_sell_signal'], 'number'],
            [['date'], 'string', 'max' => 10],
            [['date'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => 'Date',
            'total_scrapes' => 'Total Scrapes',
            'total_events' => 'Total Events',
            'total_patterns' => 'Total Patterns',
            'total_rates' => 'Total Rates',
            'avg_buy_signal' => 'Avg Buy Signal',
            'avg_sell_signal' => 'Avg Sell Signal',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Get statistics for date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getStatisticsByDateRange($startDate, $endDate)
    {
        return self::find()
            ->where(['>=', 'date', $startDate])
            ->andWhere(['<=', 'date', $endDate])
            ->orderBy(['date' => SORT_ASC])
            ->all();
    }
}