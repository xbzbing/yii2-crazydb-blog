<?php

namespace app\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\models\Post;
use app\models\User;
use app\components\BaseController;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends BaseController
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['profile'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * 通过nickname获取用户信息
     * @param $name
     * @throws NotFoundHttpException
     * @return mixed
     */
    public function actionShow($name){
        /* @var User $model */
        $this->layout = 'column2';
        $model = User::findOne(['nickname'=>$name]);
        if($model==null)
            throw new NotFoundHttpException('未找到相关用户的资料。');
        $dataProvider = new ActiveDataProvider([
            'query' => Post::find()->where(['status' => [Post::STATUS_HIDDEN, Post::STATUS_PUBLISHED], 'author_id' => $model->id])->orderBy(['is_top' => SORT_DESC, 'post_time' => SORT_DESC]),
            'pagination'=>[
                'pageSize'=>10
            ]
        ]);
        $this->view->params['seo_description'] = "{$model->nickname}在「". ArrayHelper::getValue(Yii::$app->params, 'site_name') ."」共发表{$dataProvider->totalCount}篇文章，个人资料：{$model->info}。";
        $this->view->params['seo_keywords'] = "{$model->nickname}," . ArrayHelper::getValue(Yii::$app->params, 'seo_keywords');
        return $this->render('view',[
                'model'=>$model,
                'dataProvider'=>$dataProvider
            ]
        );
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionProfile()
    {
        $model = $this->findModel(Yii::$app->user->id);
        $model->setScenario(User::SCENARIO_MODIFY_PROFILE);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
