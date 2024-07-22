<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $name
 * @property int $telegram_id
 * @property string $telegram_username
 * @property int|null $channel
 * @property int $service
 * @property int $question
 * @property string|null $created_at
 * @property string|null $username
 * @property string|null $email
 * @property string|null $phone
 */
class UserTele extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_tele';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'telegram_id', 'telegram_username'], 'required'],
            [['telegram_id', 'channel', 'service', 'question'], 'integer'],
            [['created_at'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['telegram_username'], 'string', 'max' => 100],
            [['username', 'email', 'phone'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'telegram_id' => 'Telegram ID',
            'telegram_username' => 'Telegram Username',
            'channel' => 'Channel',
            'service' => 'Service',
            'question' => 'Question',
            'created_at' => 'Created At',
            'username' => 'Username',
            'email' => 'Email',
            'phone' => 'Phone',
        ];
    }
}
