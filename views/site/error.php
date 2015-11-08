<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

$this->title = $name . ' - 发生错误';
$this->params['breadcrumbs'][] = '访问错误';
?>
<br>
<div class="alert alert-warning">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= nl2br(Html::encode($message)) ?>
</div>