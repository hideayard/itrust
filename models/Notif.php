<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "notif".
 *
 * @property int $notif_id
 * @property string $notif_from
 * @property string $notif_title
 * @property string $notif_text
 * @property string $notif_date
 * @property string $notif_processed
 */
class Notif extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notif';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['notif_from', 'notif_title', 'notif_text', 'notif_date'], 'required'],
            [['notif_date'], 'safe'],
            [['notif_processed'], 'string'],
            [['notif_from'], 'string', 'max' => 50],
            [['notif_title'], 'string', 'max' => 100],
            [['notif_text'], 'string', 'max' => 1000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'notif_id' => 'Notif ID',
            'notif_from' => 'Notif From',
            'notif_title' => 'Notif Title',
            'notif_text' => 'Notif Text',
            'notif_date' => 'Notif Date',
            'notif_processed' => 'Notif Processed',
        ];
    }
}
