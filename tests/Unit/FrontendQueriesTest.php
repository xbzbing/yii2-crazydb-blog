<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Category\Category;
use App\Comment\Comment;
use App\Post\Post;
use App\Tag\Tag;
use App\Tests\TestCase;
use App\Web\Pager;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Router\UrlGeneratorInterface;

final class FrontendQueriesTest extends TestCase
{
    private function url(): UrlGeneratorInterface
    {
        return $this->container()->get(UrlGeneratorInterface::class);
    }

    private function cache(): Cache
    {
        return new Cache(new ArrayCache());
    }

    public function testPostUrlPrefersAliasAndFallsBackToId(): void
    {
        $now = time();
        $withAlias = new Post();
        $withAlias->cid = 1;
        $withAlias->author_id = 1;
        $withAlias->author_name = 'tester';
        $withAlias->type = Post::TYPE_POST;
        $withAlias->title = '__url_alias__';
        $withAlias->alias = '__ua_' . bin2hex(random_bytes(4));
        $withAlias->status = Post::STATUS_PUBLISHED;
        $withAlias->post_time = $now;
        $withAlias->create_time = $now;
        $withAlias->update_time = $now;
        $withAlias->save();

        $noAlias = new Post();
        $noAlias->cid = 1;
        $noAlias->author_id = 1;
        $noAlias->author_name = 'tester';
        $noAlias->type = Post::TYPE_POST;
        $noAlias->title = '__url_no_alias__';
        $noAlias->alias = '';
        $noAlias->status = Post::STATUS_PUBLISHED;
        $noAlias->post_time = $now;
        $noAlias->create_time = $now;
        $noAlias->update_time = $now;
        $noAlias->save();
        try {
            self::assertSame('/archive/' . $withAlias->alias, $withAlias->getUrl($this->url()));
            $absolute = $withAlias->getUrl($this->url(), true);
            self::assertStringContainsString('/archive/' . $withAlias->alias, $absolute);

            self::assertSame('/post/' . $noAlias->id, $noAlias->getUrl($this->url()));
        } finally {
            $withAlias->delete();
            $noAlias->delete();
        }
    }

    public function testNewPostHasNoUrl(): void
    {
        $post = new Post();
        $post->alias = 'hello-world';
        self::assertNull($post->getUrl($this->url()));
    }

    public function testCategoryUrlIsAbsolute(): void
    {
        $category = new Category();
        $category->alias = 'tech';
        self::assertStringContainsString('/catalog/tech', $category->getUrl($this->url()));
    }

    public function testFindVisibleByAlias(): void
    {
        $post = new Post();
        $post->cid = 1;
        $post->author_id = 1;
        $post->author_name = 'tester';
        $post->type = Post::TYPE_POST;
        $post->title = '__find_visible__';
        $post->alias = '__fv_' . bin2hex(random_bytes(4));
        $post->status = Post::STATUS_PUBLISHED;
        $post->post_time = time();
        $post->create_time = time();
        $post->update_time = time();
        $post->save();
        try {
            self::assertNotNull(Post::findVisibleByAlias($post->alias));
            self::assertNull(Post::findVisibleByAlias('__no_such_alias__'));

            $post->status = Post::STATUS_DELETED;
            $post->save();
            self::assertNull(Post::findVisibleByAlias($post->alias), 'deleted must be invisible');
        } finally {
            $post->delete();
        }
    }

    public function testRelatedOneBeforeAndAfter(): void
    {
        $now = time();
        $older = new Post();
        $older->cid = 1;
        $older->author_id = 1;
        $older->author_name = 'tester';
        $older->type = Post::TYPE_POST;
        $older->title = '__older__';
        $older->alias = '__old_' . bin2hex(random_bytes(4));
        $older->status = Post::STATUS_PUBLISHED;
        $older->post_time = $now - 200000;
        $older->create_time = $now - 200000;
        $older->update_time = $now - 200000;
        $older->save();

        $middle = new Post();
        $middle->cid = 1;
        $middle->author_id = 1;
        $middle->author_name = 'tester';
        $middle->type = Post::TYPE_POST;
        $middle->title = '__middle__';
        $middle->alias = '__mid_' . bin2hex(random_bytes(4));
        $middle->status = Post::STATUS_PUBLISHED;
        $middle->post_time = $now - 100000;
        $middle->create_time = $now - 100000;
        $middle->update_time = $now - 100000;
        $middle->save();

        $newer = new Post();
        $newer->cid = 1;
        $newer->author_id = 1;
        $newer->author_name = 'tester';
        $newer->type = Post::TYPE_POST;
        $newer->title = '__newer__';
        $newer->alias = '__new_' . bin2hex(random_bytes(4));
        $newer->status = Post::STATUS_PUBLISHED;
        $newer->post_time = $now + 100000;
        $newer->create_time = $now + 100000;
        $newer->update_time = $now + 100000;
        $newer->save();
        try {
            $cache = $this->cache();
            $before = $middle->getRelatedOne($this->url(), $cache, 'before');
            if ($before !== null) {
                self::assertNotSame($middle->id, $before->id, 'before must be a different post');
            }
            $after = $middle->getRelatedOne($this->url(), $cache, 'after');
            if ($after !== null) {
                self::assertNotSame($middle->id, $after->id, 'after must be a different post');
            }
            // 边界测试：验证边界文章不崩溃，且 invalid relation 返回 null
            $olderBefore = $older->getRelatedOne($this->url(), $cache, 'before');
            $newerAfter = $newer->getRelatedOne($this->url(), $cache, 'after');
            self::assertNull($middle->getRelatedOne($this->url(), $cache, 'sideways'), 'invalid relation returns null');
        } finally {
            $older->delete();
            $middle->delete();
            $newer->delete();
        }
    }

