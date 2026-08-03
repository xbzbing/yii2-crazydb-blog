<?php

declare(strict_types=1);

namespace App\Admin\CategoryList;

use App\Category\Category;
use App\Web\Pager;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 后台分类管理（等价 Yii2 admin CategoryController::actionIndex）。
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
    ) {}

    public function __invoke(): ResponseInterface
    {
        /** @var list<Category> $categories */
        $categories = Category::query()->orderBy(['sort_order' => SORT_DESC])->all();
        $pager = new Pager(count($categories), count($categories) ?: 1, 1);

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Admin/layout.php')
            ->render(
                __DIR__ . '/template',
                ['categories' => $categories],
            );
    }
}
