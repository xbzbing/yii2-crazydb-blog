<?php
use yii\web\View;
use yii\helpers\Html;

/* @var View $this */
$sliders = XUtils::getSiteConfig('slider');
$images = null;
$links = null;
$class = null;
if (!empty($sliders)) {
    foreach ($sliders as $slider) {
        $slider = unserialize($slider);
        if (empty($images))
            $class = ' active';
        else
            $class = '';
        $images .= '<div class="item' . $class . '">' . Html::img($slider['src'], $slider['alt'], array('title' => $slider['title'], 'width' => '270px')) . '</div>';

    }
    ?>
    <div class="widget aside-carousel">
        <h3 class="widget_tit with-shadow"><i class="glyphicon glyphicon-picture"></i>图片</h3>

        <div class="with-shadow">
            <div id="carousel-example-generic" class="carousel slide" data-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    echo $images;
                    ?>
                </div>
                <a class="left carousel-control" href="#carousel-example-generic" data-slide="prev">
                    <span class="glyphicon glyphicon-chevron-left"></span>
                </a>
                <a class="right carousel-control" href="#carousel-example-generic" data-slide="next">
                    <span class="glyphicon glyphicon-chevron-right"></span>
                </a>
            </div>
        </div>
    </div>
<?php } ?>