<?php

namespace app\search\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\User;

/**
 * UserSearch represents the model behind the search form about `app\models\User`.
 */
class UserSearch extends User
{
    public function rules()
    {
        return [
            [['id', 'regtime', 'status'], 'integer'],
            [['nickname', 'name', 'password', 'email', 'url', 'acl', 'regip', 'salt', 'info', 'ext'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = User::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'regtime' => $this->regtime,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'nickname', $this->nickname])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'password', $this->password])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'url', $this->url])
            ->andFilterWhere(['like', 'acl', $this->acl])
            ->andFilterWhere(['like', 'regip', $this->regip])
            ->andFilterWhere(['like', 'salt', $this->salt])
            ->andFilterWhere(['like', 'info', $this->info])
            ->andFilterWhere(['like', 'ext', $this->ext]);

        return $dataProvider;
    }
}
