<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "discussion".
 *
 * @property int $id
 * @property int $course_id
 * @property int $user_id
 * @property string $title
 * @property string $message
 * @property string $date
 * @property int $is_deleted
 * @property string $created_at
 * @property int $created_by
 * @property string $modified_at
 * @property int $modified_by
 */
class Discussion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'discussion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['course_id', 'user_id', 'message', 'date', 'is_deleted', 'created_by', 'modified_by'], 'required'],
            [['course_id', 'user_id', 'is_deleted', 'created_by', 'modified_by'], 'integer'],
            [['message'], 'string'],
            [['date', 'created_at', 'modified_at'], 'safe'],
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'course_id' => 'Course ID',
            'user_id' => 'User ID',
            'title' => 'Title',
            'message' => 'Message',
            'date' => 'Date',
            'is_deleted' => 'Is Deleted',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'modified_at' => 'Modified At',
            'modified_by' => 'Modified By',
        ];
    }
}
