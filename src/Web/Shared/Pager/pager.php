<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 分页链接（等价 Yii2 LinkPager 中文标签）。
 *
 * @var App\Web\Pager $pager
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $route 当前列表第一页路由名（如 site/index / category/show）
 * @var string $pageRoute 分页路由名（如 site/index-page / category/show-page）
 * @var array<string, string> $routeArgs 路由参数（如分类 alias、标签 name）
 */
$buildUrl = static fn (int $page): string => $page > 1
    ? $urlGenerator->generate($pageRoute, $routeArgs + ['page' => $page])
    : $urlGenerator->generate($route, $routeArgs);
?>

<?php if ($pager->pageCount > 1): ?>
<nav class="pager">
    <ul>
        <?php if ($pager->hasPrev()): ?>
            <li><a href="<?= Html::encode($buildUrl(1)) ?>">首页</a></li>
            <li><a href="<?= Html::encode($buildUrl($pager->currentPage - 1)) ?>">上一页</a></li>
        <?php endif; ?>
        <?php foreach ($pager->pages() as $page): ?>
            <li<?= $page === $pager->currentPage ? ' class="current"' : '' ?>>
                <?php if ($page === $pager->currentPage): ?>
                    <span><?= $page ?></span>
                <?php else: ?>
                    <a href="<?= Html::encode($buildUrl($page)) ?>"><?= $page ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        <?php if ($pager->hasNext()): ?>
            <li><a href="<?= Html::encode($buildUrl($pager->currentPage + 1)) ?>">下一页</a></li>
            <li><a href="<?= Html::encode($buildUrl($pager->pageCount)) ?>">末页</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
