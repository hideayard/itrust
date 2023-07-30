<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\DataSensors;

/**
 * DataSensorsSearch represents the model behind the search form about `app\models\DataSensors`.
 */
class DataSensorsSearch extends DataSensors
{
    public function rules()
    {
        return [
            [['id', 'created_by', 'modified_by'], 'integer'],
            [['s1', 's2', 's3', 's4', 's5', 's6', 's7', 's8', 's9', 'ip', 'created_at', 'modified_at', 'status', 'remark'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = DataSensors::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'modified_at' => $this->modified_at,
            'modified_by' => $this->modified_by,
        ]);

        $query->andFilterWhere(['like', 's1', $this->s1])
            ->andFilterWhere(['like', 's2', $this->s2])
            ->andFilterWhere(['like', 's3', $this->s3])
            ->andFilterWhere(['like', 's4', $this->s4])
            ->andFilterWhere(['like', 's5', $this->s5])
            ->andFilterWhere(['like', 's6', $this->s6])
            ->andFilterWhere(['like', 's7', $this->s7])
            ->andFilterWhere(['like', 's8', $this->s8])
            ->andFilterWhere(['like', 's9', $this->s9])
            ->andFilterWhere(['like', 'ip', $this->ip])
            ->andFilterWhere(['like', 'status', $this->status])
            ->andFilterWhere(['like', 'remark', $this->remark]);

        return $dataProvider;
    }
}
