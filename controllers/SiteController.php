<?php

namespace app\controllers;

use app\models\Log;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\filters\VerbFilter;
use app\components\BaseController;
use app\models\Option;
use app\models\LoginForm;
use app\models\User;
use app\models\Post;

class SiteController extends BaseController
{

    public $layout = 'column1';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'maxLength' => 5,
                'minLength' => 4,
                'testLimit' => 2,
                'transparent' => true
            ],
        ];
    }

    public function actionIndex()
    {
        $this->layout = 'site';
        $dataProvider = new ActiveDataProvider([
            'query' => Post::find()
                ->where(['status' => [Post::STATUS_HIDDEN, Post::STATUS_PUBLISHED]])
                ->orderBy(['post_time' => SORT_DESC]),
            'pagination' => ['pageSize' => 10]
        ]);
        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest)
            return $this->goHome();

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            if($model->login()){
                Log::record(Log::TYPE_LOGIN, 'site/login', Yii::$app->user->id, Log::STATUS_SUCCESS, "用户「{$model->username}」成功!");
                return $this->goBack();
            }else
                Log::record(Log::TYPE_LOGIN, 'site/login', 0, Log::STATUS_FAILED, "用户「{$model->username}」登录失败!");

        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * 用户注册
     */
    public function actionRegister(){
        if(!Yii::$app->user->isGuest)
            $this->goHome();

        if(ArrayHelper::getValue(Yii::$app->params, Option::ALLOW_REGISTER) !== Option::STATUS_OPEN)
            return $this->render('register-closed');

        $model = new User();
        $model->setScenario(User::SCENARIO_REGISTER);
        if ($model->load(Yii::$app->request->post())) {
            if($model->save()){
                Yii::$app->session->setFlash('RegOption','注册成功，请用刚才注册的帐号登录！');
                return $this->redirect(['site/login']);
            }else
                $model->password = $model->password_repeat = null;
        }

        return $this->render('register', [
            'model' => $model,
        ]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
