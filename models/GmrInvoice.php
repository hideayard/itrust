<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "gmr_invoice".
 *
 * Sourced from the INV_BUY_<MON> and INV_SELL_<MON> sheets. Both sheet families
 * are normalised into the same shape (qty = USD, rate = IDR/USD, amount = IDR
 * total); direction distinguishes the buy and sell invoices.
 *
 * @property int $id
 * @property string|null $month
 * @property string $source_sheet
 * @property int|null $sheet_row
 * @property string $sync_key
 * @property string $direction
 * @property string|null $invoice_date
 * @property string|null $ref_id
 * @property string|null $invoice_number
 * @property string|null $billed_to
 * @property string|null $payment_method
 * @property string|null $issue_date
 * @property string|null $due_date
 * @property string|null $status
 * @property float|null $qty
 * @property float|null $rate
 * @property float|null $amount
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class GmrInvoice extends ActiveRecord
{
    const DIRECTION_BUY = 'BUY';
    const DIRECTION_SELL = 'SELL';

    const STATUS_PENDING = 'PENDING';
    const STATUS_PAID = 'PAID';
    const STATUS_CANCEL = 'CANCEL';

    public static function tableName()
    {
        return 'gmr_invoice';
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
            [['direction'], 'in', 'range' => [self::DIRECTION_BUY, self::DIRECTION_SELL]],
            [['direction'], 'default', 'value' => self::DIRECTION_BUY],
            [['sheet_row'], 'integer'],
            [['qty', 'rate', 'amount'], 'number'],
            [['invoice_date', 'issue_date', 'due_date'], 'date', 'format' => 'php:Y-m-d'],
            [['invoice_date', 'issue_date', 'due_date', 'created_at', 'updated_at'], 'safe'],
            [['source_sheet'], 'string', 'max' => 64],
            [['sync_key'], 'string', 'max' => 191],
            [['month'], 'string', 'max' => 16],
            [['ref_id'], 'string', 'max' => 32],
            [['invoice_number'], 'string', 'max' => 64],
            [['billed_to'], 'string', 'max' => 128],
            [['payment_method'], 'string', 'max' => 64],
            [['status'], 'string', 'max' => 32],
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
            'direction' => 'Direction',
            'invoice_date' => 'Date',
            'ref_id' => 'Ref ID',
            'invoice_number' => 'Invoice Number',
            'billed_to' => 'Billed To',
            'payment_method' => 'Payment Method',
            'issue_date' => 'Issue Date',
            'due_date' => 'Due Date',
            'status' => 'Status',
            'qty' => 'Qty (USD)',
            'rate' => 'Rate',
            'amount' => 'Amount (IDR)',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @param string $direction
     * @return \yii\db\ActiveQuery
     */
    public static function findByDirection($direction)
    {
        return static::find()->where(['direction' => $direction]);
    }

    /**
     * Line items belonging to this invoice, matched on the invoice number.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDetails()
    {
        return $this->hasMany(GmrInvoiceDetail::class, ['invoice_number' => 'invoice_number'])
            ->orderBy(['gmr_invoice_detail.id' => SORT_ASC]);
    }
}
