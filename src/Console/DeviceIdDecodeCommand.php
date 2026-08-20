<?php

declare(strict_types=1);

namespace App\Console;

use App\Visit\DeviceId;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

/**
 * 解码 dbvid 设备 ID，还原创建日期并校验 CRC。
 *
 * Usage:  ./yii visit/decode-vid <dbvid值>
 * 示例：  ./yii visit/decode-vid 6B2ay4J6Ba6A0D99o8tjbe1Ojek973VWb7b7583e
 */
#[AsCommand(
    name: 'visit/decode-vid',
    description: '解码 dbvid 设备 ID，还原创建日期并校验 CRC',
)]
final class DeviceIdDecodeCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('解码 dbvid 设备 ID，还原创建日期并校验 CRC')
            ->addArgument('dbvid', InputArgument::REQUIRED, 'dbvid 设备 ID（40 字符）');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = trim((string)$input->getArgument('dbvid'));

        $parsed = DeviceId::parse($id);

        $output->writeln(sprintf('Cookie 名称：    %s', DeviceId::NAME));
        $output->writeln(sprintf('输入 ID：        %s', $id));
        $output->writeln(sprintf('长度：           %d 字符', strlen($id)));
        $output->writeln(sprintf('校验结果：       %s', $parsed['valid'] ? '✅ 通过' : '❌ 失败'));
        $output->writeln(sprintf('还原日期（20YY）：%s', $parsed['date'] ?? '无法还原'));

        if ($parsed['date'] !== null) {
            $today = date('Y-m-d');
            $todayDt = date_create($today);
            $parsedDt = date_create($parsed['date']);
            if ($todayDt && $parsedDt) {
                $diff = date_diff($todayDt, $parsedDt);
                $days = (int)$diff->format('%r%a');
                $output->writeln(sprintf('距今：           %d 天', abs($days)));
                if ($parsed['date'] > $today) {
                    $output->writeln('⚠️  日期在未来——可能是 YYMMDD 解析错误或跨世纪数据');
                }
            } else {
                $output->writeln('距今：           日期格式异常，无法计算');
            }
        }

        return ExitCode::OK;
    }
}
