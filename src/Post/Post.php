<?php

declare(strict_types=1);

namespace App\Post;

use App\Category\Category;
use App\Comment\Comment;
use App\User\User;
use Yiisoft\ActiveRecord\ActiveQuery;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\UrlGeneratorInterface;

final class Post extends ActiveRecord
{
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_HIDDEN = 'hidden';

    public const TYPE_POST = 'post';
    public const TYPE_ALBUM = 'album';
    public const TYPE_PRODUCT = 'product';

    public const FORMAT_HTML = 'html';
    public const FORMAT_MARKDOWN = 'markdown';

    public ?int $id = null;
    public int $cid = 0;
    public int $author_id = 0;
    public string $author_name = '';
    public string $type = 'post';
    public string $title = '';
    public string $alias = '';
    public ?string $excerpt = null;
    public ?string $content = null;
    public string $format = 'html';
    public ?string $cover = null;
    public ?string $password = null;
    public string $status = 'published';
    public int $create_time = 0;
    public ?int $post_time = null;
    public int $update_time = 0;
    public string $tags = '';
    public int $comment_count = 0;
    public int $view_count = 0;
    /** 文档 UV（累计独立设备数，由 post-view/sync 同步） */
    public int $view_uv = 0;
    public int $is_top = 0;

    public function tableName(): string
    {
        return '{{%post}}';
    }

    /**
     * 渲染后的正文 HTML（交由 MarkdownRenderer 处理）。
     * 展示层的 HTML→MD 转换由 PostShow/Action 在调用前处理（不修改 AR 属性）。
     */
    public function getContentProcessed(MarkdownRenderer $renderer): string
    {
        return $renderer->renderPost($this);
    }

    /**
     * 渲染后的摘要 HTML。
     * 展示层的 HTML→MD 转换由 PostShow/Action 在调用前处理。
     */
    public function getExcerptProcessed(MarkdownRenderer $renderer): string
    {
        $excerpt = (string) $this->excerpt;
        if ($excerpt === '') {
            return '';
        }
        return $renderer->render($excerpt, $this->update_time);
    }

    /**
     * 前台可见状态集合（仅已发布；草稿/已删除不公开，对齐需求）。
     *
     * @return list<string>
     */
    public static function visibleStatuses(): array
    {
        return [self::STATUS_PUBLISHED];
    }

    public function getCategory(): \Yiisoft\ActiveRecord\ActiveQueryInterface
    {
        return $this->hasOne(Category::class, ['id' => 'cid']);
    }

