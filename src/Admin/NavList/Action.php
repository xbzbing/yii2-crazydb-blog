<?php

declare(strict_types=1);

namespace App\Admin\NavList;

use App\Nav\Nav;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 后台导航管理（等价 Yii2 admin NavController::actionIndex）：树形列表。
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
    ) {}

    public function __invoke(): ResponseInterface
    {
        /** @var list<Nav> $navs */
        $navs = Nav::query()->orderBy(['sort_order' => SORT_DESC, 'id' => SORT_ASC])->all();
        $childrenByPid = [];
        foreach ($navs as $nav) {
            $childrenByPid[(int)$nav->pid][] = $nav;
        }

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Admin/layout.php')
            ->render(
                __DIR__ . '/template',
                ['childrenByPid' => $childrenByPid, 'parentIds' => array_keys($childrenByPid)],
            );
    }
}
