<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Notif;

/**
 * NotifSearch represents the model behind the search form about `app\models\Notif`.
 */
class NotifSearch extends Notif
{
    public function rules()
    {
        return [
            [['notif_id'], 'integer'],
            [['notif_from', 'notif_title', 'notif_text', 'notif_date', 'notif_processed'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Notif::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'notif_id' => $this->notif_id,
            'notif_date' => $this->notif_date,
        ]);

        $query->andFilterWhere(['like', 'notif_from', $this->notif_from])
            ->andFilterWhere(['like', 'notif_title', $this->notif_title])
            ->andFilterWhere(['like', 'notif_text', $this->notif_text])
            ->andFilterWhere(['like', 'notif_processed', $this->notif_processed]);

        return $dataProvider;
    }
}
