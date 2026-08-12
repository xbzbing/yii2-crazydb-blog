<?php

namespace app\modules\admin\controllers;


use Yii;
use yii\web\NotFoundHttpException;
use app\models\Log;
use app\models\LogSearch;
use app\models\User;
use app\modules\admin\components\Controller;
use app\components\XUtils;

/**
 * LogController implements the CRUD actions for Log model.
 */
class LogController extends Controller
{

    /**
     * Lists all Log models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new LogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->sort->defaultOrder = ['create_time' => SORT_DESC];

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Log model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionDelete($id){
        /* @var User $current_user */
        $current_user = Yii::$app->user->identity;
        Log::record(Log::TYPE_DELETE_LOG, 'log/delete', $id, Log::STATUS_FAILED, "{$current_user->username}({$current_user->id})尝试删除[ID={$id}]的日志.");
        XUtils::actionMessage('admin', 'log/delete', 'error', '删除日志失败!');
        return $this->redirect('index');
    }

    /**
     * Finds the Log model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Log the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Log::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
