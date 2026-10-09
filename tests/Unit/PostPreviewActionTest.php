<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Admin\Api\JsonResponse;
use App\Admin\Api\PostPreview\Action;
use App\Post\MarkdownRenderer;
use App\Post\Post;
use App\Tests\TestCase;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;

/**
 * 后台文章预览 API（GET /admin/api/post/{id}/preview）：
 * 返回与前台同一管线渲染并净化的正文 HTML。
 */
final class PostPreviewActionTest extends TestCase
{
    private Post $post;

    private Action $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new Action(
            new JsonResponse(new ResponseFactory()),
            new MarkdownRenderer(new Cache(new ArrayCache())),
        );
        $this->post = new Post();
        $this->post->title = '__preview_test__';
        $this->post->alias = '__preview_' . bin2hex(random_bytes(4));
        $this->post->status = Post::STATUS_DRAFT;
        $this->post->format = Post::FORMAT_MARKDOWN;
        $this->post->content = '';
        $this->post->save();
    }

    protected function tearDown(): void
    {
        $this->post->delete();
        parent::tearDown();
    }

    /** @return array{post: array<string, mixed>, html: string} */
    private function preview(): array
    {
        $request = (new ServerRequestFactory())->createServerRequest(
            'GET',
            '/admin/api/post/' . $this->post->id . '/preview',
        );
        $response = $this->action->__invoke($request, (int)$this->post->id);
        self::assertSame(200, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertIsArray($payload['data']['post']);
        self::assertIsString($payload['data']['html']);
        return $payload['data'];
    }

    public function testRendersMarkdownWithFrontendPipeline(): void
    {
        $this->post->content = "# 预览标题\n\n这是**加粗**正文。\n\n```php\n<?php echo 1;\n```";
        $this->post->save();

        $data = $this->preview();

        self::assertSame('__preview_test__', $data['post']['title']);
        self::assertSame('markdown', $data['post']['format']);
        self::assertStringContainsString('<h1>预览标题</h1>', $data['html']);
        self::assertStringContainsString('<strong>加粗</strong>', $data['html']);
        // 代码块：highlight.js 兼容输出
        self::assertStringContainsString('<pre><code class="language-php">', $data['html']);
        self::assertStringContainsString('&lt;?php echo 1;', $data['html']);
    }

    public function testStripsScriptFromMarkdownContent(): void
    {
        $this->post->content = "正文前\n\n<script>alert(1)</script>\n\n[链接](https://example.com)";
        $this->post->save();

        $html = $this->preview()['html'];

        self::assertStringNotContainsString('<script', $html);
        self::assertStringContainsString('正文前', $html);
    }

    public function testHtmlLegacyPostPurifiesInPlace(): void
    {
        $this->post->format = Post::FORMAT_HTML;
        $this->post->content = '<p>老文章 <b>正文</b></p><script>alert(1)</script>';
        $this->post->save();

        $html = $this->preview()['html'];

        self::assertStringContainsString('<p>老文章 <b>正文</b></p>', $html);
        self::assertStringNotContainsString('<script', $html);
    }

    public function testReturns404ForMissingPost(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/admin/api/post/99999999/preview');
        $response = $this->action->__invoke($request, 99999999);

        self::assertSame(404, $response->getStatusCode());
    }
}