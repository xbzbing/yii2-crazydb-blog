<?php

namespace app\modules\admin\controllers;


use Yii;
use app\models\Category;
use yii\web\NotFoundHttpException;
use yii\data\ActiveDataProvider;
use app\modules\admin\components\Controller;

use app\components\CMSUtils;

/**
 * CategoryController implements the CRUD actions for Category model.
 */
class CategoryController extends Controller
{

    /**
     * Lists all Category models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Category::find()->where(['parent'=>0]),
        ]);
        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Category model.
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
     * Creates a new Category model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Category;
        $category_array = CMSUtils::getAllCategories();
        $category_array += ['0'=>'顶级分类'];
        if ($model->load($_POST)) {
            $model->save();
        }

        return $this->render('create', [
            'model' => $model,
            'category_array' => $category_array
        ]);
    }

    /**
     * Updates an existing Category model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $parent = $model->parent()->One();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->saveNode();
            if ($model->parent == 0 && !$model->isRoot()){
                $model->moveAsRoot();
            } elseif ($model->parent != 0 && $model->parent != $parent->id){
                $root = $this->findModel($model->parent);
                $model->moveAsLast($root);
            }
            return $this->render('tree');
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Category model.
     * @param string $id
     * @param string $updown
     * @return mixed
     */
    public function actionMove($id,$updown)
    {
        $model=$this->findModel($id);

        if($updown=="down") {
            $sibling=$model->next()->one();
            if (isset($sibling)) {
                $model->moveAfter($sibling);
                return $this->redirect(array('tree'));
            }
            return $this->redirect(array('tree'));
        }
        if($updown=="up"){
            $sibling=$model->prev()->one();
            if (isset($sibling)) {
                $model->moveBefore($sibling);
                return $this->redirect(array('tree'));
            }
            return $this->redirect(array('tree'));
        }
    }

    /**
     * Deletes an existing Category model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->deleteNode();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Category model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
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
}
