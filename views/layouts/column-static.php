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
        <div class="col-md-9 main-container with-shadow">
            <div class="breadcrumbs with-shadow">
                <i class="glyphicon glyphicon-map-marker"></i>
                <?= Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
            </div>
            <?= $content ?>
        </div>
        <?= '<aside class="col-md-3 sidebar" id="right-sidebar">',
        $this->render('//widget/aside-about'),
        $this->render('//widget/aside-category'),
        $this->render('//widget/aside-comment'),
        '</aside>'; ?>
    </div>
</div>
<?php $this->endContent(); ?>

