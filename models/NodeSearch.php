<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Node;

/**
 * NodeSearch represents the model behind the search form about `app\models\Node`.
 */
class NodeSearch extends Node
{
    public function rules()
    {
        return [
            [['node_id', 'node_created_by', 'node_modified_by', 'node_status'], 'integer'],
            [['node_name', 'node_remark', 'node_created_at', 'node_modified_at'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Node::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'node_id' => $this->node_id,
            'node_created_at' => $this->node_created_at,
            'node_created_by' => $this->node_created_by,
            'node_modified_at' => $this->node_modified_at,
            'node_modified_by' => $this->node_modified_by,
            'node_status' => $this->node_status,
        ]);

        $query->andFilterWhere(['like', 'node_name', $this->node_name])
            ->andFilterWhere(['like', 'node_remark', $this->node_remark]);

        return $dataProvider;
    }
}
