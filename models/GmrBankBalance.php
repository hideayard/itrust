<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "gmr_bank_balance".
 *
 * Sourced from the BANK sheet. Each business day contains a START and an END
 * row holding the running balance for every bank account plus the USDT total.
 *
 * @property int $id
 * @property string $source_sheet
 * @property int|null $sheet_row
 * @property string $sync_key
 * @property string|null $entry_type
 * @property string|null $balance_day
 * @property string|null $balance_date
 * @property float|null $global_amount
 * @property float|null $sisa_usdt
 * @property float|null $tambah_modal
 * @property float|null $out_amount
 * @property float|null $total
 * @property float|null $profit
 * @property string|null $remark
 * @property float|null $bca
 * @property float|null $bca_bisnis
 * @property float|null $blu_bca
 * @property float|null $mandiri
 * @property float|null $bri
 * @property float|null $bri_vega
 * @property float|null $bni
 * @property float|null $sinarmas
 * @property float|null $total_bank
 * @property float|null $usdt
 * @property float|null $rate
 * @property float|null $total_usdt
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class GmrBankBalance extends ActiveRecord
{
    const ENTRY_START = 'START';
    const ENTRY_END = 'END';

    public static function tableName()
    {
        return 'gmr_bank_balance';
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
            [[
                'global_amount', 'sisa_usdt', 'tambah_modal', 'out_amount', 'total', 'profit',
                'bca', 'bca_bisnis', 'blu_bca', 'mandiri', 'bri', 'bri_vega', 'bni', 'sinarmas',
                'total_bank', 'usdt', 'rate', 'total_usdt',
            ], 'number'],
            [['balance_date'], 'date', 'format' => 'php:Y-m-d'],
            [['balance_date', 'created_at', 'updated_at'], 'safe'],
            [['source_sheet'], 'string', 'max' => 64],
            [['sync_key'], 'string', 'max' => 191],
            [['entry_type'], 'string', 'max' => 8],
            [['balance_day'], 'string', 'max' => 16],
            [['remark'], 'string', 'max' => 255],
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
            'entry_type' => 'Entry',
            'balance_day' => 'Day',
            'balance_date' => 'Date',
            'global_amount' => 'Global',
            'sisa_usdt' => 'Sisa USDT',
            'tambah_modal' => 'Tambah Modal',
            'out_amount' => 'Out',
            'total' => 'Total',
            'profit' => 'Profit',
            'remark' => 'Remark',
            'bca' => 'BCA',
            'bca_bisnis' => 'BCA Bisnis',
            'blu_bca' => 'Blu BCA',
            'mandiri' => 'Mandiri',
            'bri' => 'BRI',
            'bri_vega' => 'BRI Vega',
            'bni' => 'BNI',
            'sinarmas' => 'Sinarmas',
            'total_bank' => 'Total',
            'usdt' => 'USDT',
            'rate' => 'Rate',
            'total_usdt' => 'Total USDT',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
