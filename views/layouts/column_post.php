<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * @date 14-6-6 下午2:34
 */
/**
 * @var yii\web\View $this
 * @var string $content
 */

use yii\widgets\Breadcrumbs;

$this->beginContent('main');
?>
    <div class="main">
        <div class="container">
            <div class="col-md-12 col-lg-9 with-shadow" id="content" role="main">
                <div class="row breadcrumbs with-shadow">
                    <i class="glyphicon glyphicon-map-marker"></i>
                    <?= Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                </div>
                <?= $content ?>
            </div>
        </div>
    </div>
<?php
$this->endContent();