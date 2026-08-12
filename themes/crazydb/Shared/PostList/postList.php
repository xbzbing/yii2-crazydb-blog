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

// 批量取作者当前笔名（作者链接/显示统一按用户表 nickname，回退文章快照）
$authorNicknames = [];
if ($posts !== []) {
    $ids = array_values(array_unique(array_map(
        static fn (App\Post\Post $p): int => (int) $p->author_id,
        $posts,
    )));
    $rows = App\User\User::query()->where(['id' => $ids])->select('id,nickname')->asArray()->all();
    foreach ($rows as $row) {
        $authorNicknames[(int) $row['id']] = (string) $row['nickname'];
    }
}

if ($posts === []): ?>
    <div class="list-group-item"><h3><?= \Yiisoft\Html\Html::encode($emptyText) ?></h3></div>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <?= $this->render('Article/article.php', [
            'post' => $post,
            'category' => $categorySummary[(int)$post->cid] ?? ['name' => '', 'desc' => null, 'url' => null, 'postCount' => 0],
            'urlGenerator' => $urlGenerator,
            'markdownRenderer' => $markdownRenderer,
            'authorNickname' => $authorNicknames[(int) $post->author_id] ?? '',
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
