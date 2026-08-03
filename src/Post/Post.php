<?php

declare(strict_types=1);

namespace App\Post;

use Yiisoft\ActiveRecord\ActiveRecord;

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
