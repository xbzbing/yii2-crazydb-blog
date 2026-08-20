<?php

declare(strict_types=1);

namespace App\Console;

use App\Post\Post;
use App\Post\PostViewKeys;
use Predis\ClientInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

/**
 * 清理 V2 旧形态（方案 A）按日分片的 post-view synced key：synced:{Ymd}:{postId}。
 *
 * 无 SCAN：按「最近 N 天 × 已知文章 ID」确定性构造 key，pipeline 批量 DEL。
 * - 旧 count key（count:{Ymd}:{postId}）自带 30 天 TTL，无需清理，自动过期
 * - 旧 synced key 无 TTL 会永久残留，需要本命令清理
 * - 已删除文章的孤儿 synced key 无法由"已知文章 ID"枚举到——但删除时 clearPost 已清理新 key，
 *   旧孤儿残留量极小可忽略（文章删除本就不常见）
 *
 * 上线时执行一次即可（幂等，可重复执行）；新形态 key（count:{postId}，无冒号）不会被误删。
 */
#[AsCommand(
    name: 'post-view/cleanup-legacy',
    description: '清理 V2 旧按日分片 synced key（方案 B 上线一次性执行，无 SCAN，幂等）',
)]
final class PostViewCleanupLegacyCommand extends Command
{
    /** 扫描回看天数：覆盖旧 synced key 可能的日期跨度 */
    private const DEFAULT_DAYS = 90;

    public function __construct(
        private ClientInterface $redis,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', null, InputOption::VALUE_OPTIONAL, '回看天数（默认 90）', (string)self::DEFAULT_DAYS);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = (int)($input->getOption('days') ?? self::DEFAULT_DAYS);
        $days = max(1, min(365, $days));

        $postIds = $this->collectPostIds();
        if ($postIds === []) {
            $output->writeln('无文章，无需清理。');
            return ExitCode::OK;
        }

        // 构建候选旧 synced key：最近 N 天 × 每篇文章
        $keys = [];
        for ($i = 0; $i < $days; $i++) {
            $ts = strtotime('-' . $i . ' days');
            if ($ts === false) {
                continue;
            }
            $ymd = date('Ymd', $ts);
            foreach ($postIds as $postId) {
                $keys[] = PostViewKeys::SYNCED_PREFIX . $ymd . ':' . $postId;
            }
        }

        $deleted = 0;
        // 分批 pipeline DEL，避免一次性巨量命令阻塞连接
        foreach (array_chunk($keys, 500) as $chunk) {
            try {
                $deleted += $this->redis->del($chunk);
            } catch (\Throwable $e) {
                $output->writeln('<error>清理失败：' . $e->getMessage() . '</error>');
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        $output->writeln("已清理 {$deleted} 个旧按日分片 synced key（回看 {$days} 天，{$this->fmt(count($postIds))} 篇文章）。");
        return ExitCode::OK;
    }

    /**
     * 收集全部文章 ID（含删除标记态——历史 key 可能属于已删除文章）。
     *
     * @return list<int>
     */
    private function collectPostIds(): array
    {
        $ids = Post::query()->select('id')->column();
        return array_values(array_map('intval', $ids));
    }

    private function fmt(int $n): string
    {
        return number_format($n);
    }
}
