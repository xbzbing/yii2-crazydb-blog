<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Crazydb 主题分页（还原线上 panel-footer + Bootstrap 5 pagination）。
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
<div class="pager-footer">
    <nav>
        <ul class="pagination justify-content-center mb-0">
            <?php if ($pager->hasPrev()): ?>
                <li class="page-item"><a class="page-link" href="<?= Html::encode($buildUrl(1)) ?>">首页</a></li>
                <li class="page-item"><a class="page-link" href="<?= Html::encode($buildUrl($pager->currentPage - 1)) ?>">上一页</a></li>
            <?php endif; ?>
            <?php foreach ($pager->pages() as $page): ?>
                <li class="page-item<?= $page === $pager->currentPage ? ' active' : '' ?>">
                    <?php if ($page === $pager->currentPage): ?>
                        <span class="page-link"><?= $page ?></span>
                    <?php else: ?>
                        <a class="page-link" href="<?= Html::encode($buildUrl($page)) ?>"><?= $page ?></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            <?php if ($pager->hasNext()): ?>
                <li class="page-item"><a class="page-link" href="<?= Html::encode($buildUrl($pager->currentPage + 1)) ?>">下一页</a></li>
                <li class="page-item"><a class="page-link" href="<?= Html::encode($buildUrl($pager->pageCount)) ?>">末页</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
<?php endif; ?>
