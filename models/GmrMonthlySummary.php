<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "gmr_monthly_summary".
 *
 * Sourced from the MONTHLY sheet: the per-month rollup of buys, sells, fees and
 * profit.
 *
 * @property int $id
 * @property string $source_sheet
 * @property int|null $sheet_row
 * @property string $sync_key
 * @property string|null $month
 * @property float|null $nominal
 * @property float|null $rate_buy_avg
 * @property float|null $buy_amount
 * @property float|null $rate_sell_avg
 * @property float|null $sell_amount
 * @property float|null $fee
 * @property float|null $profit
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class GmrMonthlySummary extends ActiveRecord
{
    public static function tableName()
    {
        return 'gmr_monthly_summary';
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
            [['nominal', 'rate_buy_avg', 'buy_amount', 'rate_sell_avg', 'sell_amount', 'fee', 'profit'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
            [['source_sheet'], 'string', 'max' => 64],
            [['sync_key'], 'string', 'max' => 191],
            [['month'], 'string', 'max' => 16],
            [['sync_key'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'source_sheet' => 'Source Sheet',
            'sheet_row' => 'Sheet Row',
            'sync_key' => 'Sync Key',
            'month' => 'Month',
            'nominal' => 'Nominal',
            'rate_buy_avg' => 'Rate Buy (Avg)',
            'buy_amount' => 'Buy',
            'rate_sell_avg' => 'Rate Sell (Avg)',
            'sell_amount' => 'Sell',
            'fee' => 'Fee',
            'profit' => 'Profit',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
