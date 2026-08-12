<?php

declare(strict_types=1);

namespace App\Web\PostShow;

use App\Category\Category;
use App\Comment\Comment;
use App\Common\CMSUtils;
use App\Nav\Nav;
use App\Post\MarkdownRenderer;
use App\Post\Post;
use App\Tag\Tag;
use App\Web\NotFound\NotFoundResponder;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 前台文章详情页：文章正文 + 上一篇/下一篇 + 评论列表 + 评论表单。
 * 等价 Yii2 PostController::actionShow。
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private UrlGeneratorInterface $urlGenerator,
        private ResponseFactoryInterface $responseFactory,
        private CacheInterface $cache,
        private Aliases $aliases,
        private MarkdownRenderer $markdownRenderer,
    ) {}

    public function __invoke(ServerRequestInterface $request, #[RouteArgument] string $alias): ResponseInterface
    {
        $post = Post::findVisibleByAlias($alias);
        if ($post === null) {
            return NotFoundResponder::respond($this->viewRenderer, $this->responseFactory, $this->urlGenerator);
        }

        $siteConfig = CMSUtils::getSiteConfig($this->cache);
        $comments = $post->getComments()->all();
        $total = count($comments);
        $previous = $post->getRelatedOne($this->urlGenerator, $this->cache, 'before', false, false);
        $next = $post->getRelatedOne($this->urlGenerator, $this->cache, 'after', false, false);
        // 隐藏文章不公开全文，仅显示摘要（对齐 Yii2 实际行为：密码验证早已停用）
        $contentHtml = $post->status === Post::STATUS_HIDDEN
            ? \Yiisoft\Html\Html::encode((string)$post->excerpt)
            : $post->getContentProcessed($this->markdownRenderer);

        return $this->viewRenderer->render(
            __DIR__ . '/template',
            [
                'post' => $post,
                'contentHtml' => $contentHtml,
                'comments' => $comments,
                'commentTotal' => $total,
                'previous' => $previous,
                'next' => $next,
                'markdownRenderer' => $this->markdownRenderer,
                'urlGenerator' => $this->urlGenerator,
                'siteConfig' => $siteConfig,
                'navTree' => Nav::getNavTree($this->cache),
                'showSidebar' => true,
                'categorySummary' => Category::getCategorySummary($this->cache, $this->urlGenerator),
                'sidebarTags' => Tag::getTags($this->cache, $this->urlGenerator, false, 20),
                'sidebarComments' => Comment::getRecentComments($this->cache, $this->urlGenerator, $this->aliases, 5),
            ],
        );
    }
}
