<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * LogSearch represents the model behind the search form about `app\models\Log`.
 */
class LogSearch extends Log
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'uid', 'create_time'], 'integer'],
            [['type', 'action', 'result', 'key', 'detail', 'ip', 'user_agent'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Log::find();


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'uid' => $this->uid,
            'create_time' => $this->create_time,
            'action' => $this->action,
            'result' => $this->result,
            'key' => $this->key,
            'type' => $this->type
        ]);

        $query->andFilterWhere(['like', 'detail', $this->detail])
            ->andFilterWhere(['like', 'ip', $this->ip])
            ->andFilterWhere(['like', 'user_agent', $this->user_agent]);

        $query->with('user');

        return $dataProvider;
    }
}
