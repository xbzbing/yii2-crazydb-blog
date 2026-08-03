<?php
use yii\web\View;
use yii\widgets\Breadcrumbs;

/**
 * @var View $this
 * @var string $content
 */
$this->beginContent('@app/views/layouts/main.php');
?>
<div class="main">
    <div class="container">
        <div class="col-md-9 list-group post-list no-padding with-shadow">
            <div class="breadcrumbs with-shadow">
                <i class="glyphicon glyphicon-map-marker"></i>
                <?= Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
            </div>
            <div class="content no-padding">
                <?= $content ?>
            </div>
        </div>
        <?= $this->render('right-aside') ?>
    </div>
</div>
<?php $this->endContent(); ?>

