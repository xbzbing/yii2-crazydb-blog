<?php

declare(strict_types=1);

namespace App\Console;

use App\Visit\VisitDaily;
use App\Visit\VisitKeys;
use Predis\ClientInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

/**
 * 访问统计同步：把 Redis 实时 PV/UV（crazydb:visit:*）增量落库到 visit_daily。
 *
 * 建议每 10 分钟执行一次（crontab 调用 php yii visit/sync）。
 * - PV：用 synced 游标记录已同步数，取当前 INCR 值与游标差值累加，防重复
 * - UV：HyperLogLog PFCOUNT 直接覆盖
 * - 同步完成后删除 Redis 日 key（数据已持久化；保留最近 N 天游标用于一致性校验）
 */
#[AsCommand(
    name: 'visit/sync',
    description: '同步访问统计到 MySQL（Redis crazydb:visit:* → visit_daily）',
)]
final class VisitSyncCommand extends Command
{
    /** @var int 保留最近 Redis 统计的天数（更早的日 key 已落库，删除释放内存） */
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
            $dates = $this->collectDates();
            foreach ($dates as $ymd) {
                $pvTotal = $this->redis->get(VisitKeys::pvKey($ymd));
                $pvTotal = $pvTotal === null ? 0 : (int)$pvTotal;
                $uvTotal = $this->redis->pfcount(VisitKeys::uvKey($ymd));

                // 增量：本次新增 PV = 当前总数 - 已同步游标（首同步游标为 0）
                $prevRaw = $this->redis->get(VisitKeys::syncedKey($ymd));
                $prev = $prevRaw === null ? 0 : (int)$prevRaw;
                $deltaPv = max(0, $pvTotal - $prev);

                if ($deltaPv > 0 || $uvTotal > 0) {
                    VisitDaily::upsertByDate($this->ymdToDate($ymd), $deltaPv, $uvTotal);
                    $this->redis->set(VisitKeys::syncedKey($ymd), (string)$pvTotal);
                    $synced++;
                }

                // 非今天且超过保留期的日 key：数据已落库，清理 Redis 释放内存
                if ($ymd < $today && $this->isOlderThanKeep($ymd)) {
                    $this->redis->del([VisitKeys::pvKey($ymd), VisitKeys::uvKey($ymd)]);
                }
            }
        } catch (\Throwable $e) {
            $output->writeln('<error>同步失败：' . $e->getMessage() . '</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $output->writeln('访问统计同步完成，共更新 ' . $synced . ' 天。');
        return ExitCode::OK;
    }

    /**
     * 收集 Redis 中所有 visit:pv:{Ymd} 的日期。
     *
     * @return list<string>
     */
    private function collectDates(): array
    {
        $dates = [];
        $cursor = 0;
        do {
            /** @var array{0: string, 1: list<string>} $result */
            $result = $this->redis->scan($cursor, ['match' => VisitKeys::PV_PREFIX . '*', 'count' => 500]);
            $cursor = (int)$result[0];
            foreach ($result[1] as $key) {
                $ymd = substr($key, strlen(VisitKeys::PV_PREFIX));
                if (preg_match('/^\d{8}$/', $ymd)) {
                    $dates[] = $ymd;
                }
            }
        } while ($cursor !== 0);
        sort($dates);
        return $dates;
    }

    private function ymdToDate(string $ymd): string
    {
        return substr($ymd, 0, 4) . '-' . substr($ymd, 4, 2) . '-' . substr($ymd, 6, 2);
    }

    private function isOlderThanKeep(string $ymd): bool
    {
        $keepFromTs = strtotime('-' . self::KEEP_REDIS_DAYS . ' days');
        if ($keepFromTs === false) {
            return false;
        }
        $keepFrom = date('Ymd', $keepFromTs);
        return $ymd < $keepFrom;
    }
}
