<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Courses;

/**
 * CoursesSearch represents the model behind the search form about `app\models\Courses`.
 */
class CoursesSearch extends Courses
{
    public function rules()
    {
        return [
            [['course_id', 'course_star', 'course_created_by', 'course_modified_by', 'course_status', 'course_is_deleted'], 'integer'],
            [['course_title', 'course_desc', 'course_content', 'course_type', 'course_is_online', 'course_price', 'course_created_at', 'course_modified_at'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Courses::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'course_id' => $this->course_id,
            'course_star' => $this->course_star,
            'course_created_by' => $this->course_created_by,
            'course_created_at' => $this->course_created_at,
            'course_modified_by' => $this->course_modified_by,
            'course_modified_at' => $this->course_modified_at,
            'course_status' => $this->course_status,
            'course_is_deleted' => $this->course_is_deleted,
        ]);

        $query->andFilterWhere(['like', 'course_title', $this->course_title])
            ->andFilterWhere(['like', 'course_desc', $this->course_desc])
            ->andFilterWhere(['like', 'course_content', $this->course_content])
            ->andFilterWhere(['like', 'course_type', $this->course_type])
            ->andFilterWhere(['like', 'course_is_online', $this->course_is_online])
            ->andFilterWhere(['like', 'course_price', $this->course_price]);

        return $dataProvider;
    }

    public function searchForTrainer($params, $userid)
    {
        $query = Courses::find();

        if (Yii::$app->user->identity->user_tipe != 'ADMIN') {
            $query->andWhere(['course_trainer' => $userid]);
        }
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'course_id' => $this->course_id,
            'course_star' => $this->course_star,
            'course_created_by' => $this->course_created_by,
            'course_created_at' => $this->course_created_at,
            'course_modified_by' => $this->course_modified_by,
            'course_modified_at' => $this->course_modified_at,
            'course_status' => $this->course_status,
            'course_is_deleted' => $this->course_is_deleted,
        ]);

        $query->andFilterWhere(['like', 'course_title', $this->course_title])
            ->andFilterWhere(['like', 'course_desc', $this->course_desc])
            ->andFilterWhere(['like', 'course_content', $this->course_content])
            ->andFilterWhere(['like', 'course_type', $this->course_type])
            ->andFilterWhere(['like', 'course_is_online', $this->course_is_online])
            ->andFilterWhere(['like', 'course_price', $this->course_price]);

        return $dataProvider;
    }
}
