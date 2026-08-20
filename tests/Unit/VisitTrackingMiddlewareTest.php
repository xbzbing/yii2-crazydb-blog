<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;
use App\Visit\DeviceId;
use App\Visit\VisitKeys;
use App\Visit\VisitTrackingMiddleware;
use HttpSoft\Message\ServerRequestFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Predis\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Cache\CacheInterface;

/**
 * 中间件 → 下游（PostShow 等）的接线集成测试：
 * - 正常访问：handle 前写入 device_id attribute（下游可读）、handle 后追加 Set-Cookie
 * - 爬虫/脚本：不生成 cookie、不写 UV、仍写 IP 与分类 PV
 * - 非前台 GET（POST / admin / static）：完全跳过，不产生任何 Redis 写入
 */
#[AllowMockObjectsWithoutExpectations]
final class VisitTrackingMiddlewareTest extends TestCase
{
    private CacheInterface $cache;

    protected function setUp(): void
    {
        parent::setUp();
        // 关键词配置缓存桩：返回空关键词（全部判定为正常访问）
        $this->cache = $this->createMock(CacheInterface::class);
        $this->cache->method('getOrSet')->willReturnCallback(
            static fn (mixed $key, callable $callable, mixed $ttl = null, mixed $dep = null): mixed => $callable(),
        );
    }

    // ── 正常访问 ──────────────────────────────────────────────────────────

    public function testNormalVisitSetsAttributeAndCookieAndWritesUv(): void
    {
        $ymd = date('Ymd');
        $ymdH = date('YmdH');

        $handler = new CapturingHandler();
        $redis = new RecordingRedisStub();
        $response = $this->middleware($redis)->process($this->request(), $handler);

        // 下游（PostShow 等）能读到 device_id attribute（handle 前写入）
        $this->assertNotNull($handler->deviceId);
        $this->assertMatchesRegularExpression('/^[0-9A-Za-z]{40}$/', (string) $handler->deviceId);

        // 响应携带 dbvid Set-Cookie（handle 后追加）
        $cookies = $response->getHeader('Set-Cookie');
        $this->assertCount(1, $cookies);
        $this->assertStringContainsString('dbvid=', $cookies[0]);

        // 日 UV + 日 IP + 日 PV
        $this->assertCalled($redis, 'pfadd', VisitKeys::uvKey($ymd));
        $this->assertCalled($redis, 'pfadd', VisitKeys::ipKey($ymd));
        $this->assertCalled($redis, 'incr', VisitKeys::pvKey($ymd));
        // 小时 UV + IP + PV（含 EXPIRE 续期）
        $this->assertCalled($redis, 'pfadd', VisitKeys::uvHourKey($ymdH));
        $this->assertCalled($redis, 'pfadd', VisitKeys::ipHourKey($ymdH));
        $this->assertCalled($redis, 'incr', VisitKeys::pvHourKey($ymdH));
        $this->assertCalled($redis, 'expire', VisitKeys::uvHourKey($ymdH));
        // 正常访问不写分类 PV
        $this->assertNotCalled($redis, 'incr', VisitKeys::crawlerKey($ymd));
        $this->assertNotCalled($redis, 'incr', VisitKeys::scriptKey($ymd));
    }

    public function testExistingValidDeviceIdCookieIsReusedWithoutNewSetCookie(): void
    {
        $existing = DeviceId::generate();
        $handler = new CapturingHandler();
        $redis = new RecordingRedisStub();
        $response = $this->middleware($redis)->process(
            $this->request()->withCookieParams([DeviceId::NAME => $existing]),
            $handler,
        );

        $this->assertSame($existing, $handler->deviceId);
        $this->assertSame([], $response->getHeader('Set-Cookie'));
    }

    public function testTamperedDeviceIdCookieIsReplaced(): void
    {
        $tampered = substr(DeviceId::generate(), 0, -1) . 'z';
        $handler = new CapturingHandler();
        $redis = new RecordingRedisStub();
        $response = $this->middleware($redis)->process(
            $this->request()->withCookieParams([DeviceId::NAME => $tampered]),
            $handler,
        );

        $this->assertNotSame($tampered, $handler->deviceId);
        $this->assertCount(1, $response->getHeader('Set-Cookie'));
    }

