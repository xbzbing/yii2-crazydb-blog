<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Yiisoft\Definitions\ReferencesArray;
use Yiisoft\Log\Logger;
use Yiisoft\Log\StreamTarget;
use Yiisoft\Log\Target\File\FileTarget;

/** @var array $params */

return [
    LoggerInterface::class => [
        'class' => Logger::class,
        '__construct()' => [
            'targets' => ReferencesArray::from([
                // 文件目标：error 级落盘 runtime/logs/app.log（Yii3 规范位置，10MB×5 轮转），
                // DI 定义与默认参数由 yiisoft/log-target-file 包自带 config 提供
                FileTarget::class,
                // 流目标：保持原有行为，全量输出到 stdout（docker logs 可见）
                StreamTarget::class,
            ]),
        ],
    ],
];
