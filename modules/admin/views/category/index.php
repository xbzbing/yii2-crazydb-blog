<?php

use yii\helpers\Html;
use app\models\Category;

/**
 * @var yii\web\View $this
 * @var Category[] $models
 */

$this->title = '分类管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <?= Html::a('增加分类', ['create'], ['class' => 'btn btn-success'])?>
            </div>
            <div class="box-body post-index">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th width="35">#</th>
                        <th colspan="2">名字</th>
                        <th>访问别名</th>
                        <th>排序</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if($models){
                        foreach ($models as $node) {
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
        </div>
    </div>
</div>
