<?php

declare(strict_types=1);

namespace App\Comment;

use App\Common\CMSUtils;
use App\Common\XUtils;
use App\Log\Log;
use App\Option\Option;
use App\Post\Post;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\UrlGeneratorInterface;

final class Comment extends ActiveRecord
{
    public const STATUS_UNAPPROVED = 'unapproved';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SPAM = 'spam';

    public ?int $id = null;
    public int $pid = 0;
    public ?int $uid = null;
    public string $nickname = '';
    public string $email = '';
    public ?int $reply_to = null;
    public ?string $url = null;
    public string $ip = '';
    public string $user_agent = '';
    public int $create_time = 0;
    public int $update_time = 0;
    public ?string $content = null;
    public string $status = 'unapproved';

    public function tableName(): string
    {
        return 'comment';
    }

    /**
     * @return array<string, string>
     */
    public static function getAvailableStatus(): array
    {
        return [
            self::STATUS_UNAPPROVED => '未审核',
            self::STATUS_APPROVED => '审核通过',
            self::STATUS_SPAM => '垃圾评论',
        ];
    }

    public static function getStatusName(string $status): ?string
    {
        return self::getAvailableStatus()[$status] ?? null;
    }

    public function getCommentStatus(): ?string
    {
        return self::getStatusName($this->status);
    }

    public function isReply(): bool
    {
        return ($this->reply_to ?? 0) > 0;
    }

    public function getCommentType(): string
    {
        return $this->isReply() ? '回复' : '评论';
    }

    public function getReply(): ?self
    {
        if (!$this->isReply()) {
            return null;
        }
        return self::query()->findByPk($this->reply_to);
    }

    public function getPost(): ?Post
    {
        return Post::query()->findByPk($this->pid);
    }

    /**
     * 最新评论列表（等价 Yii2 getRecentComments）：包含头像/文章链接，按 create_time 降序。
     *
     * @return list<array{
     *     id: ?int, nickname: string, website: ?string, pid: ?int, post_url: ?string,
     *     content: ?string, create_time: ?int, email: string, avatar: string, title: string
     * }>
     */
    public static function getRecentComments(
        CacheInterface $cache,
        UrlGeneratorInterface $urlGenerator,
        \Yiisoft\Aliases\Aliases $aliases,
        int $limit = 10,
        bool $refresh = false,
        int $size = 40,
    ): array {
        $cacheKey = '__recentComments.' . (int)self::query()->max('update_time');
        if ($refresh) {
            $cache->remove($cacheKey);
        }
        /** @var list<array{id: ?int, nickname: string, website: ?string, pid: ?int, post_url: ?string, content: string, create_time: ?int, email: string, avatar: string, title: string}> $items */
        $items = $cache->getOrSet(
            $cacheKey,
            static function () use ($urlGenerator, $aliases, $limit, $size): array {
                $items = [];
                $comments = self::query()
                    ->orderBy(['create_time' => SORT_DESC])
                    ->limit($limit)
                    ->all();
                foreach ($comments as $comment) {
                    $post = $comment->getPost();
                    $items[] = [
                        'id' => $comment->id,
                        'nickname' => $comment->nickname,
                        'website' => $comment->url,
                        'pid' => $comment->pid,
                        'post_url' => $post?->getUrl($urlGenerator) . '#comment-' . $comment->id,
                        'content' => $comment->content,
                        'create_time' => $comment->create_time,
                        'email' => $comment->email,
                        'avatar' => XUtils::getAvatar($aliases, $comment->email, $size),
                        'title' => $post?->title ?? '',
                    ];
                }
                return $items;
            },
            3600,
        );
        return $items;
    }

    /**
     * 是否允许评论（option allow_comment）。
     */
    public static function isAllowComment(CacheInterface $cache): bool
    {
        return CMSUtils::getSysConfig($cache, Option::ALLOW_COMMENT) === Option::STATUS_OPEN;
    }

    /**
     * 评论发布前净化（等价 Yii2 beforeSave：content HTMLPurify、nickname 去标签）。
     */
    public function sanitize(): void
    {
        $this->content = XUtils::htmlPurify((string)$this->content);
        $this->nickname = htmlspecialchars(strip_tags($this->nickname), ENT_QUOTES);
    }

    /**
     * 评论内容必须包含中文（等价 Yii2 antiSpam），否则拦截并记录日志。
     */
    public function passAntiSpam(Log $log): bool
    {
        if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', (string)$this->content) === 1) {
            return true;
        }
        $log->record(
            Log::TYPE_COMMENT,
            self::class,
            $this->nickname,
            Log::STATUS_FAILED,
            'email:' . $this->email . ',content:' . $this->content,
        );
        return false;
    }

    /**
     * 发布前填充默认值（IP/UA/时间戳，等价 Yii2 beforeSave 新记录逻辑）。
     */
    public function fillDefaultsForInsert(string $ip, string $userAgent): void
    {
        $now = time();
        $this->ip = $ip;
        $this->user_agent = htmlspecialchars($userAgent, ENT_QUOTES);
        $this->create_time = $now;
        $this->update_time = $now;
    }
}
