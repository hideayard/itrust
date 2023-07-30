<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Enroll;

/**
 * EnrollSearch represents the model behind the search form about `app\models\Enroll`.
 */
class EnrollSearch extends Enroll
{

    public $courseTitle;
    public $participantName;
    public $teacherName;

    public function rules()
    {
        return [
            [['enroll_id', 'enroll_userid', 'enroll_courseid', 'enroll_cs', 'enroll_created_by', 'enroll_modified_by', 'enroll_status'], 'integer'],
            [['courseTitle', 'participantName', 'teacherName', 'enroll_remark', 'enroll_created_at', 'enroll_modified_at'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Enroll::find();
        $query->joinWith(['course', 'users', 'courseSession']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'enroll_id' => $this->enroll_id,
            'enroll_userid' => $this->enroll_userid,
            'enroll_courseid' => $this->enroll_courseid,
            'enroll_cs' => $this->enroll_cs,
            'enroll_created_at' => $this->enroll_created_at,
            'enroll_created_by' => $this->enroll_created_by,
            'enroll_modified_at' => $this->enroll_modified_at,
            'enroll_modified_by' => $this->enroll_modified_by,
            'enroll_status' => $this->enroll_status,
        ]);

        $query->andFilterWhere(['like', 'enroll_remark', $this->enroll_remark])
            ->andFilterWhere(['like', 'courses.course_title', $this->courseTitle])
            ->andFilterWhere(['like', 'users.user_nama', $this->participantName])
            ->andFilterWhere(['like', 'enrollCs.cs_teacher', $this->teacherName]);

        return $dataProvider;
    }
}
