<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "service".
 *
 * @property int $id
 * @property int|null $channel_id
 * @property int|null $user_id
 * @property string $name
 * @property string $code
 */
class Service extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'service';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['channel_id', 'user_id'], 'integer'],
            [['name', 'code'], 'required'],
            [['name', 'code'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'channel_id' => 'Channel ID',
            'user_id' => 'User ID',
            'name' => 'Name',
            'code' => 'Code',
        ];
    }
}