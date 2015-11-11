<?php

namespace app\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\caching\DbDependency;
use yii\db\Query;
use yii\data\ActiveDataProvider;
use app\models\Comment;
use app\models\Post;
use app\models\PostSearch;
use app\components\BaseController;

/**
 * PostController implements the CRUD actions for Post model.
 */
class PostController extends BaseController
{

    public $layout = 'column-post';

    /**
     * Lists all Post models.
     * @return mixed
     */
    public function actionList()
    {
        $this->layout = 'column-list';
        $dataProvider = new ActiveDataProvider([
            'query' => Post::find()
                ->where(['status' => [Post::STATUS_HIDDEN, Post::STATUS_PUBLISHED]])
                ->orderBy(['post_time' => SORT_DESC]),
            'pagination' => ['defaultPageSize' => 10]
        ]);
        $this->view->params['breadcrumbs'][] = '所有文章';
        return $this->render('posts', ['dataProvider' => $dataProvider]);
    }

    /**
     * Displays a single Post model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * @param $name
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionShow($name)
    {
        $post = $this->findModelByAlias($name);

        //按类型显示
        $comments = Comment::findAll(['pid' => $post->id, 'status' => Comment::STATUS_APPROVED]);
        $post->updateCounters(['view_count' => 1, 'comment_count' => count($comments) - $post->comment_count]);
        return $this->render('view', [
            'post' => $post,
            'comments' => $comments,
            'hide_post' => array(
                'passed' => [],
                'info' => '',
                'captcha' => 'hide-captcha',
                'pwd' => 'hide-pwd'
            )
        ]);
    }

    /**
     * 显示文章归档
     */
    public function actionArchives()
    {
        $items = Post::find()
            ->select('id,cid,title,alias,post_time')
            ->where(['in', 'status', [Post::STATUS_PUBLISHED, Post::STATUS_HIDDEN]])
            ->orderBy(['post_time' => SORT_DESC])
            ->asArray()
            ->all();
        $this->view->title = '文章归档';
        $this->view->params['seo_keywords'] .= ',文章归档';
        $num = count($items);
        $this->view->params['seo_description'] = "这里是「{" . Yii::$app->params['site_name'] . "」的文章归档，目前共有{$num}篇文章。";
        return $this->render('archives', ['data' => $items, 'sum' => $num]);
    }

    /**
     * @todo 归档的细化操作
     * @param $year
     * @param $month
     */
    public function actionArchivesDate($year, $month)
    {
        $this->layout = 'column-list';
        $date = "{$year}-{$month}";
        $start = strtotime($date);
        $end = strtotime('+1 month', $start);
        $dataProvider = new ActiveDataProvider([
            'query' => Post::find()->where(['between', 'post_time', $start, $end]),
            'pagination' => [
                'defaultPageSize' => 10
            ]
        ]);
        $this->view->title  = '文章归档:' . $date;
        echo $this->render('archives-date', ['date' => $date, 'dataProvider' => $dataProvider]);
    }

    /**
     * Finds the Post model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Post the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Post::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Finds the Post model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $alias
     * @return Post the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModelByAlias($alias)
    {
        if (($model = Post::find()->where(['alias' => $alias])->with('category')->one()) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
