<?php

namespace app\controllers;

use Yii;
use app\models\Tag;
use app\models\Post;
use app\components\BaseController;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

/**
 * TagController implements the CRUD actions for Tag model.
 */
class TagController extends BaseController
{
    public $layout = 'column-static';

    /**
     * 按照name获取tag
     * @param string $name
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionShow($name)
    {
        $tags = Tag::find()->select('pid')->where(['name' => $name])->asArray()->all();
        $pid = array();
        if (empty($tags))
            throw new NotFoundHttpException('The requested page does not exist.');

        foreach ($tags as $tag)
            $pid[] = $tag['pid'];

        $pid = array_filter(array_filter($pid));

        $dataProvider = new ActiveDataProvider([
            'query' => Post::find()->where(['in', 'id', $pid])->andWhere(['in', 'status', [Post::STATUS_HIDDEN, Post::STATUS_PUBLISHED]])->orderBy(['post_time' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 10
            ]
        ]);
        /* @var Post[] $posts */
        return $this->render('show', ['tag' => $name, 'dataProvider' => $dataProvider]);
    }

    /**
     * 显示所有的tag标签
     */
    public function actionList()
    {
        return $this->render('list', ['tags' => Tag::getTags(1)]);
    }
}
