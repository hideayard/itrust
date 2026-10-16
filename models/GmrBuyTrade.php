<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "gmr_buy_trade".
 *
 * Sourced from the BUY_<MON> sheets of the iTrust 2026 workbook. Each row is
 * one USD buy leg (Day, Date, ID, Nominal, Price, Total).
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
 * @property float|null $price
 * @property float|null $total
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class GmrBuyTrade extends ActiveRecord
{
    public static function tableName()
    {
        return 'gmr_buy_trade';
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
            [['nominal', 'price', 'total'], 'number'],
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
            'nominal' => 'Nominal (USD)',
            'price' => 'Price',
            'total' => 'Total (IDR)',
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
