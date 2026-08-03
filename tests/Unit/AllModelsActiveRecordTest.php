<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Category\Category;
use App\Comment\Comment;
use App\Log\Log;
use App\Nav\Nav;
use App\Option\Option;
use App\Post\Post;
use App\Tag\Tag;
use App\Tests\TestCase;
use App\User\User;

/**
 * Smoke test: every table is readable/writable through typed ActiveRecord
 * models. Uses throwaway rows only; requires the seeded MySQL container.
 */
final class AllModelsActiveRecordTest extends TestCase
{
    public function testPostCrud(): void
    {
        $post = new Post();
        $post->title = '__crud_test__';
        $post->alias = '__crud_' . bin2hex(random_bytes(4));
        $post->save();

        $loaded = Post::query()->findByPk($post->id);
        self::assertNotNull($loaded);
        self::assertSame('__crud_test__', $loaded->title);
        $loaded->title = '__crud_test_updated__';
        $loaded->save();
        self::assertSame('__crud_test_updated__', Post::query()->findByPk($post->id)?->title);
        $loaded->delete();
    }

    public function testCategoryCrud(): void
    {
        $category = new Category();
        $category->name = '__crud_test__';
        $category->alias = '__crud_' . bin2hex(random_bytes(4));
        $category->save();

        $loaded = Category::query()->findByPk($category->id);
        self::assertNotNull($loaded);
        self::assertSame('list', $loaded->display);
        $loaded->name = '__crud_test_updated__';
        $loaded->save();
        self::assertSame('__crud_test_updated__', Category::query()->findByPk($category->id)?->name);
        $loaded->delete();
    }

    public function testCommentCrud(): void
    {
        $comment = new Comment();
        $comment->pid = 0;
        $comment->nickname = '__crud_test__';
        $comment->email = '__crud@example.com';
        $comment->save();

        $loaded = Comment::query()->findByPk($comment->id);
        self::assertNotNull($loaded);
        self::assertSame('unapproved', $loaded->status);
        $loaded->delete();
    }

    public function testTagCrud(): void
    {
        $tag = new Tag();
        $tag->name = '__crud_test__';
        $tag->pid = 0;
        $tag->save();

        $loaded = Tag::query()->findByPk($tag->id);
        self::assertNotNull($loaded);
        self::assertSame('__crud_test__', $loaded->name);
        $loaded->delete();
    }

    public function testNavCrud(): void
    {
        $nav = new Nav();
        $nav->name = '__crud_test__';
        $nav->url = '/__crud__';
        $nav->save();

        $loaded = Nav::query()->findByPk($nav->id);
        self::assertNotNull($loaded);
        self::assertSame('/__crud__', $loaded->url);
        $loaded->delete();
    }

    public function testOptionCompositeKeyCrud(): void
    {
        $option = new Option();
        $option->type = 'sys';
        $option->name = '__crud_test__';
        $option->value = 'v1';
        $option->save();

        $loaded = Option::query()->findByPk(['type' => 'sys', 'name' => '__crud_test__']);
        self::assertNotNull($loaded);
        self::assertSame('v1', $loaded->value);
        $loaded->value = 'v2';
        $loaded->save();
        self::assertSame('v2', Option::query()->findByPk(['type' => 'sys', 'name' => '__crud_test__'])?->value);
        $loaded->delete();
    }

    public function testLogCrud(): void
    {
        $log = new Log();
        $log->uid = 0;
        $log->action = '__crud_test__';
        $log->save();

        $loaded = Log::query()->findByPk($log->id);
        self::assertNotNull($loaded);
        self::assertSame('__crud_test__', $loaded->action);
        $loaded->delete();
    }

    public function testReadExistingSeededRecords(): void
    {
        self::assertNotNull(User::query()->findByPk(1));
        self::assertNotNull(Option::query()->findByPk(['type' => 'sys', 'name' => 'site_name']));
    }
}
