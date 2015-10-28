<?php
use yii\helpers\Html;
use app\models\Nav;
use app\components\CMSUtils;

/* @var yii\web\View $this */
/* @var Nav[] $parent */
$this->title = '导航';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box nav-index">
    <div class="box-header with-border">
        <h3 class="box-title">导航</h3>
    </div>
    <div class="box-body">
        <table class="table table-bordered table-hover">
            <thead>
            <tr>
                <th width="35">#</th>
                <th colspan="2">名字</th>
                <th>Url</th>
                <th>排序</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <?php
            if($parent){
                foreach ($parent as $node) {
                    echo $this->render('_view', ['model' => $node]);
                    $children = $node->children;
                    foreach ($children as $child)
                        echo $this->render('_view', ['model' => $child]);
                }
            }else
                echo '<tr><td colspan="6">暂无数据</td></tr>';
            ?>
            </tbody>
        </table>
    </div>
    <div class="box-footer">
        <?= Html::a('添加', ['create'], ['class' => 'btn btn-success']) ?>
    </div>
</div>