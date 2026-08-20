<?php

declare(strict_types=1);

namespace App\Console;

use App\Common\RedisLock;
use App\Post\PostViewKeys;
use App\Post\PostViewSyncService;
use Predis\ClientInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

/**
 * 文章浏览数同步（方案 B）：Redis pending 集合 → MySQL，全程无 SCAN。
 *
 * - PV：累计计数 vs 同步游标，delta 增量落库
 * - UV：PFCOUNT 全量覆盖（仅变化时写）
 * - Redis 互斥锁（SET NX EX）防止并发同步重复累加
 *
 * 建议 1 分钟周期（crontab）；后台 Dashboard/文章列表也会触发 on-demand 惰性同步。
 */
#[AsCommand(
    name: 'post-view/sync',
    description: '同步 Redis 文章浏览计数到 MySQL（无 SCAN）',
)]
final class PostViewSyncCommand extends Command
{
    /** 同步互斥锁 key 前缀 */
    public const LOCK_PREFIX = 'crazydb:lock:post-view-sync';

    public function __construct(
        private ClientInterface $redis,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 并发锁：cron 与 on-demand 可并存，但任一时刻只有一处执行
        $lock = new RedisLock($this->redis);
        if (!$lock->acquire(self::LOCK_PREFIX)) {
            $output->writeln('另一实例正在同步，跳过本次。');
            return ExitCode::OK;
        }

        try {
            $synced = (new PostViewSyncService($this->redis))->syncPending();
        } catch (\Throwable $e) {
            $lock->release(self::LOCK_PREFIX);
            $output->writeln('<error>同步失败：' . $e->getMessage() . '</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $lock->release(self::LOCK_PREFIX);
        $output->writeln("已同步 {$synced} 条文章浏览数据。");
        return ExitCode::OK;
    }
}
