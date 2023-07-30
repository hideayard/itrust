<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "video".
 *
 * @property int $video_id
 * @property string $video_filename
 * @property string $video_date
 * @property int $video_status
 */
class Video extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'video';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['video_filename'], 'required'],
            [['video_date'], 'safe'],
            [['video_status'], 'integer'],
            [['video_filename'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'video_id' => 'Video ID',
            'video_filename' => 'Video Filename',
            'video_date' => 'Video Date',
            'video_status' => 'Video Status',
        ];
    }
}
