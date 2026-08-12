<?php

declare(strict_types=1);

namespace App\Visit;

use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * 按日访问统计（前台 PV/UV 聚合）。
 *
 * 数据来源：visit/sync 定时任务把 Redis 实时统计（crazydb:visit:*）增量落库，
 * 供仪表盘历史趋势查询。
 */
final class VisitDaily extends ActiveRecord
{
    public ?int $id = null;
    /** MySQL DATE 列：Yii3 AR 读取时自动 hydrate 为 DateTimeImmutable，写入可用字符串 */
    public string|\DateTimeImmutable $date = '';
    public int $pv = 0;
    public int $uv = 0;
    public int $create_time = 0;
    public int $update_time = 0;

    public function tableName(): string
    {
        return '{{%visit_daily}}';
    }

    /**
     * 按日期 upsert 当天的 PV/UV 聚合（存在则累加，不存在则插入）。
     */
    public static function upsertByDate(string $date, int $pv, int $uv): void
    {
        $now = time();
        $model = self::query()->where(['date' => $date])->one();
        if ($model instanceof self) {
            $model->pv += max(0, $pv);
            $model->uv = max(0, $uv);
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
        $model->create_time = $now;
        $model->update_time = $now;
        try {
            $model->save();
        } catch (\Throwable) {
        }
    }

    /**
     * 取某时间区间（含边界）的每日 PV/UV，按日期升序。
     *
     * @return list<array{date: string, pv: int, uv: int}>
     */
    public static function range(string $from, string $to): array
    {
        /** @var list<self> $rows */
        $rows = self::query()
            ->select(['date', 'pv', 'uv'])
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
            ];
        }
        return $result;
    }
}
