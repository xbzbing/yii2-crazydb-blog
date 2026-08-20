<?php

declare(strict_types=1);

namespace App\Admin\Api\PostRank;

use App\Admin\Api\JsonResponse;
use App\Post\Post;
use App\Post\PostViewKeys;
use Predis\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * GET /admin/api/post-rank/{day}：某日（today/yesterday）阅读排行。
 * day: today | yesterday（默认 today）。
 *
 * 数据来自 Redis 按日 ZSET（POST：ZINCRBY），TTL 48h；无数据返回空列表。
 * 提示：该排行读自缓存，可能存在偏差（cache & bias）。
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
        private ClientInterface $redis,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $day = (string)($request->getQueryParams()['day'] ?? 'today');
        $date = $this->resolveDate($day);
        if ($date === null) {
            return $this->jsonResponse->fail('day 参数仅支持 today/yesterday。');
        }

        $key = PostViewKeys::topDateKey($date);
        $ranking = [];
        try {
            // Predis zrevrange withscores 返回关联数组 {member: score}，直接传给 hydrate
            /** @var array<array-key, mixed> $raw */
            $raw = $this->redis->zrevrange($key, 0, PostViewKeys::TOP_LIMIT - 1, ['withscores' => true]);
            $ranking = $this->hydrate($raw);
        } catch (\Throwable) {
            $ranking = [];
        }

        return $this->jsonResponse->ok([
            'day' => $day,
            'date' => $date->format('Y-m-d'),
            'items' => $ranking,
        ]);
    }

    /**
     * @return list<array{post_id: int, title: string, views: int, alias: string}>
     * @param array<array-key, mixed> $raw Predis zrevrange withscores 返回关联数组 {member: score}
     */
    private function hydrate(array $raw): array
    {
        $ids = [];
        // Predis 关联数组：key=member(postId 字符串), value=score(views)
        foreach ($raw as $member => $score) {
            $pid = (int)$member;
            $ids[] = $pid;
        }

        // 批量取标题
        $titleMap = [];
        if ($ids !== []) {
            /** @var list<Post> $posts */
            $posts = Post::query()->where(['in', 'id', $ids])->select('id,title,alias')->all();
            foreach ($posts as $p) {
                $titleMap[(int)$p->id] = $p;
            }
        }

        // 按分值降序构建结果（Predis zrevrange 已按分值降序，顺序不变）
        $result = [];
        foreach ($raw as $member => $score) {
            $pid = (int)$member;
            $post = $titleMap[$pid] ?? null;
            if ($post === null) {
                continue; // 过滤掉已删除/不存在的文章（ZSET TTL 48h 内可能残留）
            }
            $result[] = [
                'post_id' => $pid,
                'title' => $post->title,
                'alias' => $post->alias,
                'views' => (int)$score,
            ];
        }
        return $result;
    }

    private function resolveDate(string $day): ?\DateTimeImmutable
    {
        $now = new \DateTimeImmutable();
        return match ($day) {
            'today' => $now,
            'yesterday' => $now->modify('-1 day'),
            default => null,
        };
    }
}
