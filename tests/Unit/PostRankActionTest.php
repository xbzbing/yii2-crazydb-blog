<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Admin\Api\JsonResponse;
use App\Admin\Api\PostRank\Action;
use App\Console\InitMigrateCommand;
use App\Post\Post;
use App\Post\PostViewKeys;
use App\Tests\TestCase;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use Symfony\Component\Console\Tester\CommandTester;

final class PostRankActionTest extends TestCase
{
    private static bool $migrated = false;

    private InMemoryRedisStub $redis;

    private Action $action;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$migrated) {
            (new CommandTester(new InitMigrateCommand()))->execute([]);
            self::$migrated = true;
        }
        $this->redis = new InMemoryRedisStub();
        $this->action = new Action(new JsonResponse(new ResponseFactory()), $this->redis);
        $this->post = new Post();
        $this->post->title = '__rank_test__';
        $this->post->alias = '__rank_' . bin2hex(random_bytes(4));
        $this->post->save();
    }

    protected function tearDown(): void
    {
        $this->post->delete();
        parent::tearDown();
    }

    public function testReturnsRankingSortedByViewsDesc(): void
    {
        $pid = (int)$this->post->id;
        $ymd = date('Ymd');
        // 另一篇更热的文章（不存在的 id，验证已删除占位）
        $this->redis->zincrby(PostViewKeys::topKey($ymd), 3, '99999999');
        $this->redis->zincrby(PostViewKeys::topKey($ymd), 1, (string)$pid);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/admin/api/post-rank')
            ->withQueryParams(['day' => 'today']);
        $response = $this->action->__invoke($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['ok']);
        // 按阅读次数降序：已删除文章（99999999）被过滤，只保留存在的文章
        $this->assertCount(1, $data['data']['items']);
        $this->assertSame($pid, $data['data']['items'][0]['post_id']);
        $this->assertSame('__rank_test__', $data['data']['items'][0]['title']);
        $this->assertSame(1, $data['data']['items'][0]['views']);
    }

    public function testRejectsInvalidDay(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/admin/api/post-rank')
            ->withQueryParams(['day' => 'last-week']);
        $response = $this->action->__invoke($request);

        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($data['ok']);
    }

    public function testEmptyRankingReturnsEmptyItems(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/admin/api/post-rank')
            ->withQueryParams(['day' => 'yesterday']);
        $response = $this->action->__invoke($request);

        $data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['ok']);
        $this->assertSame([], $data['data']['items']);
    }

    public function testRankingTruncatedAtTopLimit(): void
    {
        $pid = (int)$this->post->id;
        $ymd = date('Ymd');
        $key = PostViewKeys::topKey($ymd);
        // 25 篇候选：本测试文章 + 24 篇"已删除"占位文章（均不在 post 表，会被过滤）
        $this->redis->zincrby($key, 1, (string)$pid);
        for ($i = 1; $i <= 24; $i++) {
            $this->redis->zincrby($key, 1, (string)(10000000 + $i));
        }

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/admin/api/post-rank')
            ->withQueryParams(['day' => 'today']);
        $response = $this->action->__invoke($request);

        $data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        // stub 与 Predis 一致：ZREVRANGE 0..19 只取前 20 名（25 条 → 20 条）
        $this->assertCount(20, $this->redis->zrevrange($key, 0, 19, ['withscores' => true]));
        // 已删除文章被过滤，仅剩本测试文章
        $this->assertCount(1, $data['data']['items']);
        $this->assertSame($pid, $data['data']['items'][0]['post_id']);
    }
}