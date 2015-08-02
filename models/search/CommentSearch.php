<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Comment;

/**
 * CommentSearch represents the model behind the search form about `app\models\Comment`.
 */
class CommentSearch extends Comment
{
    public function rules()
    {
        return [
            [['id', 'pid', 'uid', 'replyto'], 'integer'],
            [['author', 'email', 'type', 'url', 'user_agent', 'ip', 'content', 'status', 'ext'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Comment::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'pid' => $this->pid,
            'uid' => $this->uid,
            'replyto' => $this->replyto,
            'create_time' => $this->create_time,
            'update_time' => $this->update_time,
            'user_agent' => $this->user_agent,
            'email' => $this->email,
            'author' => $this->author,
            'type' => $this->type,
            'ip' => $this->ip,
            'status' => $this->status,
        ]);

        //TODO ext 属性的搜索
        $query->andFilterWhere(['like', 'url', $this->url])
            ->andFilterWhere(['like', 'content', $this->content])
            ->andFilterWhere(['like', 'user_agent', $this->user_agent]);

        return $dataProvider;
    }
}
