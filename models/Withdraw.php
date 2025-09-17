<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "withdraw".
 *
 * @property int $id
 * @property int $user_id
 * @property string $license
 * @property string $account
 * @property float $wd_value
 * @property string $created_at
 *
 * @property Users $user
 */
class Withdraw extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'withdraw';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'license', 'account', 'wd_value'], 'required'],
            [['user_id'], 'integer'],
            [['wd_value'], 'number'],
            [['created_at'], 'safe'],
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
            'wd_value' => 'Withdraw Value',
            'created_at' => 'Created At'
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::className(), ['user_id' => 'user_id']);
    }
}
