<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "trade_dd".
 *
 * @property int $id
 * @property int $user_id
 * @property string $license
 * @property string $account
 * @property float|null $wk_dd
 * @property float|null $wk_percentage_dd
 * @property string|null $wk_date
 * @property float|null $wk_equity
 * @property float|null $all_dd
 * @property float|null $all_percentage_dd
 * @property string|null $all_date
 * @property float|null $all_equity
 * @property string $created_at
 *
 * @property Users $user
 */
class Drawdown extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'drawdown';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'license', 'account'], 'required'],
            [['user_id'], 'integer'],
            [['wk_dd', 'wk_percentage_dd', 'wk_equity', 'all_dd', 'all_percentage_dd', 'all_equity'], 'number'],
            [['wk_date', 'all_date', 'created_at'], 'safe'],
            [['license'], 'string', 'max' => 255],
            [['account'], 'string', 'max' => 100],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'license' => 'License',
            'account' => 'Account',
            'wk_dd' => 'Wk Dd',
            'wk_percentage_dd' => 'Wk Percentage Dd',
            'wk_date' => 'Wk Date',
            'wk_equity' => 'Wk Equity',
            'all_dd' => 'All Dd',
            'all_percentage_dd' => 'All Percentage Dd',
            'all_date' => 'All Date',
            'all_equity' => 'All Equity',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::className(), ['id' => 'user_id']);
    }
}