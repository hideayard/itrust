<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "gmr_invoice_detail".
 *
 * Sourced from the INV_DETAIL_BUY_<MON> sheets: one line per buy leg inside an
 * invoice (DATE, ID, INVOICE NUMBER, DESCRIPTION, QTY, UNIT PRICE, AMOUNT).
 *
 * @property int $id
 * @property string|null $month
 * @property string $source_sheet
 * @property int|null $sheet_row
 * @property string $sync_key
 * @property string|null $detail_date
 * @property string|null $ref_id
 * @property string|null $invoice_number
 * @property string|null $description
 * @property float|null $qty
 * @property float|null $unit_price
 * @property float|null $amount
 * @property float|null $fee
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class GmrInvoiceDetail extends ActiveRecord
{
    public static function tableName()
    {
        return 'gmr_invoice_detail';
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
            [['qty', 'unit_price', 'amount', 'fee'], 'number'],
            [['detail_date'], 'date', 'format' => 'php:Y-m-d'],
            [['detail_date', 'created_at', 'updated_at'], 'safe'],
            [['source_sheet'], 'string', 'max' => 64],
            [['sync_key'], 'string', 'max' => 191],
            [['month'], 'string', 'max' => 16],
            [['ref_id'], 'string', 'max' => 32],
            [['invoice_number'], 'string', 'max' => 64],
            [['description'], 'string', 'max' => 255],
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
            'detail_date' => 'Date',
            'ref_id' => 'Ref ID',
            'invoice_number' => 'Invoice Number',
            'description' => 'Description',
            'qty' => 'Qty',
            'unit_price' => 'Unit Price',
            'amount' => 'Amount',
            'fee' => 'Fee',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice()
    {
        return $this->hasOne(GmrInvoice::class, ['invoice_number' => 'invoice_number']);
    }
}
