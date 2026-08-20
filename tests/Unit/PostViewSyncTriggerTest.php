<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Console\InitMigrateCommand;
use App\Post\Post;
use App\Post\PostViewKeys;
use App\Post\PostViewSyncTrigger;
use App\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class PostViewSyncTriggerTest extends TestCase
{
    private static bool $migrated = false;

    private InMemoryRedisStub $redis;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$migrated) {
            (new CommandTester(new InitMigrateCommand()))->execute([]);
            self::$migrated = true;
        }
        $this->redis = new InMemoryRedisStub();
        $this->post = new Post();
        $this->post->title = '__trigger_test__';
        $this->post->alias = '__trigger_' . bin2hex(random_bytes(4));
        $this->post->save();
    }

    protected function tearDown(): void
    {
        $this->post->delete();
        parent::tearDown();
    }

    public function testTriggersSyncOfPendingPosts(): void
    {
        $pid = (int)$this->post->id;
        $this->redis->store[PostViewKeys::counterKey($pid)] = '5';
        $this->redis->store[PostViewKeys::syncedKey($pid)] = '2';
        $this->redis->sadd(PostViewKeys::pendingKey(), [(string)$pid]);

        $this->assertTrue((new PostViewSyncTrigger($this->redis))->trigger());

        $loaded = Post::query()->findByPk($pid);
        $this->assertInstanceOf(Post::class, $loaded);
        $this->assertSame(3, (int)$loaded->view_count);
        // 上次同步时间戳已更新（后续降频依据）
        $this->assertGreaterThan(0, (int)$this->redis->store[PostViewSyncTrigger::LAST_SYNC_KEY]);
        // pending 已清空
        $this->assertSame([], $this->redis->smembers(PostViewKeys::pendingKey()));
    }

    public function testDebounceSkipsWithinWindow(): void
    {
        $pid = (int)$this->post->id;
        $this->redis->store[PostViewKeys::counterKey($pid)] = '5';
        $this->redis->store[PostViewKeys::syncedKey($pid)] = '2';
        $this->redis->sadd(PostViewKeys::pendingKey(), [(string)$pid]);
        // 60s 内刚同步过
        $this->redis->store[PostViewSyncTrigger::LAST_SYNC_KEY] = (string)time();

        $this->assertFalse((new PostViewSyncTrigger($this->redis))->trigger());

        $loaded = Post::query()->findByPk($pid);
        $this->assertInstanceOf(Post::class, $loaded);
        $this->assertSame(0, (int)$loaded->view_count); // 未执行同步
    }

    public function testHeldLockSkipsSync(): void
    {
        $pid = (int)$this->post->id;
        $this->redis->store[PostViewKeys::counterKey($pid)] = '5';
        $this->redis->sadd(PostViewKeys::pendingKey(), [(string)$pid]);
        // cron/并行请求已持锁
        $this->redis->store['crazydb:lock:post-view-sync'] = (string)time();

        $this->assertFalse((new PostViewSyncTrigger($this->redis))->trigger());
    }
}