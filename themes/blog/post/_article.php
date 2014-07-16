<?php
/**
 * 文章列表中的文章list-li
 */
use app\models\Post;
use app\components\XUtils;
use yii\helpers\Url;
/* @var yii\web\View $this */
/* @var Post $post  */
/* @var $category string */
/* @var $extCss string */
?>
<article id="post-<?=$post->id?>" class="list-group-item<?=isset($extCss)?' '.$extCss:''?>">
	<header class="entry-header">
		<h3>
			<a title="<?=$category?>" href="<?=Url::toRoute(['category/view','id'=>$post->cid])?>" class="pl-category">
                <span class="label label-info"><?=$category?></span>
            </a>
            <?php
            if($post->status == Post::STATUS_HIDDEN)
                echo '<span class="label label-warning"><em class="glyphicon glyphicon-lock"></em></span>';
            ?>
			<a class="pl-title" href="<?=$post->getUrl()?>" title="<?=$post->title?>" rel="bookmark">
                <?=$post->title?>
            </a>
		</h3>
	</header>
	<div class="entry-content">
        <?php
        if($post->status==Post::STATUS_HIDDEN){
            echo '<div class="label label-warning">这是一篇隐藏的文章，需要输入密码才能查看全文。</div>';
        }
        echo $post->excerpt;
        ?>
        <div class="pull-right">
        <a href="<?=$post->getUrl()?>" title="<?=$post->title?>" class="read-more">
            <em class="glyphicon glyphicon-new-window"></em><span>阅读全文</span>
        </a></div>
	</div>
	<footer class="entry-footer row">
		<span><i class="glyphicon glyphicon-user"></i>
		<a href="<?= Url::toRoute(['user/alias','name'=>$post->author_name])?>"><?=$post->author_name?></a>
		</span>
		<span><i class="glyphicon glyphicon-time"></i>
			<?=XUtils::XDateFormatter($post->post_time)?>
		</span>
		<span>
			<i class="glyphicon glyphicon-eye-open"></i>
			<?=$post->view_count;?> 浏览
		</span>
		<span>
			<a href="<?=$post->getUrl()?>#comments">
                <span class="badge"><?=$post->comment_count>0?$post->comment_count.' 评论':'抢沙发'?></span>
            </a>
		</span>
	</footer>
</article>