<?php
use yii\web\View;
/**
 * @var View $this
 * @var string $content
 */
$this->beginContent('@app/views/layouts/main.php');
?>
<div class="main">
    <div class="container">
        <div class="col-md-9 list-group post-list no-padding with-shadow">
            <?= $content ?>
        </div>
        <?= $this->render('right-aside'); ?>
    </div>
</div>
<?php $this->endContent(); ?>