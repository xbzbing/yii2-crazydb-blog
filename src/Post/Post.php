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

    public ?int $id = null;
    public int $cid = 0;
    public int $author_id = 0;
    public string $author_name = '';
    public string $type = 'post';
    public string $title = '';
    public string $alias = '';
    public ?string $excerpt = null;
    public ?string $content = null;
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
}
