<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\CourseSession;

/**
 * CourseSessionSearch represents the model behind the search form of `app\models\CourseSession`.
 */
class CourseSessionSearch extends CourseSession
{

    public $courseTitle;
    public $courseTeacher;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cs_id', 'cs_course_id', 'cs_teacher_id', 'cs_participant_limit', 'cs_created_by', 'cs_modified_by'], 'integer'],
            [['courseTitle', 'courseTeacher', 'cs_remark', 'cs_teacher', 'cs_date_start', 'cs_date_end', 'cs_hour_start', 'cs_hour_end', 'cs_dateline', 'cs_email', 'cs_next_course', 'cs_price', 'cs_code', 'cs_doc', 'cs_desc', 'cs_created_at', 'cs_modified_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = CourseSession::find();
        $query->joinWith(['course', 'teacher']);
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $dataProvider->sort->attributes['courseTitle'] = [
            'asc' => ['courses.course_title' => SORT_ASC],
            'desc' => ['courses.course_title' => SORT_DESC],
        ];

        $dataProvider->sort->attributes['courseTeacher'] = [
            'asc' => ['users.user_nama' => SORT_ASC],
            'desc' => ['users.user_nama' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'cs_id' => $this->cs_id,
            'cs_course_id' => $this->cs_course_id,
            'cs_teacher_id' => $this->cs_teacher_id,
            'cs_date_start' => $this->cs_date_start,
            'cs_date_end' => $this->cs_date_end,
            'cs_hour_start' => $this->cs_hour_start,
            'cs_hour_end' => $this->cs_hour_end,
            'cs_dateline' => $this->cs_dateline,
            'cs_participant_limit' => $this->cs_participant_limit,
            'cs_created_by' => $this->cs_created_by,
            'cs_created_at' => $this->cs_created_at,
            'cs_modified_by' => $this->cs_modified_by,
            'cs_modified_at' => $this->cs_modified_at,
        ]);

        $query->andFilterWhere(['like', 'cs_remark', $this->cs_remark])
            ->andFilterWhere(['like', 'cs_teacher', $this->cs_teacher])
            ->andFilterWhere(['like', 'cs_email', $this->cs_email])
            ->andFilterWhere(['like', 'cs_next_course', $this->cs_next_course])
            ->andFilterWhere(['like', 'cs_price', $this->cs_price])
            ->andFilterWhere(['like', 'cs_code', $this->cs_code])
            ->andFilterWhere(['like', 'cs_doc', $this->cs_doc])
            ->andFilterWhere(['like', 'cs_desc', $this->cs_desc])
            ->andFilterWhere(['like', 'courses.course_title', $this->courseTitle])
            ->andFilterWhere(['like', 'users.user_nama', $this->courseTeacher]);

        return $dataProvider;
    }
}
