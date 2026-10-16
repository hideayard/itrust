<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "gmr_exchange".
 *
 * Sourced from the <MON> exchange sheets (AUG, SEP, OCT, ...). Each row holds a
 * single exchange transaction with both its buy and sell side.
 *
 * @property int $id
 * @property string|null $month
 * @property string $source_sheet
 * @property int|null $sheet_row
 * @property string $sync_key
 * @property string|null $trade_day
 * @property string|null $trade_date
 * @property string|null $ref_id
 * @property float|null $nominal
 * @property float|null $rate_buy
 * @property float|null $buy_amount
 * @property float|null $rate_sell
 * @property float|null $sell_amount
 * @property float|null $fee_pct
 * @property float|null $fee_amount
 * @property float|null $omset
 * @property float|null $profit
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class GmrExchange extends ActiveRecord
{
    public static function tableName()
    {
        return 'gmr_exchange';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['source_sheet', 'sync_key'], 'required'],
            [['sheet_row'], 'integer'],
            [['nominal', 'rate_buy', 'buy_amount', 'rate_sell', 'sell_amount', 'fee_pct', 'fee_amount', 'omset', 'profit'], 'number'],
            [['trade_date'], 'date', 'format' => 'php:Y-m-d'],
            [['trade_date', 'created_at', 'updated_at'], 'safe'],
            [['source_sheet'], 'string', 'max' => 64],
            [['sync_key'], 'string', 'max' => 191],
            [['month', 'trade_day'], 'string', 'max' => 16],
            [['ref_id'], 'string', 'max' => 32],
            [['sync_key'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'month' => 'Month',
            'source_sheet' => 'Source Sheet',
            'sheet_row' => 'Sheet Row',
            'sync_key' => 'Sync Key',
            'trade_day' => 'Day',
            'trade_date' => 'Date',
            'ref_id' => 'Ref ID',
            'nominal' => 'Nominal',
            'rate_buy' => 'Rate Buy',
            'buy_amount' => 'Buy',
            'rate_sell' => 'Rate Sell',
            'sell_amount' => 'Sell',
            'fee_pct' => 'Fee (%)',
            'fee_amount' => 'Fee (Rp)',
            'omset' => 'Omset',
            'profit' => 'Profit',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @param string $month
     * @return \yii\db\ActiveQuery
     */
    public static function findByMonth($month)
    {
        return static::find()->where(['month' => $month]);
    }
}
