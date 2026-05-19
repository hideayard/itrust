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
            [['order_multi_account'], 'string'], // JSON will be stored as text
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
            'order_multi_account' => 'Multi Account',
        ];
    }
    
    /**
     * Get multi accounts as array
     * @return array
     */
    public function getMultiAccounts()
    {
        if (empty($this->order_multi_account)) {
            return [];
        }
        return json_decode($this->order_multi_account, true) ?? [];
    }
    
    /**
     * Set multi accounts from array
     * @param array $accounts
     */
    public function setMultiAccounts(array $accounts)
    {
        $this->order_multi_account = json_encode($accounts);
    }
}