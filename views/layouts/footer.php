<?php
use yii\web\View;
use yii\helpers\ArrayHelper;

/**
 * @var View $this
 */
$site_name = ArrayHelper::getValue(Yii::$app->params, 'site_name', Yii::$app->name);
$copyright = ArrayHelper::getValue(Yii::$app->params, 'copyright');
if(!$copyright)
    $copyright = 'Copyright &copy; 2013-' . date('Y');
$site_icp = ArrayHelper::getValue(Yii::$app->params, 'site_icp', '');
$site_analyzer = ArrayHelper::getValue(Yii::$app->params, 'site_analyzer');
?>
<footer id="footer">
    <div class="container more-information">
        <div class="friend-link col-md-3">
        </div>
        <div class="col-md-8">
            <h3>WHAT'S THE NEXT?</h3>
        </div>
    </div>
    <div id="copyright">
        <?= "<span>{$site_name}</span><span>{$copyright}</span><span>{$site_icp}</span>" ?>
        <?php if ($site_analyzer): ?>
            <div class="scriptAnalyzer"><?= $site_analyzer ?></div>
        <?php endif; ?>
    </div>
</footer>