    // ── 爬虫 / 脚本 ───────────────────────────────────────────────────────

    public function testCrawlerGetsNoCookieNoUvButStillCountsIpAndCrawlerPv(): void
    {
        $ymd = date('Ymd');
        $ymdH = date('YmdH');
        $ua = 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)';

        $handler = new CapturingHandler();
        $redis = new RecordingRedisStub();
        $response = $this->middleware($redis)->process($this->request()->withHeader('User-Agent', $ua), $handler);

        // 爬虫：无 attribute、无 Set-Cookie
        $this->assertNull($handler->deviceId);
        $this->assertSame([], $response->getHeader('Set-Cookie'));
        // UV 不计（日 + 小时）
        $this->assertNotCalled($redis, 'pfadd', VisitKeys::uvKey($ymd));
        $this->assertNotCalled($redis, 'pfadd', VisitKeys::uvHourKey($ymdH));
        // IP 与分类 PV 仍计
        $this->assertCalled($redis, 'pfadd', VisitKeys::ipKey($ymd));
        $this->assertCalled($redis, 'pfadd', VisitKeys::ipHourKey($ymdH));
        $this->assertCalled($redis, 'incr', VisitKeys::crawlerKey($ymd));
    }

    public function testScriptGetsNoCookieNoUvAndCountsScriptPv(): void
    {
        $ymd = date('Ymd');
        $ua = 'curl/7.68.0';

        $handler = new CapturingHandler();
        $redis = new RecordingRedisStub();
        $response = $this->middleware($redis)->process($this->request()->withHeader('User-Agent', $ua), $handler);

        $this->assertNull($handler->deviceId);
        $this->assertSame([], $response->getHeader('Set-Cookie'));
        $this->assertNotCalled($redis, 'pfadd', VisitKeys::uvKey($ymd));
        $this->assertCalled($redis, 'incr', VisitKeys::scriptKey($ymd));
    }

    // ── 跳过路径 ──────────────────────────────────────────────────────────

    public function testPostRequestIsNotTrackedAtAll(): void
    {
        $handler = new CapturingHandler();
        $redis = new RecordingRedisStub();
        $this->middleware($redis)->process(
            $this->request()->withMethod('POST'),
            $handler,
        );
        $this->assertSame([], $redis->calls);
    }

    public function testAdminAndStaticPathsAreNotTrackedAtAll(): void
    {
        foreach (['/admin', '/static/avatar/x.png', '/assets/app.js'] as $path) {
            $redis = new RecordingRedisStub();
            $this->middleware($redis)->process(
                (new ServerRequestFactory())->createServerRequest('GET', 'http://localhost' . $path),
                new CapturingHandler(),
            );
            $this->assertSame([], $redis->calls, "路径 {$path} 不应产生统计写入");
        }
    }

    // ── Set-Cookie 追加 / Redis 异常 ──────────────────────────────────────

    public function testExistingSetCookieHeaderIsPreserved(): void
    {
        // Session/RememberMe 等外层中间件可能已设置 Set-Cookie，dbvid 必须追加而非替换
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new \HttpSoft\Message\ResponseFactory())
                    ->createResponse()
                    ->withAddedHeader('Set-Cookie', 'PHPSESSID=abc123; Path=/');
            }
        };

        $redis = new RecordingRedisStub();
        $response = $this->middleware($redis)->process($this->request(), $handler);

        $cookies = $response->getHeader('Set-Cookie');
        $this->assertCount(2, $cookies);
        $this->assertStringContainsString('PHPSESSID=abc123', $cookies[0]);
        $this->assertStringContainsString('dbvid=', $cookies[1]);
    }

    public function testRedisFailureIsSilent(): void
    {
        $handler = new CapturingHandler();
        $response = (new VisitTrackingMiddleware(new FailingRedisStub(), $this->cache))
            ->process($this->request(), $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull($handler->deviceId);
    }

    // ── 辅助 ──────────────────────────────────────────────────────────────

    private function middleware(RecordingRedisStub $redis): VisitTrackingMiddleware
    {
        return new VisitTrackingMiddleware($redis, $this->cache);
    }

    private function request(): ServerRequestInterface
    {
        return (new ServerRequestFactory())
            ->createServerRequest('GET', 'http://localhost/')
            ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0 Safari/537.36');
    }

    private function assertCalled(RecordingRedisStub $redis, string $method, string $key): void
    {
        foreach ($redis->calls as $call) {
            if ($call[0] === $method && $call[1][0] === $key) {
                $this->addToAssertionCount(1);
                return;
            }
        }
        $this->fail("期望 Redis {$method}({$key}) 被调用，实际调用：" . $redis->dump());
    }

    private function assertNotCalled(RecordingRedisStub $redis, string $method, string $key): void
    {
        foreach ($redis->calls as $call) {
            if ($call[0] === $method && $call[1][0] === $key) {
                $this->fail("期望 Redis {$method}({$key}) 未被调用，实际调用：" . $redis->dump());
            }
        }
        $this->addToAssertionCount(1);
    }
}

