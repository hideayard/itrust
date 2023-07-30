<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Video;

/**
 * VideoSearch represents the model behind the search form about `app\models\Video`.
 */
class VideoSearch extends Video
{
    public function rules()
    {
        return [
            [['video_id'], 'integer'],
            [['video_filename', 'video_date', 'video_status'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Video::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'video_id' => $this->video_id,
            'video_date' => $this->video_date,
        ]);

        $query->andFilterWhere(['like', 'video_filename', $this->video_filename])
            ->andFilterWhere(['like', 'video_status', $this->video_status]);

        return $dataProvider;
    }
}
