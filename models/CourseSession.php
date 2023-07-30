<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "course_session".
 *
 * @property int $cs_id
 * @property int|null $cs_course_id
 * @property string|null $cs_remark
 * @property string|null $cs_teacher
 * @property int|null $cs_teacher_id
 * @property string|null $cs_date_start
 * @property string|null $cs_date_end
 * @property string|null $cs_hour_start
 * @property string|null $cs_hour_end
 * @property string|null $cs_dateline
 * @property string|null $cs_email
 * @property string|null $cs_next_course
 * @property string|null $cs_price
 * @property string|null $cs_code
 * @property string|null $cs_doc
 * @property string|null $cs_desc
 * @property int $cs_participant_limit
 * @property int|null $cs_created_by
 * @property string $cs_created_at
 * @property int|null $cs_modified_by
 * @property string $cs_modified_at
 *
 * @property Courses $csCourse
 */
class CourseSession extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'course_session';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cs_course_id', 'cs_teacher_id', 'cs_participant_limit', 'cs_created_by', 'cs_modified_by'], 'integer'],
            [['cs_date_start', 'cs_date_end', 'cs_hour_start', 'cs_hour_end', 'cs_dateline', 'cs_created_at', 'cs_modified_at'], 'safe'],
            [['cs_desc'], 'string'],
            [['cs_remark', 'cs_teacher'], 'string', 'max' => 100],
            [['cs_email', 'cs_next_course', 'cs_code'], 'string', 'max' => 50],
            [['cs_price'], 'string', 'max' => 25],
            [['cs_doc'], 'string', 'max' => 255],
            [['cs_course_id'], 'exist', 'skipOnError' => true, 'targetClass' => Courses::className(), 'targetAttribute' => ['cs_course_id' => 'course_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'cs_id' => 'ID',
            'cs_course_id' => 'Course ID',
            'cs_remark' => 'Remark',
            'cs_teacher' => 'Teacher',
            'cs_teacher_id' => 'Teacher ID',
            'cs_date_start' => 'Date Start',
            'cs_date_end' => 'Date End',
            'cs_hour_start' => 'Hour Start',
            'cs_hour_end' => 'Hour End',
            'cs_dateline' => 'Dateline',
            'cs_email' => 'Email',
            'cs_next_course' => 'Next Course',
            'cs_price' => 'Price',
            'cs_code' => 'Code',
            'cs_doc' => 'Doc',
            'cs_desc' => 'Desc',
            'cs_participant_limit' => 'Participant Limit',
            'cs_created_by' => 'Created By',
            'cs_created_at' => 'Created At',
            'cs_modified_by' => 'Modified By',
            'cs_modified_at' => 'Modified At',
        ];
    }

    /**
     * Gets query for [[CsCourse]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(Courses::className(), ['course_id' => 'cs_course_id']);
    }

    /** 
     * Gets query for [[CsTeacher]]. 
     * 
     * @return \yii\db\ActiveQuery 
     */
    public function getTeacher()
    {
        return $this->hasOne(Users::className(), ['user_id' => 'cs_teacher_id']);
    }

    /** 
     * Gets query for [[Enrolls]]. 
     * 
     * @return \yii\db\ActiveQuery 
     */
    public function getEnrolls()
    {
        return $this->hasMany(Enroll::className(), ['enroll_cs' => 'cs_id']);
    }
}
