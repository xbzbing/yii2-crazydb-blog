<?php

declare(strict_types=1);

/**
 * 墨刊文章列表（杂志网格卡片 3 列）。
 *
 * @var Yiisoft\View\WebView $this
 * @var list<App\Post\Post> $posts
 * @var App\Web\Pager $pager
 * @var App\Post\MarkdownRenderer $markdownRenderer
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array<int, array{name: string, desc: ?string, url: ?string, postCount: int}> $categorySummary
 * @var string $emptyText
 * @var string $route
 * @var string $pageRoute
 * @var array<string, string> $routeArgs
 */

if ($posts === []): ?>
    <article class="article-card"><h1><?= \Yiisoft\Html\Html::encode($emptyText) ?></h1></article>
<?php else: ?>
    <div class="article-grid">
        <?php foreach ($posts as $post): ?>
            <?= $this->render('Article/article.php', [
                'post' => $post,
                'category' => $categorySummary[(int)$post->cid] ?? ['name' => '', 'desc' => null, 'url' => null, 'postCount' => 0],
                'urlGenerator' => $urlGenerator,
                'markdownRenderer' => $markdownRenderer,
            ]) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $this->render('Pager/pager.php', [
    'pager' => $pager,
    'urlGenerator' => $urlGenerator,
    'route' => $route,
    'pageRoute' => $pageRoute,
    'routeArgs' => $routeArgs,
]) ?>
