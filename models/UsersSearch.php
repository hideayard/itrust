<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Users;

/**
 * UsersSearch represents the model behind the search form about `app\models\Users`.
 */
class UsersSearch extends Users
{
    public function rules()
    {
        return [
            [['user_id', 'user_status', 'created_by', 'modified_by', 'is_deleted'], 'integer'],
            [['user_name', 'user_nama', 'user_pass', 'user_hp', 'user_email', 'user_tipe', 'user_foto', 'created_at', 'modified_at'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        
        $query = Users::find();
        if (Yii::$app->user->identity->user_tipe != 'ADMIN') {
            $query->where([
                'user_tipe' => 'USER'
            ]);
        }
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'user_id' => $this->user_id,
            'user_status' => $this->user_status,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'modified_at' => $this->modified_at,
            'modified_by' => $this->modified_by,
            'is_deleted' => $this->is_deleted,
        ]);

        

        $query->andFilterWhere(['like', 'user_name', $this->user_name])
            ->andFilterWhere(['like', 'user_nama', $this->user_nama])
            ->andFilterWhere(['like', 'user_pass', $this->user_pass])
            ->andFilterWhere(['like', 'user_hp', $this->user_hp])
            ->andFilterWhere(['like', 'user_email', $this->user_email])
            ->andFilterWhere(['like', 'user_tipe', $this->user_tipe])
            ->andFilterWhere(['like', 'user_foto', $this->user_foto]);

        return $dataProvider;
    }
}
