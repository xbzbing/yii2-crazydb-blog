<?php

declare(strict_types=1);

namespace App\Visit;

use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * 按日访问统计（前台 PV/UV/IP 聚合）。
 *
 * 数据来源：visit/sync 定时任务把 Redis 实时统计（crazydb:visit:*）增量落库，
 * 供仪表盘历史趋势查询。
 */
final class VisitDaily extends ActiveRecord
{
    public ?int $id = null;
    public string|\DateTimeImmutable $date = '';
    public int $pv = 0;
    public int $uv = 0;
    /** IP 去重数（全部访问，含爬虫/脚本） */
    public int $ip = 0;
    public int $pv_crawler = 0;
    public int $pv_script = 0;
    public int $create_time = 0;
    public int $update_time = 0;

    public function tableName(): string
    {
        return '{{%visit_daily}}';
    }

    /**
     * 按日期 upsert 当天的 PV/UV/IP/分类 PV 聚合（存在则累加/覆盖，不存在则插入）。
     *
     * PV/爬虫/脚本：累加（增量 delta）；UV/IP：全量覆盖（HLL 无法增量）。
     */
    public static function upsertByDate(
        string $date,
        int $pv,
        int $uv,
        int $ip,
        int $pvCrawler = 0,
        int $pvScript = 0,
    ): void {
        $now = time();
        $model = self::query()->where(['date' => $date])->one();
        if ($model instanceof self) {
            $model->pv += max(0, $pv);
            $model->uv = max(0, $uv);
            $model->ip = max(0, $ip);
            $model->pv_crawler += max(0, $pvCrawler);
            $model->pv_script += max(0, $pvScript);
            $model->update_time = $now;
            try {
                $model->save();
            } catch (\Throwable) {
            }
            return;
        }
        $model = new self();
        $model->date = $date;
        $model->pv = max(0, $pv);
        $model->uv = max(0, $uv);
        $model->ip = max(0, $ip);
        $model->pv_crawler = max(0, $pvCrawler);
        $model->pv_script = max(0, $pvScript);
        $model->create_time = $now;
        $model->update_time = $now;
        try {
            $model->save();
        } catch (\Throwable) {
        }
    }

    /**
     * 取某时间区间（含边界）的每日 PV/UV/IP/分类 PV，按日期升序。
     *
     * @return list<array{date: string, pv: int, uv: int, ip: int, pv_crawler: int, pv_script: int}>
     */
    public static function range(string $from, string $to): array
    {
        /** @var list<self> $rows */
        $rows = self::query()
            ->select(['date', 'pv', 'uv', 'ip', 'pv_crawler', 'pv_script'])
            ->where(['>=', 'date', $from])
            ->andWhere(['<=', 'date', $to])
            ->orderBy(['date' => SORT_ASC])
            ->all();
        $result = [];
        foreach ($rows as $row) {
            $date = $row->date instanceof \DateTimeImmutable ? $row->date->format('Y-m-d') : $row->date;
            $result[] = [
                'date' => $date,
                'pv' => $row->pv,
                'uv' => $row->uv,
                'ip' => $row->ip,
                'pv_crawler' => $row->pv_crawler,
                'pv_script' => $row->pv_script,
            ];
        }
        return $result;
    }
}
