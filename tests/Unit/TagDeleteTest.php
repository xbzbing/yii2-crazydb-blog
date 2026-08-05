<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Post\Post;
use App\Tag\Tag;
use App\Tests\TestCase;
use Yiisoft\Cache\CacheInterface;

/**
 * 标签删除：无文章关联才可删 + 清理 post.tags 冗余字符串。
 */
final class TagDeleteTest extends TestCase
{
    public function testDeleteByNameCleansPostTags(): void
    {
        $cache = $this->container()->get(CacheInterface::class);

        // 准备：给测试文章附加一个临时标签（tag 表 + post.tags 字符串）
        $tag = new Tag();
        $tag->name = '测试标签x';
        $tag->pid = 2484;
        $tag->cid = 1;
        $tag->create_time = time();
        $tag->save();

        $post = Post::query()->findByPk(2484);
        if (!$post instanceof Post) {
            self::markTestSkipped('测试文章 2484 不存在');
        }
        $post->tags = 'php,yii3,迁移,测试标签x';
        $post->save();

        try {
            // 残留场景：tag 关联行已删（文章数为 0 可删），但 post.tags 字符串仍含该标签
            (new Tag())->deleteAll(['name' => '测试标签x']);
            Tag::deleteByName('测试标签x', $cache);

            $post->refresh();
            self::assertStringNotContainsString('测试标签x', (string)$post->tags, '冗余标签应从 post.tags 清理');
            self::assertStringContainsString('php', (string)$post->tags);
            self::assertStringContainsString('yii3', (string)$post->tags);
            self::assertStringContainsString('迁移', (string)$post->tags);
            self::assertSame(0, (int)Tag::query()->where(['name' => '测试标签x'])->count());
        } finally {
            // 还原测试文章标签
            $post = Post::query()->findByPk(2484);
            if ($post instanceof Post) {
                $post->tags = 'php,yii3,迁移';
                $post->save();
            }
            (new Tag())->deleteAll(['name' => '测试标签x']);
        }
    }
}
