<?php

declare(strict_types=1);

namespace App\Web\Maintenance;

use App\Common\CMSUtils;
use App\Option\Option;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 维护模式中间件：站点状态为「维护中」时，除 /admin（后台+后台 API）
 * 与 /login（管理员登录入口）外的所有前台请求统一返回维护页。
 *
 * 放行 /login 避免死锁：维护模式下管理员需要登录后台才能恢复运行中状态。
 */
final readonly class MaintenanceMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CacheInterface $cache,
        private WebViewRenderer $viewRenderer,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        // 后台 + 后台 API：直接放行（维护期间后台仍可用）
        // 登录页：放行，否则管理员 session 过期后无法登录后台取消维护，形成死锁
        if (str_starts_with($path, '/admin') || $path === '/login' || $path === '/login/') {
            return $handler->handle($request);
        }

        $siteConfig = CMSUtils::getSiteConfig($this->cache);
        if (($siteConfig[Option::SITE_STATUS] ?? Option::STATUS_RUNNING) !== Option::STATUS_MAINTENANCE) {
            return $handler->handle($request);
        }

        return $this->viewRenderer->renderPartial(
            dirname(__DIR__) . '/HomePage/maintenance',
            [
                'siteConfig' => $siteConfig,
                'maintenanceMessage' => (string)($siteConfig[Option::MAINTENANCE_MESSAGE] ?? Option::MAINTENANCE_MESSAGE_DEFAULT),
            ],
        );
    }
}