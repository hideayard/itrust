<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "enroll".
 *
 * @property int $enroll_id
 * @property int|null $enroll_userid
 * @property int|null $enroll_courseid
 * @property int|null $enroll_cs
 * @property string|null $enroll_remark
 * @property string|null $enroll_created_at
 * @property int|null $enroll_created_by
 * @property string|null $enroll_modified_at
 * @property int|null $enroll_modified_by
 * @property int $enroll_status
 *
 * @property Courses $enrollCourse
 * @property Users $enrollUser
 */
class Enroll extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'enroll';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['enroll_userid', 'enroll_courseid', 'enroll_cs', 'enroll_created_by', 'enroll_modified_by', 'enroll_status', 'enroll_is_deleted'], 'integer'],
            [['enroll_created_at', 'enroll_modified_at'], 'safe'],
            [['enroll_remark'], 'string', 'max' => 100],
            [['enroll_courseid'], 'exist', 'skipOnError' => true, 'targetClass' => Courses::className(), 'targetAttribute' => ['enroll_courseid' => 'course_id']],
            [['enroll_userid'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['enroll_userid' => 'user_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'enroll_id' => 'Enroll ID',
            'enroll_userid' => 'Enroll Userid',
            'enroll_courseid' => 'Enroll Courseid',
            'enroll_cs' => 'Enroll Cs',
            'enroll_remark' => 'Enroll Remark',
            'enroll_created_at' => 'Enroll Created At',
            'enroll_created_by' => 'Enroll Created By',
            'enroll_modified_at' => 'Enroll Modified At',
            'enroll_modified_by' => 'Enroll Modified By',
            'enroll_status' => 'Enroll Status',
            'enroll_is_deleted' => 'Enroll Status',
        ];
    }

    /** 
    * Gets query for [[EnrollCs]]. 
    * 
    * @return \yii\db\ActiveQuery 
    */ 
   public function getEnrollCs() 
   { 
       return $this->hasOne(CourseSession::className(), ['cs_id' => 'enroll_cs']); 
   }

    /**
     * Gets query for [[EnrollCourse]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEnrollCourse()
    {
        return $this->hasOne(Courses::className(), ['course_id' => 'enroll_courseid']);
    }

    public function getEnrollSectionCount()
    {
        return $this->hasMany(CourseSection::className(), ['course_id' => 'enroll_courseid'])->count('course_id');
    }

    public function getEnrollProgress()
    {
        $sectionCount = $this->hasMany(CourseSection::className(), ['course_id' => 'enroll_courseid'])->count('course_id');
        if($sectionCount<1){$sectionCount=1;}
        $progressCount = $this->hasMany(EnrollProgress::className(), ['ep_enroll_id' => 'enroll_id'])->where(['ep_status' => 1])->count('ep_status');
        return  round( (( $progressCount / $sectionCount ) * 100) , 2) ;

    }

    public function getEnrollProgressText()
    {
        $sectionCount = $this->hasMany(CourseSection::className(), ['course_id' => 'enroll_courseid'])->count('course_id');
        if($sectionCount<1){$sectionCount=1;}
        $progressCount = $this->hasMany(EnrollProgress::className(), ['ep_enroll_id' => 'enroll_id'])->where(['ep_status' => 1])->count('ep_status');
        return  $progressCount ."/". $sectionCount;

    }

    /**
     * Gets query for [[EnrollUser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEnrollUser()
    {
        return $this->hasOne(Users::className(), ['user_id' => 'enroll_userid']);
    }

    public function getCourse()
    {
        return $this->hasOne(Courses::className(), ['course_id' => 'enroll_courseid']);
    }

    public function getUsers()
    {
        return $this->hasOne(Users::className(), ['user_id' => 'enroll_userid']);
    }

    public function getCourseSession()
    {
        return $this->hasOne(CourseSession::className(), ['cs_id' => 'enroll_cs']);
    }

}
