<?php

use yii\data\ActiveDataProvider;

/**
 * @var yii\web\View $this
 * @var ActiveDataProvider $dataProvider
 */

echo $this->render('//post/posts', ['dataProvider' => $dataProvider]);