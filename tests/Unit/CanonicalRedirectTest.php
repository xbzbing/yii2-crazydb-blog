<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Category\Category;
use App\Post\Post;
use App\Tests\TestCase;
use App\Web\CategoryShow\Action as CategoryShowAction;
use App\Web\PostShow\Action as PostShowAction;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * SEO 规范 URL 301：/post/{id} → /archive/{alias}、/category/{id} → /catalog/{alias}，
 * 合并双入口的重复内容信号（无 alias 时保持原地渲染）。
 */
final class CanonicalRedirectTest extends TestCase
{
    private Post $post;
    private Post $postNoAlias;
    private Category $category;

    private function renderer(): WebViewRenderer
    {
        // console 测试容器无 web 层定义：仿 LoginOtpTest 手工组装
        return new WebViewRenderer(
            new ResponseFactory(),
            new \HttpSoft\Message\StreamFactory(),
            $this->container()->get(Aliases::class),
            $this->container()->get(\Yiisoft\View\WebView::class),
            viewPath: dirname(__DIR__, 2) . '/src/Web',
            layout: null,
        );
    }

    private function postAction(): PostShowAction
    {
        $container = $this->container();
        return new PostShowAction(
            $this->renderer(),
            $container->get(UrlGeneratorInterface::class),
            new ResponseFactory(),
            new Cache(new ArrayCache()),
            $container->get(\Predis\ClientInterface::class),
            $container->get(\App\User\LoginThrottle::class),
            $container->get(\App\User\AuthService::class),
            $container->get(Aliases::class),
            $container->get(\App\Post\MarkdownRenderer::class),
            new \App\Post\HtmlToMarkdownService(),
        );
    }

    private function categoryAction(): CategoryShowAction
    {
        $container = $this->container();
        return new CategoryShowAction(
            $this->renderer(),
            $container->get(UrlGeneratorInterface::class),
            new ResponseFactory(),
            new Cache(new ArrayCache()),
            $container->get(Aliases::class),
            $container->get(\App\Post\MarkdownRenderer::class),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $now = time();

        $this->post = new Post();
        $this->post->cid = 1;
        $this->post->author_id = 1;
        $this->post->author_name = 'tester';
        $this->post->type = Post::TYPE_POST;
        $this->post->title = '__redirect_with_alias__';
        $this->post->content = '';
        $this->post->format = Post::FORMAT_MARKDOWN;
        $this->post->alias = '__rwa_' . bin2hex(random_bytes(4));
        $this->post->status = Post::STATUS_PUBLISHED;
        $this->post->post_time = $now;
        $this->post->create_time = $now;
        $this->post->update_time = $now;
        $this->post->save();

        $this->postNoAlias = new Post();
        $this->postNoAlias->cid = 1;
        $this->postNoAlias->author_id = 1;
        $this->postNoAlias->author_name = 'tester';
        $this->postNoAlias->type = Post::TYPE_POST;
        $this->postNoAlias->title = '__redirect_no_alias__';
        $this->postNoAlias->content = '';
        $this->postNoAlias->format = Post::FORMAT_MARKDOWN;
        $this->postNoAlias->alias = '';
        $this->postNoAlias->status = Post::STATUS_PUBLISHED;
        $this->postNoAlias->post_time = $now;
        $this->postNoAlias->create_time = $now;
        $this->postNoAlias->update_time = $now;
        $this->postNoAlias->save();

        $this->category = new Category();
        $this->category->name = '__redirect_cat__';
        $this->category->alias = '__rc_' . bin2hex(random_bytes(4));
        $this->category->display = Category::DISPLAY_LIST;
        $this->category->update_time = $now;
        $this->category->save();
    }

    protected function tearDown(): void
    {
        // delete 走 PK，失败不阻断清理
        try {
            (new Post())->deleteAll(['id' => [$this->post->id, $this->postNoAlias->id]]);
            (new Category())->deleteAll(['id' => $this->category->id]);
        } catch (\Throwable) {
        }
        parent::tearDown();
    }

    public function testPostByIdRedirectsToAliasUrl(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/post/' . $this->post->id);
        $response = $this->postAction()->show($request, null, (int)$this->post->id);

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/archive/' . $this->post->alias, $response->getHeaderLine('Location'));
    }

    public function testPostWithoutAliasRendersInPlace(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/post/' . $this->postNoAlias->id);
        $response = $this->postAction()->show($request, null, (int)$this->postNoAlias->id);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('Location'));
    }

    public function testPostByAliasRendersInPlace(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/archive/' . $this->post->alias);
        $response = $this->postAction()->show($request, $this->post->alias, null);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('Location'));
    }

    public function testCategoryByIdRedirectsToAliasUrl(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/category/' . $this->category->id);
        $response = ($this->categoryAction())($request, null, (int)$this->category->id);

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/catalog/' . $this->category->alias, $response->getHeaderLine('Location'));
    }

    public function testCategoryByAliasRendersInPlace(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/catalog/' . $this->category->alias);
        $response = ($this->categoryAction())($request, $this->category->alias, null);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('Location'));
    }
}
