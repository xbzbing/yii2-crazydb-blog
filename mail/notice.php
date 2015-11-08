<?php
use yii\web\View;
/* @var View $this */
/* @var string $name */
/* @var string $message */
/* @var string $title */
$this->title = $title;
?>
<strong><?= $name ?>：</strong>
<div class="mail-content">
    <p>您好，</p>
    <p><?= $message ?></p>
</div>