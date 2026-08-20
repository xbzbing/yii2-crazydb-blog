<?php

declare(strict_types=1);

namespace App\Console;

use App\Common\RedisLock;
use App\Visit\VisitSyncService;
use Predis\ClientInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

/**
 * 访问统计同步：Redis 实时 PV/UV/IP/分类 → MySQL visit_daily。
 *
 * 无 SCAN（固定窗口枚举日期）；Redis 互斥锁防并发重复累加（cron + on-demand 并存安全）。
 * 建议每 10 分钟执行（crontab）。小时 key 不参与同步（Redis-only，TTL 48h 自动清理）。
 */
#[AsCommand(
    name: 'visit/sync',
    description: '同步访问统计到 MySQL（Redis crazydb:visit:* → visit_daily）',
)]
final class VisitSyncCommand extends Command
{
    public function __construct(
        private ClientInterface $redis,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lock = new RedisLock($this->redis);
        $lockKey = 'crazydb:lock:visit-sync';
        if (!$lock->acquire($lockKey)) {
            $output->writeln('另一实例正在同步，跳过本次。');
            return ExitCode::OK;
        }

        try {
            $synced = (new VisitSyncService($this->redis))->syncPending();
        } catch (\Throwable $e) {
            $lock->release($lockKey);
            $output->writeln('<error>同步失败：' . $e->getMessage() . '</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $lock->release($lockKey);
        $output->writeln('访问统计同步完成，共更新 ' . $synced . ' 天。');
        return ExitCode::OK;
    }
}
