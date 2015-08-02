<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Logger;

/**
 * LoggerSearch represents the model behind the search form about `app\models\Logger`.
 */
class LoggerSearch extends Logger
{
    public function rules()
    {
        return [
            [['id', 'uid', 'create_time'], 'integer'],
            [['status', 'optype', 'info', 'ip', 'user_agent'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Logger::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'uid' => $this->uid,
            'create_time' => $this->create_time,
        ]);

        $query->andFilterWhere(['like', 'status', $this->status])
            ->andFilterWhere(['like', 'optype', $this->optype])
            ->andFilterWhere(['like', 'info', $this->info])
            ->andFilterWhere(['like', 'ip', $this->ip])
            ->andFilterWhere(['like', 'user_agent', $this->user_agent]);

        return $dataProvider;
    }
}