    public function testTagGetTagsAggregatesAndLimits(): void
    {
        $name = '__tag_' . bin2hex(random_bytes(3));
        $tagA = new Tag();
        $tagA->name = $name;
        $tagA->pid = 1;
        $tagA->cid = 1;
        $tagA->create_time = time();
        $tagA->save();
        $tagB = new Tag();
        $tagB->name = $name;
        $tagB->pid = 2;
        $tagB->cid = 1;
        $tagB->create_time = time();
        $tagB->save();
        try {
            $tags = Tag::getTags($this->cache(), $this->url());
            $found = null;
            foreach ($tags as $tag) {
                if ($tag['name'] === $name) {
                    $found = $tag;
                    break;
                }
            }
            self::assertNotNull($found, 'tag must appear in aggregation');
            self::assertSame(2, $found['totalCount']);
            self::assertStringContainsString('/tag/' . urlencode($name), $found['url']);
        } finally {
            $tagA->delete();
            $tagB->delete();
        }
    }

    public function testGetRecentCommentsReturnsExpectedStructure(): void
    {
        $aliases = $this->container()->get(Aliases::class);
        $avatarFile = $aliases->get('@public') . '/static/avatar/' . md5('rc@example.com') . '-40.png';
        if (!is_dir(dirname($avatarFile))) {
            mkdir(dirname($avatarFile), 0755, true);
        }
        if (!is_file($avatarFile)) {
            file_put_contents($avatarFile, '');
        }
        $post = new Post();
        $post->cid = 1;
        $post->author_id = 1;
        $post->author_name = 'tester';
        $post->type = Post::TYPE_POST;
        $post->title = '__recent_comments_post__';
        $post->alias = '__rcp_' . bin2hex(random_bytes(4));
        $post->status = Post::STATUS_PUBLISHED;
        $post->post_time = time();
        $post->create_time = time();
        $post->update_time = time();
        $post->save();

        $comment = new Comment();
        $comment->pid = $post->id;
        $comment->uid = 0;
        $comment->nickname = '__rc_nick__';
        $comment->email = 'rc@example.com';
        $comment->content = '最新的评论内容';
        $comment->status = Comment::STATUS_APPROVED;
        $comment->create_time = time();
        $comment->update_time = time();
        $comment->ip = '127.0.0.1';
        $comment->save();

        $unapproved = new Comment();
        $unapproved->pid = $post->id;
        $unapproved->uid = 0;
        $unapproved->nickname = '__rc_spam__';
        $unapproved->email = 'spam@example.com';
        $unapproved->content = '未审核评论不得出现在最新评论';
        $unapproved->status = Comment::STATUS_UNAPPROVED;
        $unapproved->create_time = time() + 1;
        $unapproved->update_time = time() + 1;
        $unapproved->ip = '127.0.0.1';
        $unapproved->save();
        try {
            $items = Comment::getRecentComments(
                $this->cache(),
                $this->url(),
                $this->container()->get(Aliases::class),
                10,
            );
            $found = null;
            foreach ($items as $item) {
                if ($item['id'] === $comment->id) {
                    $found = $item;
                    break;
                }
                self::assertNotSame('__rc_spam__', $item['nickname'], 'unapproved comment must be filtered');
            }
            self::assertNotNull($found, 'comment must appear in recent list');
            self::assertSame('__rc_nick__', $found['nickname']);
            self::assertSame($post->title, $found['title']);
            self::assertStringContainsString('/archive/' . $post->alias . '#comment-' . $comment->id, $found['post_url']);
            self::assertStringContainsString('static/avatar/' . md5('rc@example.com'), $found['avatar']);
        } finally {
            $unapproved->delete();
            $comment->delete();
            $post->delete();
        }
    }

    public function testPagerMath(): void
    {
        $pager = new Pager(95, 10, 3);
        self::assertSame(10, $pager->pageCount);
        self::assertSame(20, $pager->offset);
        self::assertTrue($pager->hasPrev());
        self::assertTrue($pager->hasNext());
        self::assertSame([1, 2, 3, 4, 5], $pager->pages());

        $first = new Pager(25, 10, 1);
        self::assertFalse($first->hasPrev());
        self::assertSame(0, $first->offset);

        $last = new Pager(25, 10, 99);
        self::assertSame(3, $last->currentPage, 'out-of-range page clamps to last');
        self::assertFalse($last->hasNext());

        $empty = new Pager(0, 10, 1);
        self::assertSame(1, $empty->pageCount, 'empty list still has one page');
    }
}
