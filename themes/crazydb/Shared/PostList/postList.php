<?php

declare(strict_types=1);

/**
 * Crazydb 主题文章分页列表（容器 .post-list.list-group 由 layout 提供）。
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
    <div class="list-group-item"><h3><?= \Yiisoft\Html\Html::encode($emptyText) ?></h3></div>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <?= $this->render('Article/article.php', [
            'post' => $post,
            'category' => $categorySummary[(int)$post->cid] ?? ['name' => '', 'desc' => null, 'url' => null, 'postCount' => 0],
            'urlGenerator' => $urlGenerator,
            'markdownRenderer' => $markdownRenderer,
        ]) ?>
    <?php endforeach; ?>
<?php endif; ?>

<?= $this->render('Pager/pager.php', [
    'pager' => $pager,
    'urlGenerator' => $urlGenerator,
    'route' => $route,
    'pageRoute' => $pageRoute,
    'routeArgs' => $routeArgs,
]) ?>
