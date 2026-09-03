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
 * V3 变更（vs V2）：
 * - 4xx/5xx 响应不进主 PV/UV/IP/分类/小时统计，也不再追加 dbvid cookie
 * - 404 响应单独计数（404pv/404uv，UV 按去重来源 IP），供疑似扫描器监控；
 *   仅「今日/昨日」实时卡片读取，不落库，key 自带 7 天 TTL 自动过期
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
        /** 强制 dbvid cookie 带 Secure（生产 https 部署开启，见 COOKIE_SECURE） */
        private bool $cookieSecure = false,
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

        // 错误响应（4xx/5xx）不进主统计；404 单独计数（疑似扫描器监控）。
        // 注意：失败路径不追加 dbvid cookie——避免给扫描器种 cookie，也让 404uv 只能用 IP 维度度量。
        $status = $response->getStatusCode();
        if ($status >= 400) {
            if ($status === 404) {
                $this->trackNotFound($request);
            }
            return $response;
        }

        // 若为新生成的设备 ID，向响应追加 Set-Cookie（必须在 handle 之后，需要 response）
        // 用 withAddedHeader 追加而非 withHeader 替换，避免覆盖 Session/RememberMe 已设置的 cookie
        if ($needsCookie) {
            $response = $response->withAddedHeader('Set-Cookie', DeviceId::cookieValue($deviceId, $request, $this->cookieSecure));
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
     * 统计写入合并为单次 Redis pipeline（一次 RTT），少量丢失可接受。
     */
    private function track(ServerRequestInterface $request, string $type, string $deviceId): void
    {
        try {
            $ymd = date('Ymd');
            $ymdH = date('YmdH');
            $ip = XUtils::getClientIP($request->getServerParams());
            $isNormal = ($type === VisitClassifier::TYPE_NORMAL);

            $pvKey = VisitKeys::pvKey($ymd);
            $ipKey = VisitKeys::ipKey($ymd);
            $uvKey = VisitKeys::uvKey($ymd);
            $datesKey = VisitKeys::datesKey();
            $typeKey = match ($type) {
                VisitClassifier::TYPE_CRAWLER => VisitKeys::crawlerKey($ymd),
                VisitClassifier::TYPE_SCRIPT => VisitKeys::scriptKey($ymd),
                default => '',
            };
            $pvHourKey = VisitKeys::pvHourKey($ymdH);
            $ipHourKey = VisitKeys::ipHourKey($ymdH);
            $uvHourKey = VisitKeys::uvHourKey($ymdH);

            /** @var \Predis\Client $client */
            $client = $this->redis;
            // Predis Pipeline 的 __call 无返回类型标注，psalm 无法识别其命令方法；
            // Predis Pipeline 的 __call 无返回类型标注，psalm 无法识别其命令方法
            // （UndefinedMagicMethod），运行时命令经 __call 转发，行为与 Client 一致。
            $client->pipeline(
            /** @param \Predis\Pipeline\Pipeline $pipe */
            static function ($pipe) use (
                $pvKey,
                $ipKey,
                $uvKey,
                $datesKey,
                $ymd,
                $typeKey,
                $pvHourKey,
                $ipHourKey,
                $uvHourKey,
                $ip,
                $deviceId,
                $isNormal,
            ): void {
                /** @psalm-suppress UndefinedMagicMethod Predis Pipeline 命令走 __call */
                $pipe->incr($pvKey);
                /** @psalm-suppress UndefinedMagicMethod */
                $pipe->sadd($datesKey, [$ymd]);
                /** @psalm-suppress UndefinedMagicMethod */
                $pipe->pfadd($ipKey, [$ip]);
                // 日 UV（仅正常访问）
                if ($isNormal && $deviceId !== '') {
                    /** @psalm-suppress UndefinedMagicMethod */
                    $pipe->pfadd($uvKey, [$deviceId]);
                }
                // 分类 PV
                if ($typeKey !== '') {
                    /** @psalm-suppress UndefinedMagicMethod */
                    $pipe->incr($typeKey);
                }
                // 小时 PV + IP（全部访问）
                /** @psalm-suppress UndefinedMagicMethod */
                $pipe->incr($pvHourKey);
                /** @psalm-suppress UndefinedMagicMethod */
                $pipe->expire($pvHourKey, VisitKeys::HOUR_TTL);
                /** @psalm-suppress UndefinedMagicMethod */
                $pipe->pfadd($ipHourKey, [$ip]);
                /** @psalm-suppress UndefinedMagicMethod */
                $pipe->expire($ipHourKey, VisitKeys::HOUR_TTL);
                // 小时 UV（仅正常访问）
                if ($isNormal && $deviceId !== '') {
                    /** @psalm-suppress UndefinedMagicMethod */
                    $pipe->pfadd($uvHourKey, [$deviceId]);
                    /** @psalm-suppress UndefinedMagicMethod */
                    $pipe->expire($uvHourKey, VisitKeys::HOUR_TTL);
                }
            });
        } catch (\Throwable) {
            // 统计失败静默，不影响页面
        }
    }

    /**
     * 404 响应单独计数（疑似扫描器监控）：日 PV（INCR）+ 日独立来源 IP（HLL）。
     *
     * 与主统计完全隔离：不写 pv/uv/ip/分类/小时桶，也不生成 dbvid cookie。
     * UV 按去重来源 IP 度量（4xx 不种 cookie，无法做设备级去重）；IP 维度对
     * 扫描告警最可操作（拦截/封禁按 IP）。
     * 仅供「今日/昨日」实时卡片读取（VisitService::today/yesterday），不落库不参与 visit/sync；
     * key 自带 NOTFOUND_TTL（7 天）自动过期，无需清理任务。
     * 写入合并为单次 Redis pipeline（一次 RTT），少量丢失可接受。
     */
    private function trackNotFound(ServerRequestInterface $request): void
    {
        try {
            $ymd = date('Ymd');
            $ip = XUtils::getClientIP($request->getServerParams());
            $pvKey = VisitKeys::notFoundPvKey($ymd);
            $uvKey = VisitKeys::notFoundUvKey($ymd);

            /** @var \Predis\Client $client */
            $client = $this->redis;
            $client->pipeline(
            /** @param \Predis\Pipeline\Pipeline $pipe */
            static function ($pipe) use ($pvKey, $uvKey, $ip): void {
                /** @psalm-suppress UndefinedMagicMethod Predis Pipeline 命令走 __call */
                $pipe->incr($pvKey);
                /** @psalm-suppress UndefinedMagicMethod */
                $pipe->expire($pvKey, VisitKeys::NOTFOUND_TTL);
                /** @psalm-suppress UndefinedMagicMethod */
                $pipe->pfadd($uvKey, [$ip]);
                /** @psalm-suppress UndefinedMagicMethod */
                $pipe->expire($uvKey, VisitKeys::NOTFOUND_TTL);
            });
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
