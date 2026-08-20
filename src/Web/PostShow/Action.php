<?php

declare(strict_types=1);

namespace App\Web\PostShow;

use App\Category\Category;
use App\Comment\Comment;
use App\Common\CMSUtils;
use App\Nav\Nav;
use App\Post\HtmlToMarkdownService;
use App\Post\MarkdownRenderer;
use App\Post\Post;
use App\Post\PostViewKeys;
use App\Tag\Tag;
use App\User\AuthService;
use App\User\LoginThrottle;
use App\User\User;
use App\Web\NotFound\NotFoundResponder;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Predis\ClientInterface;
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
        private ClientInterface $redis,
        private LoginThrottle $loginThrottle,
        private AuthService $authService,
        private Aliases $aliases,
        private MarkdownRenderer $markdownRenderer,
        private HtmlToMarkdownService $htmlToMarkdownService,
    ) {}

    public function show(
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
        // 热点文章详情只原子写 Redis；由 post-view/sync 增量合并到 MySQL。
        // 方案 B：累计计数（不按日）+ pending 标记 + 按日排行 ZSET，均无 SCAN。
        try {
            $postId = (int) $post->id;
            $ymd = date('Ymd');
            $this->redis->incr(PostViewKeys::counterKey($postId));
            $this->redis->sadd(PostViewKeys::pendingKey(), [(string)$postId]);
            $this->redis->zincrby(PostViewKeys::topKey($ymd), 1, (string)$postId);
            $this->redis->expire(PostViewKeys::topKey($ymd), PostViewKeys::TOP_TTL);
            $post->view_count++;
            // UV（仅正常访问，deviceId 由 VisitTrackingMiddleware 写入 request attribute）
            $deviceId = $request->getAttribute('device_id');
            if (is_string($deviceId) && $deviceId !== '') {
                $this->redis->pfadd(PostViewKeys::uvKey($postId), [$deviceId]);
            }
        } catch (\Throwable) {
            // 统计不可用不能影响文章访问。
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
        // 机会式对账 comment_count（对齐 Yii2 actionShow：计数漂移时顺手修正）。
        // 用显式 updateAll 只改 comment_count，避免裸 save() 把本请求内存中的
        // view_count++ 增量提前写入 MySQL（会与 post-view/sync 的 Redis 增量重复累计）。
        if ($total !== (int)$post->comment_count) {
            try {
                // updateAll 为实例方法（Yiisoft AR），惯例 new 后调用，只更新指定字段
                (new Post())->updateAll(['comment_count' => $total], ['id' => $post->id]);
            } catch (\Throwable) {
            }
        }
        $relatedCacheVersion = Post::relatedCacheVersion();
        $previous = $post->getRelatedOne(
            $this->urlGenerator,
            $this->cache,
            'before',
            false,
            false,
            $relatedCacheVersion,
        );
        $next = $post->getRelatedOne(
            $this->urlGenerator,
            $this->cache,
            'after',
            false,
            false,
            $relatedCacheVersion,
        );
        // 加锁文章（password 非空）：每次访问都需输入密码，不写入 session，登录也不绕过。
        // 密码在本请求（POST 到本页）内校验：正确则直接渲染全文，错误则渲染摘要+表单+错误提示。
        $password = (string) $post->password;
        $unlocked = false;
        $passwordError = false;
        $passwordLocked = false;
        if ($password !== '') {
            $body = $request->getParsedBody();
            $input = trim((string) (is_array($body) ? ($body['password'] ?? '') : ''));
            $submitted = $request->getMethod() === 'POST' && $input !== '';
            $clientIp = \App\Common\XUtils::getClientIP($request->getServerParams());
            $scope = 'post_password';
            $subject = (string) $post->id;
            $passwordLocked = $this->loginThrottle->remaining($subject, $clientIp, $scope) > 0;

            if ($submitted && !$passwordLocked && $post->verifyAccessPassword($input)) {
                $unlocked = true;
                if ($post->rehashAccessPasswordIfNeeded($input)) {
                    // 密码 rehash 只显式更新 password 字段，避免裸 save() 把本请求
                    // 的 view_count++ 增量（内存中）一并写入 MySQL 造成重复累计。
                    try {
                        (new Post())->updateAll(['password' => $post->password], ['id' => $post->id]);
                    } catch (\Throwable) {
                        // 迁移写入失败不影响本次已完成的密码验证。
                    }
                }
                $this->loginThrottle->clear($subject, $clientIp, $scope);
            } else {
                if ($submitted && !$passwordLocked) {
                    $passwordError = true;
                    $passwordLocked = $this->loginThrottle->recordFailure($subject, $clientIp, $scope) > 0;
                }
            }
        }

        // 预转换：旧 HTML 文章 → Markdown（一次转换，所有展示路径复用转换后的内容）。
        // 必须置于所有 save()（comment_count 对账、密码 rehash）之后、任何渲染（
        // getContentProcessed/getExcerptProcessed/getCoverImage/getSeoDescription）之前：
        // 保证落库的是原始 content，转换结果只用于本次请求的渲染，不触发隐式数据迁移。
        if ($post->format === Post::FORMAT_HTML) {
            $post->content = $this->htmlToMarkdownService->convert((string) $post->content);
            $post->format = Post::FORMAT_MARKDOWN;
        }

        if ($unlocked || $password === '') {
            $contentHtml = $post->getContentProcessed($this->markdownRenderer);
            $toc = $this->markdownRenderer->attachTocAnchors($contentHtml);
        } else {
            $contentHtml = $post->getExcerptProcessed($this->markdownRenderer);
            $toc = [];
        }

        /** @var ?User $author */
        $author = User::query()->findByPk((int)$post->author_id);

        return $this->viewRenderer->render(
            __DIR__ . '/template',
            [
                'post' => $post,
                'authorNickname' => $author?->nickname,
                'currentUser' => $this->authService->currentUser(),
                'contentHtml' => $contentHtml,
                'toc' => $toc,
                'unlocked' => $unlocked,
                'passwordError' => $passwordError,
                'passwordLocked' => $passwordLocked,
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
