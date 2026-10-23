<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Post\Post;
use App\Tag\Tag;
use App\Tests\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;

/**
 * 标签删除：无文章关联才可删 + 清理 post.tags 冗余字符串。
 *
 * 夹具自建文章（不再依赖库里的 2484 号文章）：旧写法先插 tag 行、再判文章存在并
 * markTestSkipped，文章不存在时 finally 根本不会执行——每跑一次就漏一行孤儿
 * tag 行，后台标签列表因此虚增文章数（测试标签x 显示 7 篇、实际 0 篇）。
 */
final class TagDeleteTest extends TestCase
{
    private const TAG_NAME = '测试标签x';

    /** 夹具文章（content 显式给空串，兼容 content NOT NULL 的旧库结构） */
    private ?Post $post = null;

    protected function tearDown(): void
    {
        (new Tag())->deleteAll(['name' => self::TAG_NAME]);
        $this->post?->delete();
        $this->post = null;
        parent::tearDown();
    }

    private function createPost(): Post
    {
        $post = new Post();
        $post->cid = 1;
        $post->author_id = 1;
        $post->author_name = 'tester';
        $post->type = Post::TYPE_POST;
        $post->title = '__tag_delete_test__';
        $post->alias = '__tdt_' . bin2hex(random_bytes(4));
        $post->content = '';
        $post->status = Post::STATUS_PUBLISHED;
        $post->post_time = time();
        $post->create_time = time();
        $post->update_time = time();
        $post->save();
        $this->post = $post;
        return $post;
    }

    public function testDeleteByNameCleansPostTags(): void
    {
        $cache = new Cache(new ArrayCache());

        // 准备：给测试文章附加一个临时标签（tag 表 + post.tags 字符串）
        $post = $this->createPost();
        $tag = new Tag();
        $tag->name = self::TAG_NAME;
        $tag->pid = (int)$post->id;
        $tag->cid = 1;
        $tag->create_time = time();
        $tag->save();

        $post->tags = 'php,yii3,迁移,' . self::TAG_NAME;
        $post->save();

        // 残留场景：tag 关联行已删（文章数为 0 可删），但 post.tags 字符串仍含该标签
        (new Tag())->deleteAll(['name' => self::TAG_NAME]);
        Tag::deleteByName(self::TAG_NAME, $cache);

        $post->refresh();
        self::assertStringNotContainsString(self::TAG_NAME, (string)$post->tags, '冗余标签应从 post.tags 清理');
        self::assertStringContainsString('php', (string)$post->tags);
        self::assertStringContainsString('yii3', (string)$post->tags);
        self::assertStringContainsString('迁移', (string)$post->tags);
        self::assertSame(0, (int)Tag::query()->where(['name' => self::TAG_NAME])->count());
    }
}
