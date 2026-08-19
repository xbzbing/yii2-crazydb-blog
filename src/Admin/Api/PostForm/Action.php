<?php

declare(strict_types=1);

namespace App\Admin\Api\PostForm;

use App\Admin\Api\ApiSerializer;
use App\Admin\Api\JsonResponse;
use App\Category\Category;
use App\Common\XUtils;
use App\Post\HtmlToMarkdownService;
use App\Post\MarkdownRenderer;
use App\Post\Post;
use App\Tag\Tag;
use App\User\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * 后台文章新建/编辑 JSON API。
 * - GET  /admin/api/post/{id}  单篇详情（表单回填 + 分类下拉）
 * - POST /admin/api/post/save       新建
 * - POST /admin/api/post/update/{id} 更新
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
        private CacheInterface $cache,
        private AuthService $authService,
        private MarkdownRenderer $markdownRenderer,
        private HtmlToMarkdownService $htmlToMarkdownService,
    ) {
    }

    public function detail(ServerRequestInterface $request, #[RouteArgument] int $id): ResponseInterface
    {
        $post = Post::query()->findByPk($id);
        if (!$post instanceof Post) {
            return $this->jsonResponse->fail('文章不存在。', 404);
        }
        $data = ApiSerializer::postDetail($post);
        // 旧版 HTML 文章：编辑时自动转换为 Markdown，使 Vditor 编辑器可加载；
        // 保存时以 format=markdown 落库（编辑即转换）。
        if ($post->format === Post::FORMAT_HTML) {
            $data['content'] = $this->htmlToMarkdownService->convert((string)$post->content);
            $data['excerpt'] = $post->excerpt !== null && $post->excerpt !== ''
                ? $this->htmlToMarkdownService->convert($post->excerpt)
                : '';
            $data['format'] = Post::FORMAT_MARKDOWN;
        }
        return $this->jsonResponse->ok([
            'post' => $data,
            'categories' => Category::getAllCategories($this->cache),
        ]);
    }

    public function save(ServerRequestInterface $request): ResponseInterface
    {
        return $this->persist($request, null);
    }

    public function update(ServerRequestInterface $request, #[RouteArgument] int $id): ResponseInterface
    {
        return $this->persist($request, $id);
    }

    private function persist(ServerRequestInterface $request, ?int $id): ResponseInterface
    {
        /** @var ?Post $post */
        $post = $id !== null ? Post::query()->findByPk($id) : null;
        if ($id !== null && !$post instanceof Post) {
            return $this->jsonResponse->fail('文章不存在。', 404);
        }
        $isNew = $post === null;
        $post ??= new Post();

        $body = $request->getParsedBody();
        $data = is_array($body) ? $body : [];
        $post->title = trim((string)($data['title'] ?? ''));
        $post->alias = trim((string)($data['alias'] ?? ''));
        if ($post->alias === '') {
            $post->alias = XUtils::generateAlias($post->title);
            $base = $post->alias;
            $n = 2;
            while (Post::query()->where(['alias' => $post->alias])->andWhere(['!=', 'id', (int)$post->id])->exists()) {
                $post->alias = $base . '-' . $n++;
            }
        }
        $post->cid = (int)($data['cid'] ?? 0);
        $post->status = (string)($data['status'] ?? Post::STATUS_DRAFT);
        $post->format = (string)($data['format'] ?? Post::FORMAT_HTML);
        $post->tags = trim((string)($data['tags'] ?? ''));
        $post->excerpt = trim((string)($data['excerpt'] ?? '')) ?: null;
        $post->content = (string)($data['content'] ?? '');
        $post->is_top = (int)($data['is_top'] ?? 0) > 0 ? 1 : 0;
            $accessPassword = trim((string)($data['password'] ?? ''));
            $clearAccessPassword = filter_var($data['clear_password'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($accessPassword !== '') {
                $post->password = Post::hashAccessPassword((int)$post->id, $accessPassword);
            } elseif ($isNew || $clearAccessPassword) {
                $post->password = null;
            }
        // post_time：前端不传时新建取当前时间、编辑保留原值（发布时间功能已从前端移除）
        if (array_key_exists('post_time', $data)) {
            $post->post_time = (int)$data['post_time'];
        } elseif ($isNew) {
            $post->post_time = time();
        }
        // 笔名（author_name）：编辑可改，新建默认当前用户昵称
        $post->author_name = trim((string)($data['author_name'] ?? ''));
        // 封面图片地址（cover）
        $post->cover = trim((string)($data['cover'] ?? '')) ?: null;
        // 自动生成封面（auto_cover）：cover 为空时从正文提取第一张图片（对齐 Yii2 beforeSave）
        $autoCover = (int)($data['auto_cover'] ?? 0) > 0;

        $errors = [];
        if ($post->title === '') {
            $errors['title'] = '标题不能为空。';
        } elseif ($post->alias !== '' && Post::query()->where(['alias' => $post->alias])->andWhere(['!=', 'id', (int)$post->id])->exists()) {
            $errors['alias'] = '别名已存在。';
        }
        if (!in_array($post->status, [Post::STATUS_PUBLISHED, Post::STATUS_DRAFT, Post::STATUS_DELETED], true)) {
            $errors['status'] = '状态不合法。';
        }
        if (!in_array($post->format, [Post::FORMAT_HTML, Post::FORMAT_MARKDOWN], true)) {
            $errors['format'] = '格式不合法。';
        }
        if ($errors !== []) {
            return $this->jsonResponse->ok(['ok' => false, 'errors' => $errors]);
        }

        if ($isNew) {
            $user = $this->authService->currentUser();
            $post->author_id = (int)$user?->id;
            if ($post->author_name === '') {
                $post->author_name = (string)$user?->nickname;
            }
            $post->type = Post::TYPE_POST;
            $post->create_time = time();
        }
        if ($post->post_time === 0) {
            $post->post_time = time();
        }
        $post->update_time = time();
        if ($post->format === Post::FORMAT_HTML) {
            $post->content = XUtils::htmlPurify($post->content);
            $post->excerpt = $post->excerpt !== null ? XUtils::htmlPurify($post->excerpt) : null;
        }
        // 自动生成封面：cover 为空且勾选 auto_cover → 从正文提取首图
        // （getCoverImage 对 markdown 先渲染再取 <img>，html 直接取）
        if ($autoCover && $post->cover === null) {
            $post->cover = $post->getCoverImage($this->markdownRenderer);
        }
        try {
            $post->save();
        } catch (\Throwable) {
            return $this->jsonResponse->fail('保存失败，请检查字段内容。');
        }
        if ($isNew && $post->password !== null && $post->password !== '') {
            // 新建时 id 尚未分配，保存后按真实 id 重新哈希（md5 依赖 post_id）。
            $post->password = Post::hashAccessPassword((int)$post->id, $accessPassword);
            try {
                $post->save();
            } catch (\Throwable) {
            }
        }
        Tag::post2tags($post->tags, (int)$post->id, (int)$post->cid);
        Tag::invalidateCache($this->cache);
        Category::invalidateSummaryCache($this->cache);

        return $this->jsonResponse->ok([
            'id' => (int)$post->id,
            'message' => $isNew ? '文章已创建。' : '文章已更新。',
        ]);
    }
}
