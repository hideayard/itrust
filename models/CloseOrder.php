<?php

namespace app\models;

use Yii;

class CloseOrder extends \yii\db\ActiveRecord
{
    const DEFAULT_EXPIRE_MINUTES = 10;

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
            [['order_date', 'expired_date'], 'safe'],
            [['order_status'], 'integer'],
            [['order_account','order_cmd'], 'string', 'max' => 50],
            [['order_multi_account', 'order_finished_account'], 'string'], // JSON will be stored as text
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeValidate()
    {
        if ($this->isNewRecord && empty($this->expired_date)) {
            $this->expired_date = date('Y-m-d H:i:s', strtotime('+' . self::DEFAULT_EXPIRE_MINUTES . ' minutes'));
        }

        return parent::beforeValidate();
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
            'expired_date' => 'Expired Date',
            'order_multi_account' => 'Multi Account',
            'order_finished_account' => 'Finished Account',
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

    /**
     * Get finished accounts as array
     * @return array
     */
    public function getFinishedAccounts()
    {
        if (empty($this->order_finished_account)) {
            return [];
        }
        return json_decode($this->order_finished_account, true) ?? [];
    }

    /**
     * Set finished accounts from array
     * @param array $accounts
     */
    public function setFinishedAccounts(array $accounts)
    {
        $this->order_finished_account = json_encode(array_values(array_unique($accounts)));
    }

    /**
     * Mark one account as finished.
     * @param string|int $account
     */
    public function addFinishedAccount($account)
    {
        $finishedAccounts = $this->getFinishedAccounts();
        $finishedAccounts[] = (string)$account;
        $this->setFinishedAccounts($finishedAccounts);
    }

    /**
     * Check whether all multi-account targets are finished.
     * @return bool
     */
    public function isMultiAccountFinished()
    {
        $multiAccounts = array_values(array_unique(array_map('strval', $this->getMultiAccounts())));
        $finishedAccounts = array_values(array_unique(array_map('strval', $this->getFinishedAccounts())));

        if (empty($multiAccounts)) {
            return false;
        }

        sort($multiAccounts);
        sort($finishedAccounts);

        return $multiAccounts === $finishedAccounts;
    }
}
