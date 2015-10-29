<?php

namespace app\modules\admin\controllers;


use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use app\models\Category;
use app\modules\admin\components\Controller;

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
            'query' => Category::find()->where(['parent' => 0]),
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
        $model = new Category();
        $category_array = Category::getAllCategories();

        if($category_array)
            $category_array = ['0' => '顶级分类'] + $category_array;
        else
            $category_array = ['0' => '顶级分类'];

        if (isset($category_array[1]) && $category_array[1] == '未分类')
            unset($category_array[1]);

        if ($model->load($_POST)) {
            if ($model->save())
                $this->redirect(['category/view', 'id' => $model->id]);
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
        $category_array = Category::getAllCategories();
        $category_array = ['0' => '顶级分类'] + $category_array;
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $id]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'category_array' => $category_array
            ]);
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
        $this->findModel($id)->delete();

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
            throw new NotFoundHttpException('404 未找到.');
        }
    }
}
