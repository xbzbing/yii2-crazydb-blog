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
            // ZREVRANGE key 0 19 WITHSCORES → [[id, score], ...]（按阅读次数降序，取前 TOP_LIMIT 名）
            $raw = $this->redis->zrevrange($key, 0, PostViewKeys::TOP_LIMIT - 1, ['withscores' => true]);
            /** @var list<mixed> $flat */
            $flat = array_values((array)$raw);
            $ranking = $this->hydrate($flat);
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
     * @param list<mixed> $raw ZREVRANGE WITHSCORES 结果
     */
    private function hydrate(array $raw): array
    {
        $items = [];
        $id = null;
        $ids = [];
        foreach ($raw as $v) {
            if ($id === null) {
                $id = (int)$v;
                $ids[] = $id;
            } else {
                $items[(string)$id] = ['views' => (int)$v];
                $id = null;
            }
        }
        if ($id !== null) {
            // 末尾 ID 无 score（异常，忽略）
            $ids = array_slice($ids, 0, count($items));
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

        $result = [];
        foreach ($items as $postId => $score) {
            $pid = (int)$postId;
            $post = $titleMap[$pid] ?? null;
            $result[] = [
                'post_id' => $pid,
                'title' => $post ? $post->title : "(已删除 {$pid})",
                'alias' => $post ? $post->alias : '',
                'views' => $score['views'],
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
