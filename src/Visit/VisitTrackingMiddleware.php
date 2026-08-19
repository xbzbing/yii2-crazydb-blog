<?php

declare(strict_types=1);

namespace App\Visit;

use App\Common\XUtils;
use App\Option\Option;
use Predis\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Cache\CacheInterface;

/**
 * 前台访问统计中间件：每请求记录 PV（INCR）+ UV（HyperLogLog PFADD），
 * 并按 UA 细分爬虫 / 脚本 / 正常访问（关键词来自 option 配置，默认值兜底）。
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
        private CacheInterface $cache,
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
            // 细分：爬虫 / 脚本 / 正常（关键词来自 option 配置）
            $type = $this->classify($request->getHeaderLine('User-Agent'));
            if ($type === VisitClassifier::TYPE_CRAWLER) {
                $this->redis->incr(VisitKeys::crawlerKey($ymd));
            } elseif ($type === VisitClassifier::TYPE_SCRIPT) {
                $this->redis->incr(VisitKeys::scriptKey($ymd));
            }
        } catch (\Throwable) {
            // 统计失败静默，不影响页面
        }
    }

    /**
     * 按 UA 判定访问类型。关键词配置从 sys option 读取（缺失/空时用默认值）。
     *
     * 关键词经固定键 + 短 TTL 缓存（VisitClassifier::KEYWORDS_CACHE_KEY），
     * 避免每请求触发 CMSUtils::getSiteConfig 的 MAX(update_time) DB 查询（热路径优化）。
     */
    private function classify(string $userAgent): string
    {
        $keywords = $this->keywordOptions();
        $botRaw = $keywords['bot'];
        $scriptRaw = $keywords['script'];
        $botKeywords = $botRaw !== ''
            ? VisitClassifier::parseKeywords($botRaw)
            : VisitClassifier::DEFAULT_BOT_KEYWORDS;
        $scriptKeywords = $scriptRaw !== ''
            ? VisitClassifier::parseKeywords($scriptRaw)
            : VisitClassifier::DEFAULT_SCRIPT_KEYWORDS;
        return VisitClassifier::classify($userAgent, $botKeywords, $scriptKeywords);
    }

    /**
     * 读取两条关键词 option（只取所需字段），固定键缓存 TTL=KEYWORDS_CACHE_TTL。
     * 后台保存配置时（Admin\Api\Config\Action::save）会同步清除该键，保证尽快生效。
     *
     * @return array{bot: string, script: string}
     */
    private function keywordOptions(): array
    {
        /** @var array{bot: string, script: string} $keywords */
        $keywords = $this->cache->getOrSet(
            VisitClassifier::KEYWORDS_CACHE_KEY,
            static function (): array {
                $keywords = ['bot' => '', 'script' => ''];
                /** @var list<Option> $options */
                $options = Option::query()->where(['type' => 'sys'])->all();
                foreach ($options as $option) {
                    if ($option->name === VisitClassifier::OPTION_BOT_KEYWORDS) {
                        $keywords['bot'] = (string)$option->value;
                    } elseif ($option->name === VisitClassifier::OPTION_SCRIPT_KEYWORDS) {
                        $keywords['script'] = (string)$option->value;
                    }
                }
                return $keywords;
            },
            VisitClassifier::KEYWORDS_CACHE_TTL,
        );
        return $keywords;
    }
}
