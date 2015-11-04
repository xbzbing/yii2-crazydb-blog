<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * PostSearch represents the model behind the search form about `app\models\Post`.
 */
class PostSearch extends Post
{

    public function rules()
    {
        return [
            [['id', 'cid', 'author_id', 'create_time', 'post_time', 'update_time'], 'integer'],
            [['author_name', 'type', 'title', 'alias', 'excerpt', 'content', 'cover', 'password', 'status', 'tags', 'options', 'ext_info'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Post::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'cid' => $this->cid,
            'type' => $this->type,
            'status' => $this->status,
            'author_id' => $this->author_id,
            'create_time' => $this->create_time,
            'post_time' => $this->post_time,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'author_name', $this->author_name])
            ->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'tags', $this->tags]);

        return $dataProvider;
    }
}
