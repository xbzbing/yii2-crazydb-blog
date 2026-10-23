<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Admin\Api\JsonResponse;
use App\Admin\Api\TagList\Action;
use App\Post\Post;
use App\Tag\Tag;
use App\Tests\TestCase;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;

/**
 * 后台标签管理 API 回归：
 * 标签的「文章数」必须只统计 post 表中仍然存在的文章。
 *
 * tag 行可能成为孤儿（文章已被删除但关联行漏删、测试残留等），
 * 直接 COUNT(tag) 会虚增数量——曾出现「测试标签x 显示 7 篇、实际 0 篇」，
 * 且虚增的计数还会把删除入口一起堵死，垃圾关联行永远清不掉。
 */
final class AdminTagListApiTest extends TestCase
{
    /** 只属于本测试的标签名（共享测试库，须避免与既有标签重名） */
    private const ORPHAN_NAME = '__回归_孤儿标签__';
    private const LINKED_NAME = '__回归_关联标签__';

    /** @var list<Post> 本测试创建的文章，tearDown 删除 */
    private array $createdPosts = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearFixtures();
    }

    protected function tearDown(): void
    {
        $this->clearFixtures();
        parent::tearDown();
    }

    private function clearFixtures(): void
    {
        (new Tag())->deleteAll(['name' => self::ORPHAN_NAME]);
        (new Tag())->deleteAll(['name' => self::LINKED_NAME]);
        foreach ($this->createdPosts as $post) {
            $post->delete();
        }
        $this->createdPosts = [];
    }

    private function action(): Action
    {
        return new Action(new JsonResponse(new ResponseFactory()), new Cache(new ArrayCache()));
    }

    private function request(): ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest('GET', '/admin/api/tags');
    }

    private function insertTag(string $name, int $pid): void
    {
        $tag = new Tag();
        $tag->name = $name;
        $tag->pid = $pid;
        $tag->cid = 1;
        $tag->create_time = time();
        $tag->save();
    }

    private function createPost(): Post
    {
        $post = new Post();
        $post->title = '__tag_list_test__';
        $post->alias = '__tag_list_' . bin2hex(random_bytes(4));
        $post->content = '';
        $post->author_id = 1;
        $post->author_name = 'tester';
        $post->save();
        $this->createdPosts[] = $post;
        return $post;
    }

    /**
     * 调 GET /admin/api/tags 并返回 items。
     *
     * @return list<array{name: string, totalCount: int}>
     */
    private function listItems(): array
    {
        $response = $this->action()->list($this->request());
        self::assertSame(200, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['ok']);
        self::assertIsArray($payload['data']['items']);
        return $payload['data']['items'];
    }

    /** @param list<array{name: string, totalCount: int}> $items */
    private function tagCount(array $items, string $name): ?int
    {
        foreach ($items as $item) {
            if ($item['name'] === $name) {
                return (int)$item['totalCount'];
            }
        }
        return null;
    }

    private function deleteTag(string $name): ResponseInterface
    {
        return $this->action()->delete($this->request(), $name);
    }

    public function testOrphanTagRowsAreCountedAsZeroArticles(): void
    {
        // Arrange：3 行 tag 指向一篇并不存在的文章（复现「显示 7 篇、实际 0 篇」）
        $missingId = (int)Post::query()->max('id') + 10000;
        for ($i = 0; $i < 3; $i++) {
            $this->insertTag(self::ORPHAN_NAME, $missingId);
        }

        // Act
        $items = $this->listItems();

        // Assert：孤儿关联行一篇真实文章都没有
        self::assertSame(
            0,
            $this->tagCount($items, self::ORPHAN_NAME),
            '指向已不存在文章的 tag 行不得计入文章数',
        );
    }

    public function testTagLinkedToExistingPostCountsThatPost(): void
    {
        // Arrange：标签确实挂在一篇现存文章上
        $post = $this->createPost();
        $this->insertTag(self::LINKED_NAME, (int)$post->id);

        // Act
        $items = $this->listItems();

        // Assert：现存文章照常计数（防止修复把有效关联一起过滤掉）
        self::assertSame(1, $this->tagCount($items, self::LINKED_NAME));
    }

    public function testDuplicateRowsForSamePostCountAsOneArticle(): void
    {
        // Arrange：同一篇文章、同一标签的重复关联行只应算 1 篇
        $post = $this->createPost();
        $this->insertTag(self::LINKED_NAME, (int)$post->id);
        $this->insertTag(self::LINKED_NAME, (int)$post->id);

        // Act
        $items = $this->listItems();

        // Assert
        self::assertSame(1, $this->tagCount($items, self::LINKED_NAME));
    }

    public function testDeleteSucceedsWhenNoRealPostLinksTheTag(): void
    {
        // Arrange：只剩孤儿关联行的标签应当可删，否则垃圾行永远清不掉
        $missingId = (int)Post::query()->max('id') + 10000;
        $this->insertTag(self::ORPHAN_NAME, $missingId);
        $this->insertTag(self::ORPHAN_NAME, $missingId);

        // Act
        $response = $this->deleteTag(self::ORPHAN_NAME);

        // Assert
        self::assertSame(200, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['ok'], (string)($payload['error'] ?? ''));
        self::assertSame(0, (int)Tag::query()->where(['name' => self::ORPHAN_NAME])->count());
    }

    public function testDeleteStillRejectedWhileRealPostLinksTheTag(): void
    {
        // Arrange：真实关联仍然挡住删除（原有保护不得回归）
        $post = $this->createPost();
        $this->insertTag(self::LINKED_NAME, (int)$post->id);

        // Act
        $response = $this->deleteTag(self::LINKED_NAME);

        // Assert
        self::assertSame(422, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($payload['ok']);
        self::assertSame(1, (int)Tag::query()->where(['name' => self::LINKED_NAME])->count());
    }
}
