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
 * 访问统计同步（V2）：把 Redis 实时 PV/UV/IP/分类 PV 增量落库到 visit_daily。
 *
 * 建议每 10 分钟执行一次（crontab 调用 php yii visit/sync）。
 * - PV/爬虫/脚本：增量（当前总数 - 已同步游标）
 * - UV/IP：HyperLogLog PFCOUNT 全量覆盖（无法增量）
 * - 小时 key 不参与同步（Redis-only，TTL 48h 自动清理）
 */
#[AsCommand(
    name: 'visit/sync',
    description: '同步访问统计到 MySQL（Redis crazydb:visit:* → visit_daily）',
)]
final class VisitSyncCommand extends Command
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
            $dates = $this->collectDates();
            foreach ($dates as $ymd) {
                $pvTotal = $this->countOf(VisitKeys::pvKey($ymd));
                $uvTotal = $this->redis->pfcount(VisitKeys::uvKey($ymd));
                $ipTotal = $this->redis->pfcount(VisitKeys::ipKey($ymd));
                $crawlerTotal = $this->countOf(VisitKeys::crawlerKey($ymd));
                $scriptTotal = $this->countOf(VisitKeys::scriptKey($ymd));

                // 增量：本次新增 PV = 当前总数 - 已同步游标（首同步游标为 0）
                $deltaPv = max(0, $pvTotal - $this->prevOf(VisitKeys::syncedKey($ymd)));
                $deltaCrawler = max(0, $crawlerTotal - $this->prevOf(VisitKeys::crawlerSyncedKey($ymd)));
                $deltaScript = max(0, $scriptTotal - $this->prevOf(VisitKeys::scriptSyncedKey($ymd)));

                if ($deltaPv > 0 || $uvTotal > 0 || $ipTotal > 0 || $deltaCrawler > 0 || $deltaScript > 0) {
                    VisitDaily::upsertByDate(
                        $this->ymdToDate($ymd),
                        $deltaPv,
                        $uvTotal,
                        $ipTotal,
                        $deltaCrawler,
                        $deltaScript,
                    );
                    $this->redis->set(VisitKeys::syncedKey($ymd), (string)$pvTotal);
                    $this->redis->set(VisitKeys::crawlerSyncedKey($ymd), (string)$crawlerTotal);
                    $this->redis->set(VisitKeys::scriptSyncedKey($ymd), (string)$scriptTotal);
                    $synced++;
                }

                // 非今天且超过保留期的日 key：数据已落库，清理 Redis 释放内存
                if ($ymd < $today && $this->isOlderThanKeep($ymd)) {
                    $this->redis->del([
                        VisitKeys::pvKey($ymd),
                        VisitKeys::uvKey($ymd),
                        VisitKeys::ipKey($ymd),
                        VisitKeys::crawlerKey($ymd),
                        VisitKeys::scriptKey($ymd),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $output->writeln('<error>同步失败：' . $e->getMessage() . '</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $output->writeln('访问统计同步完成，共更新 ' . $synced . ' 天。');
        return ExitCode::OK;
    }

    private function countOf(string $key): int
    {
        $raw = $this->redis->get($key);
        return $raw === null ? 0 : (int)$raw;
    }

    private function prevOf(string $key): int
    {
        return $this->countOf($key);
    }

    /**
     * @return list<string> 所有 visit:pv:{Ymd} 的日期列表（YYYYMMDD）
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
