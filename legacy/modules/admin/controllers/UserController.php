<?php

namespace app\modules\admin\controllers;

use app\components\XUtils;
use app\models\Log;
use Yii;
use yii\web\NotFoundHttpException;
use app\models\User;
use app\models\UserSearch;
use app\modules\admin\components\Controller;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
{

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UserSearch;
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * Displays a single User model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->setScenario(User::SCENARIO_MANAGE);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        /* @var User $current_user */
        $action = 'user/delete';
        $current_user = Yii::$app->user->identity;
        if(intval($id) === Yii::$app->user->id){
            XUtils::actionMessage('admin', $action, 'error', '不能删除自己!');
            Log::record(Log::TYPE_PERMISSION_DENY, $action, $id, Log::STATUS_FAILED, "「$current_user->nickname」尝试删除自己失败!");
        }else{
            $user = $this->findModel($id);
            if($user->isAdmin()){
                $user->status = User::STATUS_DELETED;
                $user->save();
                XUtils::actionMessage('admin', $action, 'success', '管理员用户不能直接删除,已经将其状态修改为"DELETED",如果要彻底删除请直接操作数据库或者等更新....');
                Log::record(Log::TYPE_PERMISSION_DENY, $action, $id, Log::STATUS_FAILED, "「$current_user->nickname」尝试删除管理员「$user->nickname」失败!");
            }else{
                $user->delete();
                XUtils::actionMessage('admin', $action, 'success', '删除成功!');
                Log::record(Log::TYPE_PERMISSION_DENY, $action, $id, Log::STATUS_FAILED, "「$current_user->nickname」删除用户「$user->nickname」($id)成功!");
            }
        }
        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
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
