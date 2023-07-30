<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "course_section".
 *
 * @property int $id
 * @property int $course_id
 * @property int $section_order
 * @property int $subsection_order
 * @property int $section_prev
 * @property int $section_next
 * @property string|null $section_title
 * @property string|null $subsection_title
 * @property string|null $type
 * @property string|null $content
 * @property string|null $video_url
 * @property int $video_duration
 * @property int|null $is_deleted
 * @property string $created_at
 * @property int|null $created_by
 * @property string $modified_at
 * @property int|null $modified_by
 * @property string|null $resource_url
 *
 * @property Courses $course
 */
class CourseSection extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'course_section';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['course_id'], 'required'],
            [['course_id', 'section_order', 'subsection_order', 'section_prev', 'section_next', 'video_duration', 'is_deleted', 'created_by', 'modified_by'], 'integer'],
            [['content', 'resource_url'], 'string'],
            [['created_at', 'modified_at'], 'safe'],
            [['section_title', 'subsection_title', 'video_url'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 50],
            [['course_id'], 'exist', 'skipOnError' => true, 'targetClass' => Courses::className(), 'targetAttribute' => ['course_id' => 'course_id']],
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
            'section_order' => 'Section Order',
            'subsection_order' => 'Subsection Order',
            'section_prev' => 'Section Prev',
            'section_next' => 'Section Next',
            'section_title' => 'Section Title',
            'subsection_title' => 'Subsection Title',
            'type' => 'Type',
            'content' => 'Content',
            'video_url' => 'Video Url',
            'video_duration' => 'Video Duration',
            'is_deleted' => 'Is Deleted',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'modified_at' => 'Modified At',
            'modified_by' => 'Modified By',
            'resource_url' => 'Resource Url',
        ];
    }

    /**
     * Gets query for [[Course]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(Courses::className(), ['course_id' => 'course_id']);
    }
}
