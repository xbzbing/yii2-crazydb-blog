<?php

declare(strict_types=1);

namespace App\Console;

use App\Post\Post;
use App\Post\PostViewKeys;
use Predis\ClientInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

/**
 * 文章浏览数同步（V2）：Redis 实时数据 → MySQL。
 *
 * - PV：增量同步（游标 delta），建议 1 分钟周期
 * - UV：全量 PFCOUNT 覆盖（HLL 无法增量），仅在变化时写库
 */
#[AsCommand(
    name: 'post-view/sync',
    description: '同步 Redis 文章浏览计数到 MySQL',
)]
final class PostViewSyncCommand extends Command
{
    private const KEEP_REDIS_DAYS = 30;

    public function __construct(
        private ClientInterface $redis,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $synced = 0;
        $today = date('Ymd');

        try {
            // PV 同步（增量，按日分片 key）
            foreach ($this->collectCounters() as [$ymd, $postId]) {
                $total = (int)($this->redis->get(PostViewKeys::counterKey($postId, $ymd)) ?? 0);
                $previous = (int)($this->redis->get(PostViewKeys::syncedKey($postId, $ymd)) ?? 0);
                $delta = max(0, $total - $previous);

                if ($delta > 0) {
                    $post = Post::query()->findByPk($postId);
                    if ($post instanceof Post) {
                        $post->updateCounters(['view_count' => $delta]);
                        $this->redis->set(PostViewKeys::syncedKey($postId, $ymd), (string)$total);
                        $synced++;
                    }
                }

                if ($ymd < $today && $this->isOlderThanKeep($ymd)) {
                    $this->redis->del([
                        PostViewKeys::counterKey($postId, $ymd),
                        PostViewKeys::syncedKey($postId, $ymd),
                    ]);
                }
            }

            // UV 同步（全量 PFCOUNT，独立 SCAN）
            foreach ($this->collectUvKeys() as $postId) {
                $uvCurrent = $this->redis->pfcount(PostViewKeys::uvKey($postId));
                if ($uvCurrent > 0) {
                    $post = Post::query()->findByPk($postId);
                    if ($post instanceof Post && $post->view_uv !== $uvCurrent) {
                        $post->view_uv = $uvCurrent;
                        $post->save();
                        $synced++;
                    }
                }
            }
        } catch (\Throwable $e) {
            $output->writeln('<error>同步失败：' . $e->getMessage() . '</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $output->writeln("已同步 {$synced} 条文章浏览数据。");
        return ExitCode::OK;
    }

    /**
     * @return list<array{string, int}>
     */
    private function collectCounters(): array
    {
        $counters = [];
        $cursor = 0;
        do {
            /** @var array{0: string, 1: list<string>} $result */
            $result = $this->redis->scan($cursor, ['match' => PostViewKeys::COUNTER_PREFIX . '*', 'count' => 500]);
            $cursor = (int)$result[0];
            foreach ($result[1] as $key) {
                $suffix = substr($key, strlen(PostViewKeys::COUNTER_PREFIX));
                if (preg_match('/^(\d{8}):(\d+)$/', $suffix, $matches)) {
                    $counters[] = [$matches[1], (int)$matches[2]];
                }
            }
        } while ($cursor !== 0);
        return $counters;
    }

    /**
     * @return list<int> 文章 ID 列表（有 UV HLL 的文章）
     */
    private function collectUvKeys(): array
    {
        $postIds = [];
        $cursor = 0;
        do {
            /** @var array{0: string, 1: list<string>} $result */
            $result = $this->redis->scan($cursor, ['match' => PostViewKeys::UV_PREFIX . '*', 'count' => 500]);
            $cursor = (int)$result[0];
            foreach ($result[1] as $key) {
                $suffix = substr($key, strlen(PostViewKeys::UV_PREFIX));
                if (preg_match('/^\d+$/', $suffix)) {
                    $postIds[] = (int)$suffix;
                }
            }
        } while ($cursor !== 0);
        return $postIds;
    }

    private function isOlderThanKeep(string $ymd): bool
    {
        $timestamp = strtotime($ymd);
        return $timestamp !== false && $timestamp < strtotime('-' . self::KEEP_REDIS_DAYS . ' days');
    }
}
