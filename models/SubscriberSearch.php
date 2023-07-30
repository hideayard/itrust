<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Subscriber;

/**
 * SubscriberSearch represents the model behind the search form about `app\models\Subscriber`.
 */
class SubscriberSearch extends Subscriber
{
    public function rules()
    {
        return [
            [['subs_id', 'subs_status'], 'integer'],
            [['subs_email', 'subs_remark', 'created_at'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Subscriber::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'subs_id' => $this->subs_id,
            'subs_status' => $this->subs_status,
            'created_at' => $this->created_at,
        ]);

        $query->andFilterWhere(['like', 'subs_email', $this->subs_email])
            ->andFilterWhere(['like', 'subs_remark', $this->subs_remark]);

        return $dataProvider;
    }
}
