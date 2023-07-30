<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Banner;

/**
 * BannerSearch represents the model behind the search form about `app\models\Banner`.
 */
class BannerSearch extends Banner
{
    public function rules()
    {
        return [
            [['b_id', 'b_created_by', 'b_modified_by'], 'integer'],
            [['b_title', 'b_desc', 'b_link', 'b_foto', 'b_created_at', 'b_modified_at', 'b_status'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Banner::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'b_id' => $this->b_id,
            'b_created_by' => $this->b_created_by,
            'b_created_at' => $this->b_created_at,
            'b_modified_by' => $this->b_modified_by,
            'b_modified_at' => $this->b_modified_at,
        ]);

        $query->andFilterWhere(['like', 'b_title', $this->b_title])
            ->andFilterWhere(['like', 'b_desc', $this->b_desc])
            ->andFilterWhere(['like', 'b_link', $this->b_link])
            ->andFilterWhere(['like', 'b_foto', $this->b_foto])
            ->andFilterWhere(['like', 'b_status', $this->b_status]);

        return $dataProvider;
    }
}
