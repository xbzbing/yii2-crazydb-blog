<?php
/**
 * @author xbzbing <xbzbing@gmail.com>
 * @link http://www.crazydb.com
 */

use yii\web\View;
/* @var View $this */

echo '<aside class="col-md-3 sidebar" id="right-sidebar">',
$this->render('//widget/aside-about'),
$this->render('//widget/aside-category'),
$this->render('//widget/aside-tags'),
$this->render('//widget/aside-comment'),
'</aside>';