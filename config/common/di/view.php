<?php

declare(strict_types=1);

use Yiisoft\Aliases\Aliases;
use Yiisoft\Definitions\Contract\ReferenceInterface;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Reference;
use Yiisoft\View\Theme;
use Yiisoft\View\WebView;

/** @var array $params */

/**
 * WebView 定义：镜像 yiisoft/view 包 di-web.php 的完整定义（公共参数注入
 * setParameters 不可丢失），并追加 magazine 主题（withTheme）。
 * 注意：config-plugin 对 service 定义是同 key 整体覆盖，不能只写 withTheme。
 */
return [
    WebView::class => [
        '__construct()' => [
            'basePath' => $params['yiisoft/view']['basePath'] === null
                ? null
                : DynamicReference::to(
                    static fn (Aliases $aliases) => $aliases->get($params['yiisoft/view']['basePath'])
                ),
        ],
        'setParameters()' => [$params['yiisoft/view']['parameters']],
        'withRenderers()' => [$params['yiisoft/view']['renderers']],
        'withFallbackExtension()' => [...(array) $params['yiisoft/view']['fallbackExtension']],
        'withTheme()' => [Reference::to(Theme::class)],
        'reset' => function (\Psr\Container\ContainerInterface $container) use ($params) {
            /** @var WebView $this */
            $this->clear();
            $parameters = $params['yiisoft/view']['parameters'];
            foreach ($parameters as $name => $parameter) {
                $parameters[$name] = $parameter instanceof ReferenceInterface
                    ? $parameter->resolve($container)
                    : $parameter;
            }
            $this->setParameters($parameters);
        },
    ],
];
