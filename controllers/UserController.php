<?php

namespace app\controllers;

use app\models\ModifyPassword;
use app\models\Option;
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
                        'actions' => ['profile', 'modify-password'],
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
    public function actionShow($name)
    {
        /* @var User $model */
        $this->layout = 'column2';
        $model = User::findOne(['nickname' => $name]);
        if ($model == null)
            throw new NotFoundHttpException('未找到相关用户的资料。');
        $dataProvider = new ActiveDataProvider([
            'query' => Post::find()->where(['status' => [Post::STATUS_HIDDEN, Post::STATUS_PUBLISHED], 'author_id' => $model->id])->orderBy(['is_top' => SORT_DESC, 'post_time' => SORT_DESC]),
            'pagination' => [
                'defaultPageSize' => 10
            ]
        ]);
        $this->view->params[Option::SEO_DESCRIPTION] = "{$model->nickname}在「" . ArrayHelper::getValue(Yii::$app->params, Option::SITE_NAME) . "」共发表{$dataProvider->totalCount}篇文章，个人简介：{$model->info}";
        $key_words = ArrayHelper::getValue(Yii::$app->params, Option::SEO_KEYWORDS);

        if (empty($key_words))
            Yii::$app->params[Option::SEO_KEYWORDS] = $model->nickname;
        elseif (strpos($key_words, $model->nickname) === false)
            Yii::$app->params[Option::SEO_KEYWORDS] = "{$model->nickname}," . $key_words;

        return $this->render('view', [
                'model' => $model,
                'dataProvider' => $dataProvider
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
            return $this->redirect(['user/show', 'name' => $model->nickname]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * 修改密码
     * @return string|\yii\web\Response
     */
    public function actionModifyPassword()
    {
        $model = new ModifyPassword();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['user/show', 'name' => $model->getUser()->nickname]);
        }
        $model->password = $model->old_password = $model->password_repeat = null;

        return $this->render('modify-pwd', [
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
