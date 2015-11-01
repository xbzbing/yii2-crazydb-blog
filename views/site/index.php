<?php

use yii\data\ActiveDataProvider;

/**
 * @var yii\web\View $this
 * @var ActiveDataProvider $dataProvider
 */

$this->title = Yii::$app->name;
echo $this->render('//post/posts', ['dataProvider' => $dataProvider]);