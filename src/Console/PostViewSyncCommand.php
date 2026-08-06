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
 * 将 Redis 中按日累积的文章浏览数增量同步至 MySQL。
 *
 * 建议每 10 分钟执行一次：php yii post-view/sync。
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
            foreach ($this->collectCounters() as [$ymd, $postId]) {
                $total = (int) ($this->redis->get(PostViewKeys::counterKey($postId, $ymd)) ?? 0);
                $previous = (int) ($this->redis->get(PostViewKeys::syncedKey($postId, $ymd)) ?? 0);
                $delta = max(0, $total - $previous);

                if ($delta > 0) {
                    $post = Post::query()->findByPk($postId);
                    if ($post instanceof Post) {
                        $post->updateCounters(['view_count' => $delta]);
                        $this->redis->set(PostViewKeys::syncedKey($postId, $ymd), (string) $total);
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
        } catch (\Throwable $e) {
            $output->writeln('<error>同步失败：' . $e->getMessage() . '</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $output->writeln("已同步 {$synced} 条文章浏览计数。");
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
            $cursor = (int) $result[0];
            foreach ($result[1] as $key) {
                $suffix = substr($key, strlen(PostViewKeys::COUNTER_PREFIX));
                if (preg_match('/^(\d{8}):(\d+)$/', $suffix, $matches)) {
                    $counters[] = [$matches[1], (int) $matches[2]];
                }
            }
        } while ($cursor !== 0);

        return $counters;
    }

    private function isOlderThanKeep(string $ymd): bool
    {
        $timestamp = strtotime($ymd);
        return $timestamp !== false && $timestamp < strtotime('-' . self::KEEP_REDIS_DAYS . ' days');
    }
}
