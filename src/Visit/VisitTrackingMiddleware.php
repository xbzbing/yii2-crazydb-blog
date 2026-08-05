<?php

declare(strict_types=1);

namespace App\Visit;

use App\Common\XUtils;
use Predis\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 前台访问统计中间件：每请求记录 PV（INCR）+ UV（HyperLogLog PFADD）。
 *
 * - 只统计前台 GET 请求：跳过 /admin、/static、/assets、/favicon、/feed、/tool 等
 * - Redis 异常静默（统计失败不阻断业务）
 * - key 前缀 crazydb:visit:，与缓存（crazydbcache_*）隔离
 */
final readonly class VisitTrackingMiddleware implements MiddlewareInterface
{
    /** @var list<string> 不统计的前缀 */
    private const SKIP_PREFIXES = ['/admin', '/static', '/assets', '/favicon', '/feed', '/tool'];

    public function __construct(
        private ClientInterface $redis,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $this->track($request);
        return $response;
    }

    private function track(ServerRequestInterface $request): void
    {
        try {
            $method = $request->getMethod();
            if ($method !== 'GET') {
                return;
            }
            $path = $request->getUri()->getPath();
            if ($path === '') {
                $path = '/';
            }
            foreach (self::SKIP_PREFIXES as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    return;
                }
            }
            $ymd = date('Ymd');
            $ip = XUtils::getClientIP($request->getServerParams());
            // PV：精确计数；UV：HyperLogLog（固定 ~12KB，误差 <1%）
            $this->redis->incr(VisitKeys::pvKey($ymd));
            $this->redis->pfadd(VisitKeys::uvKey($ymd), [$ip]);
        } catch (\Throwable) {
            // 统计失败静默，不影响页面
        }
    }
}
