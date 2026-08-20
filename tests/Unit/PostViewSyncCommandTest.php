<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Console\InitMigrateCommand;
use App\Console\PostViewSyncCommand;
use App\Post\Post;
use App\Post\PostViewKeys;
use App\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;

final class PostViewSyncCommandTest extends TestCase
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
        $this->post->title = '__sync_test__';
        $this->post->alias = '__sync_' . bin2hex(random_bytes(4));
        $this->post->save();
    }

    protected function tearDown(): void
    {
        $this->post->delete();
        parent::tearDown();
    }

    private function pid(): int
    {
        return (int)$this->post->id;
    }

    public function testSyncsPvDeltaAndUvOverwriteFromPending(): void
    {
        $pid = $this->pid();
        // 累计计数 5，游标 2 → delta 3；pending 标记待同步
        $this->redis->store[PostViewKeys::counterKey($pid)] = '5';
        $this->redis->store[PostViewKeys::syncedKey($pid)] = '2';
        $this->redis->sadd(PostViewKeys::pendingKey(), [(string)$pid]);
        $this->redis->pfadd(PostViewKeys::uvKey($pid), ['dev-1', 'dev-2', 'dev-3', 'dev-4']);

        $exit = (new CommandTester(new PostViewSyncCommand($this->redis)))->execute([]);
        $this->assertSame(ExitCode::OK, $exit);

        $loaded = Post::query()->findByPk($pid);
        $this->assertInstanceOf(Post::class, $loaded);
        $this->assertSame(3, (int)$loaded->view_count); // delta 5-2=3
        $this->assertSame(4, (int)$loaded->view_uv);    // UV 全量 PFCOUNT 覆盖
        $this->assertSame('5', $this->redis->store[PostViewKeys::syncedKey($pid)]);
        // 同步后从 pending 移除
        $this->assertSame([], $this->redis->smembers(PostViewKeys::pendingKey()));
    }

    public function testPostWithoutPendingIsNotProcessed(): void
    {
        $pid = $this->pid();
        // 有计数但无 pending 标记（理论上写路径总会 SADD，此处验证防御）
        $this->redis->store[PostViewKeys::counterKey($pid)] = '7';

        (new CommandTester(new PostViewSyncCommand($this->redis)))->execute([]);

        $loaded = Post::query()->findByPk($pid);
        $this->assertInstanceOf(Post::class, $loaded);
        $this->assertSame(0, (int)$loaded->view_count);
    }

    public function testSecondRunDoesNotDoubleCount(): void
    {
        $pid = $this->pid();
        $this->redis->store[PostViewKeys::counterKey($pid)] = '5';
        $this->redis->store[PostViewKeys::syncedKey($pid)] = '5';
        $this->redis->sadd(PostViewKeys::pendingKey(), [(string)$pid]);
        $this->redis->pfadd(PostViewKeys::uvKey($pid), ['dev-1']);

        $tester = new CommandTester(new PostViewSyncCommand($this->redis));
        $tester->execute([]);
        $tester->execute([]);

        $loaded = Post::query()->findByPk($pid);
        $this->assertInstanceOf(Post::class, $loaded);
        // delta=0，重复执行不累计；UV 覆盖写回同值
        $this->assertSame(0, (int)$loaded->view_count);
        $this->assertSame(1, (int)$loaded->view_uv);
    }

    public function testCleansOrphanKeysOfDeletedPosts(): void
    {
        $orphanId = 99999999;
        $this->redis->store[PostViewKeys::counterKey($orphanId)] = '9';
        $this->redis->pfadd(PostViewKeys::uvKey($orphanId), ['ghost-dev']);
        $this->redis->sadd(PostViewKeys::pendingKey(), [(string)$orphanId]);

        (new CommandTester(new PostViewSyncCommand($this->redis)))->execute([]);

        // 已删除文章：统计 key 被清理，不残留孤儿
        $this->assertArrayNotHasKey(PostViewKeys::counterKey($orphanId), $this->redis->store);
        $this->assertArrayNotHasKey(PostViewKeys::uvKey($orphanId), $this->redis->store);
    }
}
