<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 */

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
        <div class="col-md-9 with-shadow" id="content" role="main">
            <div class="row breadcrumbs with-shadow">
                <i class="glyphicon glyphicon-map-marker"></i>
                <?= Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
            </div>
            <?= $content ?>
        </div>
        <?= $this->render('right-aside') ?>
    </div>
</div>
<?php
$this->endContent();