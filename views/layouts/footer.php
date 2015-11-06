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
            <h3>友情连接</h3>
            <ul>
                <li><a href="http://www.nitscan.com" target="_blank" title="RedTeam - Jack">RedTeam - Jack</a></li>
                <li><a href="http://memoryblade.com" target="_blank" title="Memoryblade - 鱼子的blog">Memoryblade -
                        鱼子的blog</a></li>
            </ul>
        </div>
        <div class="col-md-8">
            <h3>WHAT'S THE NEXT?</h3>
        </div>
    </div>
    <div id="copyright">
        <?= "<span>{$site_name}</span><span>{$copyright}</span><span>{$site_icp}</span>" ?>
        <?php if ($site_analyzer): ?>
            <div class="scriptAnalyzer">
                <?= $site_analyzer ?>
            </div>
        <?php endif; ?>
    </div>
</footer>
