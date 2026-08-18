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
 * 维护模式中间件：站点状态为「维护中」时，除维护放行路径外的所有前台请求返回维护页。
 *
 * 放行路径（防死锁 + 功能依赖）：
 * - /admin*        后台 + 后台 API
 * - /login         管理员登录入口（session 过期后需登录取消维护）
 * - /tool/captcha  登录验证码（登录表单依赖）
 * - /site/captcha  备用验证码路径
 */
final readonly class MaintenanceMiddleware implements MiddlewareInterface
{
    /** 维护模式下放行的精确路径（/admin 以外） */
    private const PASS_THROUGH_PATHS = ['/login', '/tool/captcha', '/site/captcha'];

    public function __construct(
        private CacheInterface $cache,
        private WebViewRenderer $viewRenderer,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if ($this->isPassThrough($path)) {
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

    /**
     * 判断路径是否在维护模式下放行。
     *
     * /admin 走前缀匹配（覆盖后台 + API），其余路径精确匹配。
     */
    private function isPassThrough(string $path): bool
    {
        if (str_starts_with($path, '/admin')) {
            return true;
        }
        return in_array($path, self::PASS_THROUGH_PATHS, true);
    }
}