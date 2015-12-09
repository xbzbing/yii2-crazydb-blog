<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\web\View;
use app\components\XUtils;
use app\models\Post;
use app\models\Comment;
use app\assets\AppAsset;

/**
 * @var yii\web\View $this
 * @var app\models\Post $post
 * @var app\models\User $author
 * @var app\models\Category $category
 * @var Comment[] $comments
 * @var array $hide_post
* @var AppAsset $asset
 */
$asset = AppAsset::register($this);
$author = $post->author;
$category = $post->category;
$this->title = $post->title;
$this->params['breadcrumbs'][] = ['label' => '所有文章', 'url' => ['post/list']];
$this->params['breadcrumbs'][] = ['label' => $category->name, 'url' => $category->getUrl()];
$this->params['breadcrumbs'][] = $this->title;
if($post->tags)
	$this->params['seo_keywords'] = $post->tags . ',' . $this->params['seo_keywords'];
$this->params['seo_description'] = $post->excerpt;
?>
    <article id="post-<?= $post->id ?>" class="post-view">
        <header class="entry-header">
            <h1>
                <?= $post->title ?>
            </h1>
        </header>
        <div class="entry-meta">
            <a href="<?= Url::to($post->category->getUrl()) ?>" class="pl-category"
               title="<?= $category->name ?>">
                <span class="label label-info"><?= $category->name ?></span>
            </a>
            <?php
            //显示文章标签
            if ($post->tags) {
                $tags = explode(',', $post->tags);
                $i = 1;
                $c = count($tags);
                echo '<span class="label label-primary">';
                foreach ($tags as $tag) {
                    echo Html::a($tag, ['tag/show', 'name' => $tag], ['title' => $tag]);
                    if ($i < $c)
                        echo ' / ';
                    $i++;
                }
                echo '</span>';
            }
            ?>
            <span><i class="glyphicon glyphicon-user"></i>
                <?= Html::a(Html::encode($post->author_name), ['user/show', 'name' => $post->author->nickname], ['title' => $post->author_name]) ?>
		</span>
		<span><i class="glyphicon glyphicon-time"></i>
            <?= XUtils::XDateFormatter($post->post_time) ?>
		</span>
		<span><i class="glyphicon glyphicon-eye-open"></i>
            <?= $post->view_count ?> 浏览
		</span>
		<span>
			<a href="#comments" title="查看评论"><span class="badge"><?= $post->comment_count ?> 评论</span></a>
		</span>
        </div>
        <div class="entry-content">
            <?php if ($post->post_time <= strtotime('-1 years')): ?>
                <div class="alert alert-warning">
                    <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"> </span>
                    <span>这篇日志发布时间已经超过一年，许多内容可能已经失效，请读者酌情参考。</span>
                </div>
            <?php endif; ?>
            <?php
            if ($post->status == Post::STATUS_HIDDEN) {
                if ($hide_post['passed'])
                    echo $post->content;
                else {
                    echo $post->excerpt;
                    echo '这是要密码的';
//                $this->renderPartial('//widget/hidden-post',
//                    array(
//                        'url'=>$post->getUrl(),
//                        'captcha'=>$hide_post['captcha'],
//                        'pwd'=>$hide_post['pwd'],
//                        'info'=>$hide_post['info']
//                    )
//                );
                }
            } else
                echo $post->content;
            ?>
        </div>

        <footer class="entry-footer row">
            <?php
            $before = $post->getRelatedOne('before');
            $after = $post->getRelatedOne('after');
            if ($after) {
                echo '<h4 class="pull-left"><em class="glyphicon glyphicon-chevron-left"></em>
                <a href="' . $after->getUrl() . '" title="较新的一篇">' . $after->title . '</a>
                </h4>';
            } else {
                echo '<h4 class="pull-left"><small><em class="glyphicon glyphicon-chevron-up"></em> 已是最新的文章</h4></small>';
            }
            if ($before) {
                echo '<h4 class="pull-right">
                <a href="' . $before->getUrl() . '" title="较旧的一篇">' . $before->title . '</a>
                <em class="glyphicon glyphicon-chevron-right"></em>
                </h4>';
            } else {
                echo '<h4 class="pull-right"><small>已是最后一篇文章 <em class="glyphicon glyphicon-chevron-up"></em></small></h4>';
            }

            ?>
        </footer>
    </article>
<?php
echo $this->render('//widget/comment', ['pid' => $post->id, 'comments' => $comments, 'asset' => $asset]);
//优化JS加载
if (empty($post->content) ? false : strpos($post->content, '<pre class=')) {
    $baseUrl = $asset->baseUrl;
    $this->registerCssFile($baseUrl . '/plugins/sh/styles/shCore.css', []);
    $this->registerCssFile($baseUrl . '/plugins/sh/styles/shCoreRDark.css');
    $this->registerJsFile($baseUrl . '/plugins/sh/scripts/shCore.js');
    $this->registerJsFile($baseUrl . '/plugins/sh/scripts/shAutoloader.js');
    $script = <<<SCRIPT
    SyntaxHighlighter.autoloader(
        'java {$baseUrl}/plugins/sh/scripts/shBrushJava.js',
        'php {$baseUrl}/plugins/sh/scripts/shBrushPhp.js',
        'python {$baseUrl}/plugins/sh/scripts/shBrushPython.js',
        'plain {$baseUrl}/plugins/sh/scripts/shBrushPlain.js',
        'html xml {$baseUrl}/plugins/sh/scripts/shBrushXml.js',
        'css {$baseUrl}/plugins/sh/scripts/shBrushCss.js',
        'js jscript javascript {$baseUrl}/plugins/sh/scripts/shBrushJScript.js',
        'bash shell {$baseUrl}/plugins/sh/scripts/shBrushBash.js',
        'sql {$baseUrl}/plugins/sh/scripts/shBrushSql.js'
    );
    SyntaxHighlighter.all();
SCRIPT;
    $this->registerJs($script, View::POS_READY);
}
