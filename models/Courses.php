<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "courses".
 *
 * @property int $course_id
 * @property string $course_title
 * @property string $course_desc
 * @property string $course_content
 * @property string $course_type
 * @property int $course_is_online
 * @property string $course_price
 * @property int $course_star
 * @property int $course_created_by
 * @property string $course_created_at
 * @property int|null $course_modified_by
 * @property string $course_modified_at
 * @property int $course_status
 * @property int $course_is_deleted
 * @property string|null $course_photo
 *
 * @property CourseSection[] $courseSections
 * @property Enroll[] $enrolls
 */
class Courses extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'courses';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['course_title', 'course_desc', 'course_content', 'course_type', 'course_price', 'course_created_by'], 'required'],
            [['course_content'], 'string'],
            [['course_is_online', 'course_star', 'course_participant_limit', 'course_created_by', 'course_modified_by', 'course_status', 'course_is_deleted', 'course_trainer'], 'integer'],
            [['course_title', 'course_created_at', 'course_modified_at'], 'safe'],
            [['course_title'], 'string', 'max' => 100],
            [['course_desc', 'course_photo'], 'string', 'max' => 255],
            [['course_type'], 'string', 'max' => 50],
            [['course_price'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'course_id' => 'Course ID',
            'course_title' => 'Course Title',
            'course_desc' => 'Course Desc',
            'course_content' => 'Course Content',
            'course_type' => 'Course Type',
            'course_is_online' => 'Course Is Online',
            'course_price' => 'Course Price',
            'course_star' => 'Course Star',
            'course_participant_limit' => 'Jumlah Participant',
            'course_created_by' => 'Course Created By',
            'course_created_at' => 'Course Created At',
            'course_modified_by' => 'Course Modified By',
            'course_modified_at' => 'Course Modified At',
            'course_status' => 'Course Status',
            'course_is_deleted' => 'Course Is Deleted',
            'course_photo' => 'Course Photo',
            'course_trainer' => 'Course Trainer', 
        ];
    }

    /**
     * Gets query for [[CourseSections]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCourseSections()
    {
        return $this->hasMany(CourseSection::className(), ['course_id' => 'course_id']);
    }

    /**
     * Gets query for [[Enrolls]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEnrolls()
    {
        return $this->hasMany(Enroll::className(), ['enroll_courseid' => 'course_id']);
    }

    /**
    * Gets query for [[CourseTrainer]]. 
    * 
    * @return \yii\db\ActiveQuery 
    */ 
   public function getCourseTrainer() 
   { 
       return $this->hasOne(Users::className(), ['user_id' => 'course_trainer']); 
   } 
}
