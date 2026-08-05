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

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] ?string $alias = null,
        #[RouteArgument] ?int $id = null,
    ): ResponseInterface {
        $post = $alias !== null
            ? Post::findVisibleByAlias($alias)
            : ($id !== null ? Post::findVisibleById($id) : null);
        if ($post === null) {
            return NotFoundResponder::respond($this->viewRenderer, $this->responseFactory, $this->urlGenerator);
        }

        $siteConfig = CMSUtils::getSiteConfig($this->cache);
        // 浏览数自增（对齐 Yii2 actionShow；updateCounters 不触发全行 save）
        try {
            $post->updateCounters(['view_count' => 1]);
            $post->view_count++;
        } catch (\Throwable) {
        }
        $comments = $post->getComments()->all();
        $total = count($comments);
        // 批量预取回复目标（消除模板内每条回复一次 getReply 查询）
        /** @var list<Comment> $commentList */
        $commentList = $comments;
        $replyIds = array_filter(array_map(static fn (Comment $c): ?int => $c->reply_to, $commentList));
        $replyMap = [];
        if ($replyIds !== []) {
            /** @var list<Comment> $replyTargets */
            $replyTargets = Comment::query()->where(['id' => $replyIds])->all();
            foreach ($replyTargets as $target) {
                $replyMap[(int)$target->id] = $target;
            }
        }
        // 机会式对账 comment_count（对齐 Yii2 actionShow：计数漂移时顺手修正）
        if ($total !== (int)$post->comment_count) {
            $post->comment_count = $total;
            try {
                $post->save();
            } catch (\Throwable) {
            }
        }
        $previous = $post->getRelatedOne($this->urlGenerator, $this->cache, 'before', false, false);
        $next = $post->getRelatedOne($this->urlGenerator, $this->cache, 'after', false, false);
        // 加锁文章（password 非空）：每次访问都需输入密码，不写入 session，登录也不绕过。
        // 密码在本请求（POST 到本页）内校验：正确则直接渲染全文，错误则渲染摘要+表单+错误提示。
        $password = (string)$post->password;
        $unlocked = false;
        $passwordError = false;
        if ($password !== '') {
            $body = $request->getParsedBody();
            $input = trim((string)(is_array($body) ? ($body['password'] ?? '') : ''));
            $submitted = $request->getMethod() === 'POST' && $input !== '';
            if ($submitted && hash_equals($password, $input)) {
                $unlocked = true;
                $contentHtml = $post->getContentProcessed($this->markdownRenderer);
                $toc = $this->markdownRenderer->attachTocAnchors($contentHtml);
            } else {
                $passwordError = $submitted;
                $contentHtml = $post->getExcerptProcessed($this->markdownRenderer);
                $toc = [];
            }
        } else {
            $contentHtml = $post->getContentProcessed($this->markdownRenderer);
            $toc = $this->markdownRenderer->attachTocAnchors($contentHtml);
        }

        return $this->viewRenderer->render(
            __DIR__ . '/template',
            [
                'post' => $post,
                'contentHtml' => $contentHtml,
                'toc' => $toc,
                'unlocked' => $unlocked,
                'passwordError' => $passwordError,
                'comments' => $comments,
                'replyMap' => $replyMap,
                'commentTotal' => $total,
                'previous' => $previous,
                'next' => $next,
                'markdownRenderer' => $this->markdownRenderer,
                'urlGenerator' => $this->urlGenerator,
                'siteConfig' => $siteConfig,
                'seoConfig' => CMSUtils::getSiteConfig($this->cache, 'seo'),
                'navTree' => Nav::getNavTree($this->cache, $this->urlGenerator),
                'showSidebar' => true,
                'categorySummary' => Category::getCategorySummary($this->cache, $this->urlGenerator),
                'sidebarTags' => Tag::getTags($this->cache, $this->urlGenerator, false, 20),
                'sidebarComments' => Comment::getRecentComments($this->cache, $this->urlGenerator, $this->aliases, 5),
            ],
        );
    }
}
