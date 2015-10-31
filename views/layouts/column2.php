<?php
use yii\web\View;
use yii\widgets\Breadcrumbs;

/* @var View $this */
/* @var string $content */
$this->beginContent('@app/views/layouts/main.php');
?>
<div class="main">
    <div class="container">
        <div class="col-md-9 with-shadow post-list" role="main">
            <?php
            //面包屑导航
            if (isset($this->breadcrumbs)) {
                Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]);
            }
            echo $content;
            ?>
        </div>
        <?php $this->render('right-aside'); ?>
    </div>
</div>
<?php $this->endContent(); ?>
