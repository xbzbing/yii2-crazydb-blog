<?php

declare(strict_types=1);

namespace App\Admin\LogList;

use App\Log\Log;
use App\Web\Pager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 后台日志查看：列表 + 类型筛选 + 删除（等价 Yii2 admin LogController::actionIndex）。
 */
final readonly class Action
{
    private const PAGE_SIZE = 20;

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private FlashInterface $flash,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] ?string $page = null,
    ): ResponseInterface {
        $page = (int)($page ?? $request->getQueryParams()['page'] ?? 1);
        $type = (string)($request->getQueryParams()['type'] ?? '');

        if ($request->getMethod() === Method::POST && $type === '') {
            (new Log())->deleteAll();
            $this->flash->set('flash_success', ['info' => '日志已清空。']);
            return $this->responseFactory
                ->createResponse(Status::FOUND)
                ->withHeader('Location', $this->urlGenerator->generate('admin/log/list'));
        }

        $countQuery = Log::query();
        if ($type !== '') {
            $countQuery->where(['type' => $type]);
        }
        $pager = new Pager((int)$countQuery->count(), self::PAGE_SIZE, $page);

        $query = Log::query();
        if ($type !== '') {
            $query->where(['type' => $type]);
        }
        /** @var list<Log> $logs */
        $logs = $query
            ->orderBy(['create_time' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(self::PAGE_SIZE)
            ->offset($pager->offset)
            ->all();

        /** @var list<array{type: string}> $typeRows */
        $typeRows = Log::query()->select('type')->distinct()->asArray()->all();

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Admin/layout.php')
            ->render(
                __DIR__ . '/template',
                ['logs' => $logs, 'pager' => $pager, 'type' => $type, 'types' => array_column($typeRows, 'type')],
            );
    }
}
