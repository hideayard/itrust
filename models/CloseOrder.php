<?php

namespace app\models;

use Yii;

class CloseOrder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'close_order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_date'], 'safe'],
            [['order_status'], 'integer'],
            [['order_account','order_cmd'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'order_id' => 'Order ID',
            'order_account' => 'Order Account',
            'order_cmd' => 'Order Command',
            'order_status' => 'Order Status',
            'order_date' => 'Order Date',
        ];
    }
}
