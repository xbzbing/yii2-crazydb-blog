<?php

namespace app\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use app\models\Category;
use app\models\Post;
use app\components\BaseController;

/**
 * CategoryController implements the CRUD actions for Category model.
 */
class CategoryController extends BaseController
{

    public $layout = 'column-list';

    /**
     * Displays a single Category model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('show', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * 按别名访问
     * @param $name
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionShow($name)
    {
        $model = $this->findModelByAlias($name);
        $dataProvider = new ActiveDataProvider([
            'query' => Post::find()
                ->where(['cid' => $model->id, 'status' => [Post::STATUS_PUBLISHED, Post::STATUS_HIDDEN]])
                ->orderBy(['post_time' => SORT_DESC]),
            'pagination' => [
                'defaultPageSize' => '10'
            ],
        ]);
        return $this->render('show', [
            'model' => $model,
            'dataProvider' => $dataProvider
        ]);
    }

    /**
     * Finds the Category model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Category the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Category::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Finds the Category model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $alias
     * @return Category the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModelByAlias($alias)
    {
        if (($model = Category::findOne(['alias' => $alias])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