// ── 测试桩 ────────────────────────────────────────────────────────────────

/** 记录所有 Redis 调用的 Predis 桩 */
final class RecordingRedisStub implements ClientInterface
{
    /** @var list<array{0: string, 1: list<mixed>}> */
    public array $calls = [];

    /**
     * @param string $method
     * @param list<mixed> $arguments
     */
    public function __call($method, $arguments): mixed
    {
        // pipeline(callable)：执行闭包，内部命令经 recorder 写入同一 calls 数组
        if ($method === 'pipeline' && isset($arguments[0]) && $arguments[0] instanceof \Closure) {
            $pipe = new RecordingPipelineStub($this->calls);
            ($arguments[0])($pipe);
            return [];
        }
        $this->calls[] = [(string)$method, array_values((array)$arguments)];
        return 1;
    }

    public function getProfile(): never
    {
        throw new \LogicException('not used in tests');
    }

    public function getCommandFactory(): never
    {
        throw new \LogicException('not used in tests');
    }

    public function getOptions(): never
    {
        throw new \LogicException('not used in tests');
    }

    public function connect(): void
    {
    }

    public function disconnect(): void
    {
    }

    public function getConnection(): never
    {
        throw new \LogicException('not used in tests');
    }

    /**
     * @param string $method
     * @param list<mixed> $arguments
     */
    public function createCommand($method, $arguments = []): never
    {
        throw new \LogicException('not used in tests');
    }

    public function executeCommand(\Predis\Command\CommandInterface $command): never
    {
        throw new \LogicException('not used in tests');
    }

    public function dump(): string
    {
        $parts = [];
        foreach ($this->calls as $call) {
            $parts[] = $call[0] . '(' . ($call[1][0] ?? '') . ')';
        }
        return $parts === [] ? '(无)' : implode(', ', $parts);
    }
}

/** pipeline 内部命令记录桩（把命令追加到共享 calls 数组） */
final class RecordingPipelineStub
{
    /**
     * 外部 stub 的共享 calls 数组（经引用传回），本类只写不读，由外部测试断言读取。
     *
     * @var list<array{0: string, 1: list<mixed>}>
     * @phpstan-ignore property.onlyWritten
     */
    private array $calls = [];

    /**
     * @param list<array{0: string, 1: list<mixed>}> $calls
     */
    public function __construct(array &$calls)
    {
        $this->calls = &$calls;
    }

    /**
     * @param string $method
     * @param list<mixed> $arguments
     */
    public function __call($method, $arguments): static
    {
        $this->calls[] = [(string)$method, array_values((array)$arguments)];
        return $this;
    }
}

/** 捕获下游看到的 device_id attribute（模拟 PostShow 读取行为） */
final class CapturingHandler implements RequestHandlerInterface
{
    public ?string $deviceId = null;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $deviceId = $request->getAttribute('device_id');
        $this->deviceId = is_string($deviceId) ? $deviceId : null;
        return (new \HttpSoft\Message\ResponseFactory())->createResponse();
    }
}
