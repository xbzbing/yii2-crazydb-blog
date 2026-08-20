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
            // 确保测试库已应用 V2 列（post.view_uv 等）
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

    public function testSyncsPvDeltaAndUvOverwrite(): void
    {
        $ymd = date('Ymd');
        $pid = (int)$this->post->id;
        $this->redis->store[PostViewKeys::counterKey($pid, $ymd)] = '5';
        $this->redis->store[PostViewKeys::syncedKey($pid, $ymd)] = '2';
        $this->redis->pfadd(PostViewKeys::uvKey($pid), ['dev-1', 'dev-2', 'dev-3', 'dev-4']);

        $exit = (new CommandTester(new PostViewSyncCommand($this->redis)))->execute([]);
        $this->assertSame(ExitCode::OK, $exit);

        $loaded = Post::query()->findByPk($pid);
        $this->assertInstanceOf(Post::class, $loaded);
        $this->assertSame(3, (int)$loaded->view_count); // delta 5-2=3
        $this->assertSame(4, (int)$loaded->view_uv);    // UV 全量 PFCOUNT 覆盖
        $this->assertSame('5', $this->redis->store[PostViewKeys::syncedKey($pid, $ymd)]);
    }

    public function testSecondRunDoesNotDoubleCount(): void
    {
        $ymd = date('Ymd');
        $pid = (int)$this->post->id;
        $this->redis->store[PostViewKeys::counterKey($pid, $ymd)] = '5';
        $this->redis->store[PostViewKeys::syncedKey($pid, $ymd)] = '5';
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

    public function testCleansOrphanUvKeysOfDeletedPosts(): void
    {
        $orphanId = 99999999;
        $key = PostViewKeys::uvKey($orphanId);
        $this->redis->pfadd($key, ['ghost-dev']);

        (new CommandTester(new PostViewSyncCommand($this->redis)))->execute([]);

        // 已删除文章：UV key 被清理，不残留孤儿 HLL
        $this->assertArrayNotHasKey($key, $this->redis->store);
    }
}