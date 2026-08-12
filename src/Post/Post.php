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
    public int $is_top = 0;

    public function tableName(): string
    {
        return 'post';
    }

    /**
     * 按 format 分派渲染后的正文 HTML（markdown 走管线，html 老文章净化直出）。
     */
    public function getContentProcessed(MarkdownRenderer $renderer): string
    {
        return $renderer->renderPost($this);
    }

    /**
     * 前台可见状态集合（published + hidden，对齐 Yii2）。
     */
    public static function visibleStatuses(): array
    {
        return [self::STATUS_PUBLISHED, self::STATUS_HIDDEN];
    }

    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(Category::class, ['id' => 'cid']);
    }

    public function getAuthor(): ActiveQuery
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
     * 上一篇/下一篇：relation = 'before'（较旧）| 'after'（较新）。
     * 等价 Yii2 getRelatedOne；简单模式只取 id/title/alias/status，缓存 key 按模式区分。
     */
    public function getRelatedOne(
        UrlGeneratorInterface $urlGenerator,
        CacheInterface $cache,
        string $relation,
        bool $category = false,
        bool $simple = true,
    ): ?self {
        if (!in_array($relation, ['before', 'after'], true)) {
            return null;
        }

        $cacheKey = ($simple ? 'simple' : 'all') . '_post_' . $relation . '_' . $this->id
            . '.' . (int)self::query()->max('update_time');

        /** @var ?self $related */
        $related = $cache->getOrSet(
            $cacheKey,
            function () use ($relation, $category, $simple): ?self {
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
                return $query->one();
            },
            3600,
        );
        return $related;
    }

    /**
     * 前台查询：别名取文章（published/hidden），对齐 Yii2 findModelByAlias。
     */
    public static function findVisibleByAlias(string $alias): ?self
    {
        return self::query()
            ->where(['alias' => $alias, 'status' => self::visibleStatuses()])
            ->one();
    }

    /**
     * 前台查询：ID 取文章（published/hidden），对齐 Yii2 findModel。
     */
    public static function findVisibleById(int $id): ?self
    {
        return self::query()
            ->where(['id' => $id, 'status' => self::visibleStatuses()])
            ->one();
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
     * 封面提取：渲染后取正文第一张图片（对齐 Yii2 getCoverImage，markdown 文章先渲染）。
     */
    public function getCoverImage(MarkdownRenderer $renderer): ?string
    {
        preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $this->getContentProcessed($renderer), $matches);
        return $matches[1][0] ?? null;
    }
}
