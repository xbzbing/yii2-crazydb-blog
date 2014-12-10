<?php

use yii\helpers\Url;
use yii\helpers\Html;
use app\components\XUtils;

/**
 * @var yii\web\View $this
 * @var app\models\Post $post
 * @var app\models\User $author
 * @var array $hide_post
 */
$category = $post->category;
$this->title = $post->title;
$this->params['breadcrumbs'][] = ['label' => '文章', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<article id="post-<?php echo $post->id;?>" class="post-view">
    <header class="entry-header">
        <h1>
            <?php echo $post->title;?>
        </h1>
    </header>
    <div class="entry-meta">
        <a href="<?=Url::toRoute(['category/view','id'=>$post->cid])?>" class="pl-category"  title="<?=$category->name?>">
            <span class="label label-info"><?=$category->name?></span>
        </a>
        <?php
        //显示文章标签
        if($post->tags){
            $tags = explode(',',$post->tags);
            $i = 1;
            $c = count($tags);
            echo '<span class="label label-primary">';
            foreach($tags as $tag){
                $tag_url = Url::toRoute(['tag/view','name'=>$tag]);
                echo "<a href=\"{$tag_url}\" title=\"{$tag}\">{$tag}</a>";
                if($i<$c)
                    echo ' / ';
                $i ++;
            }
            echo '</span>';
        }
        ?>
        <span><i class="glyphicon glyphicon-user"></i>
            <?=Html::a($post->author_name,['user/nickname','name'=>$post->author_name],['title'=>$post->author_name])?>
		</span>
		<span><i class="glyphicon glyphicon-time"></i>
            <?=XUtils::XDateFormatter($post->post_time)?>
		</span>
		<span><i class="glyphicon glyphicon-eye-open"></i>
            <?=$post->view_count?> 浏览
		</span>
    </div>
    <div class="entry-content">
        <?=$post->content?>
    </div>
</article>
<div class="form-group">
    <?= Html::a('返回',['post/index'],['class'=>'btn btn-default'])?>
</div>