<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "subscriber".
 *
 * @property int $subs_id
 * @property string|null $subs_email
 * @property int $subs_status
 * @property string|null $subs_remark
 * @property string $created_at
 */
class Subscriber extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subscriber';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['subs_status'], 'integer'],
            [['created_at'], 'safe'],
            [['subs_email', 'subs_remark'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'subs_id' => 'Subs ID',
            'subs_email' => 'Subs Email',
            'subs_status' => 'Subs Status',
            'subs_remark' => 'Subs Remark',
            'created_at' => 'Created At',
        ];
    }
}