    public function getAuthor(): \Yiisoft\ActiveRecord\ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'author_id']);
    }

    public function getComments(): \Yiisoft\ActiveRecord\ActiveQueryInterface
    {
        return $this->hasMany(Comment::class, ['pid' => 'id'])
            ->andOn(['status' => Comment::STATUS_APPROVED])
            ->orderBy(['create_time' => SORT_ASC]);
    }

    /**
     * 前台文章 URL：alias 优先（post/show），否则按 id（post/view）；对齐 Yii2 getUrl。
     */
    public function getUrl(UrlGeneratorInterface $urlGenerator, bool $schema = false): ?string
    {
        if ($this->isNew()) {
            return null;
        }
        if ($this->alias !== '') {
            return $schema
                ? $urlGenerator->generateAbsolute('post/show', ['alias' => $this->alias])
                : $urlGenerator->generate('post/show', ['alias' => $this->alias]);
        }
        return $schema
            ? $urlGenerator->generateAbsolute('post/view', ['id' => $this->id])
            : $urlGenerator->generate('post/view', ['id' => $this->id]);
    }

    /**
     * 前后篇缓存版本。详情页会把同一版本传给前后两次查询，避免重复聚合。
     */
    public static function relatedCacheVersion(): int
    {
        return (int) self::query()->max('update_time');
    }

    /**
     * 上一篇/下一篇：relation = 'before'（较旧）| 'after'（较新）。
     * 等价 Yii2 getRelatedOne；简单模式只取 id/title/alias/status，缓存 key 按模式区分。
     */
    public function getRelatedOne(
        UrlGeneratorInterface $urlGenerator,
        CacheInterface $cache,
        string $relation,
        bool $category = false,
        bool $simple = true,
        ?int $cacheVersion = null,
    ): ?self {
        if (!in_array($relation, ['before', 'after'], true)) {
            return null;
        }

        $cacheVersion ??= self::relatedCacheVersion();
        $cacheKey = ($simple ? 'simple' : 'all') . '_post_' . $relation . '_' . $this->id
            . ($category ? '_cat' : '')
            . '.' . $cacheVersion;

        $related = $cache->getOrSet(
            $cacheKey,
            function () use ($relation, $category, $simple): self|string {
                $query = self::query()
                    ->where(['in', 'status', self::visibleStatuses()])
                    ->andWhere(['!=', 'id', $this->id]);
                if ($category) {
                    $query->andWhere(['cid' => $this->cid]);
                }
                if ($relation === 'before') {
                    $query->andWhere(['<', 'post_time', (int)$this->post_time])
                        ->orderBy(['post_time' => SORT_DESC, 'id' => SORT_DESC]);
                } else {
                    $query->andWhere(['>', 'post_time', (int)$this->post_time])
                        ->orderBy(['post_time' => SORT_ASC, 'id' => SORT_ASC]);
                }
                if ($simple) {
                    $query->select('id,title,alias,status');
                }
                // 空结果以哨兵缓存，避免边界文章（最新/最旧）每请求 SQL 穿透
                /** @var ?self $one */
                $one = $query->one();
                return $one ?? 'none';
            },
            3600,
        );
        // @psalm-suppress MixedReturnStatement cache sentinel 'none' → mixed can't be narrowed by psalm
        /** @var self|null */
        $result = $related === 'none' ? null : $related;
        return $result;
    }

    /**
     * 前台查询：别名取文章（published/hidden），对齐 Yii2 findModelByAlias。
     *
     * @return ?self
     */
    public static function findVisibleByAlias(string $alias): ?self
    {
        // @phpstan-ignore-next-line return.type (ActiveQuery::one() declares array|AR|null, we only store Post here)
        return self::query()
            ->where(['alias' => $alias, 'status' => self::visibleStatuses()])
            ->one();
    }

    /**
     * 前台查询：ID 取文章（published/hidden），对齐 Yii2 findModel。
     *
     * @return ?self
     */
    public static function findVisibleById(int $id): ?self
    {
        // @phpstan-ignore-next-line return.type (ActiveQuery::one() declares array|AR|null, we only store Post here)
        return self::query()
            ->where(['id' => $id, 'status' => self::visibleStatuses()])
            ->one();
    }

    /**
     * 文章访问密码持久化前使用 32 字符定长哈希：md5(md5(post_id) . password)，
     * 兼容存量 varchar(32) 字段，同时避免密码明文落库。
     */
    public static function hashAccessPassword(int $postId, string $password): string
    {
        return md5(md5((string) $postId) . $password);
    }

    /**
     * 校验文章访问密码；迁移窗口内兼容历史明文。
     */
    public function verifyAccessPassword(string $password): bool
    {
        $stored = (string) $this->password;
        if ($stored === '') {
            return false;
        }

        if (hash_equals($stored, self::hashAccessPassword((int) $this->id, $password))) {
            return true;
        }

        // 历史明文兼容（迁移窗口）；新格式哈希与之碰撞的概率可忽略。
        return hash_equals($stored, $password);
    }

    /**
     * 历史明文密码首次验证成功后升级为哈希，调用方负责持久化。
     */
    public function rehashAccessPasswordIfNeeded(string $password): bool
    {
        $stored = (string) $this->password;
        if ($stored === '' || hash_equals($stored, self::hashAccessPassword((int) $this->id, $password))) {
            return false;
        }
        if (!hash_equals($stored, $password)) {
            return false;
        }

        $this->password = self::hashAccessPassword((int) $this->id, $password);
        return true;
    }

    /**
     * SEO description：渲染后去标签截断。
     */
    public function getSeoDescription(MarkdownRenderer $renderer, int $width = 150): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($this->getContentProcessed($renderer))) ?? '');
        if ($text === '') {
            return '';
        }
        return mb_strimwidth($text, 0, $width, '...', 'utf-8');
    }

    /**
     * 封面提取：取正文第一张图片（对齐 Yii2 getCoverImage）。
     * markdown 文章先渲染成 HTML 再取 <img>；同时兜底识别 markdown 图片语法 ![alt](url)。
     */
    public function getCoverImage(MarkdownRenderer $renderer): ?string
    {
        $html = $this->getContentProcessed($renderer);
        if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $html, $matches)) {
            return $matches[1];
        }
        // markdown 兜底：![alt](url) / ![](url)
        if (preg_match('/!\[[^\]]*\]\(\s*([^)\s]+)\s*\)/', $html, $m)) {
            return $m[1];
        }
        return null;
    }
}
