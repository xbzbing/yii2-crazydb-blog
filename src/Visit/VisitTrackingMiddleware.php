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
 * 前台访问统计中间件（V2）：
 *
 * V2 变更（vs V1）：
 * - UV 改按设备 ID（dbvid cookie），不再按 IP
 * - 新增 IP 维度（全部访问含爬虫/脚本）
 * - 新增小时级桶（pv1h / uv1h / ip1h，TTL 48h）
 * - 爬虫/脚本：只计 PV 分类，不计 UV/IP
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
        // 仅对需要统计的前台 GET 请求执行分类
        if (!$this->shouldTrack($request)) {
            return $handler->handle($request);
        }

        // 分类（判断正常/爬虫/脚本，决定是否生成 device_id 与 UV）
        $type = $this->classify($request->getHeaderLine('User-Agent'));
        $isNormal = ($type === VisitClassifier::TYPE_NORMAL);
        $needsCookie = false;

        if ($isNormal) {
            // 正常访问：提前解析/生成 device_id，写入 request attribute（handle 之前）
            $res = DeviceId::resolve($request);
            $deviceId = $res['id'];
            $needsCookie = $res['needsCookie'];
            $request = $request->withAttribute('device_id', $deviceId);
        } else {
            $deviceId = '';
        }

        // 处理请求（PostShow 等下游在此读取 device_id attribute）
        $response = $handler->handle($request);

        // 若为新生成的设备 ID，向响应追加 Set-Cookie（必须在 handle 之后，需要 response）
        // 用 withAddedHeader 追加而非 withHeader 替换，避免覆盖 Session/RememberMe 已设置的 cookie
        if ($needsCookie) {
            $response = $response->withAddedHeader('Set-Cookie', DeviceId::cookieValue($deviceId, $request));
        }

        $this->track($request, $type, $deviceId);
        return $response;
    }

    /**
     * 是否需要统计：前台 GET + 非 skip 前缀（跳过 admin/static/assets/favicon/feed/tool）。
     */
    private function shouldTrack(ServerRequestInterface $request): bool
    {
        if ($request->getMethod() !== 'GET') {
            return false;
        }
        $path = $request->getUri()->getPath();
        if ($path === '') {
            $path = '/';
        }
        foreach (self::SKIP_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 分类统计：PV（全部）+ UV/IP（仅正常）+ 分类 PV + 小时桶。
     * $deviceId 仅正常访问传入，非正常传空字符串。
     */
    private function track(ServerRequestInterface $request, string $type, string $deviceId): void
    {
        try {
            $ymd = date('Ymd');
            $ymdH = date('YmdH');
            $ip = XUtils::getClientIP($request->getServerParams());
            $isNormal = ($type === VisitClassifier::TYPE_NORMAL);

            // 日 PV（全部访问）
            $this->redis->incr(VisitKeys::pvKey($ymd));
            // 日 IP（全部访问）
            $this->redis->pfadd(VisitKeys::ipKey($ymd), [$ip]);
            // 日 UV（仅正常访问）
            if ($isNormal && $deviceId !== '') {
                $this->redis->pfadd(VisitKeys::uvKey($ymd), [$deviceId]);
            }
            // 分类 PV
            if ($type === VisitClassifier::TYPE_CRAWLER) {
                $this->redis->incr(VisitKeys::crawlerKey($ymd));
            } elseif ($type === VisitClassifier::TYPE_SCRIPT) {
                $this->redis->incr(VisitKeys::scriptKey($ymd));
            }
            // 小时 PV + IP（全部访问）
            $pvHourKey = VisitKeys::pvHourKey($ymdH);
            $this->redis->incr($pvHourKey);
            $this->redis->expire($pvHourKey, VisitKeys::HOUR_TTL);
            $ipHourKey = VisitKeys::ipHourKey($ymdH);
            $this->redis->pfadd($ipHourKey, [$ip]);
            $this->redis->expire($ipHourKey, VisitKeys::HOUR_TTL);
            // 小时 UV（仅正常访问）
            if ($isNormal && $deviceId !== '') {
                $uvHourKey = VisitKeys::uvHourKey($ymdH);
                $this->redis->pfadd($uvHourKey, [$deviceId]);
                $this->redis->expire($uvHourKey, VisitKeys::HOUR_TTL);
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
