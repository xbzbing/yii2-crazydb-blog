<?php

declare(strict_types=1);

namespace App\Web\NotFound;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 渲染 404 页面并返回 404 响应（Yii3 无 Action 内 NotFoundException，
 * 统一用 404 Response 表达未找到资源）。
 */
final class NotFoundResponder
{
    public static function respond(
        WebViewRenderer $viewRenderer,
        ResponseFactoryInterface $responseFactory,
        UrlGeneratorInterface $urlGenerator,
    ): ResponseInterface {
        $html = $viewRenderer->renderAsString(
            dirname(__DIR__) . '/NotFound/template',
            ['urlGenerator' => $urlGenerator],
        );
        $response = $responseFactory->createResponse(Status::NOT_FOUND);
        $response->getBody()->write($html);
        return $response
            ->withHeader('Content-Type', 'text/html; charset=UTF-8')
            ->withHeader('Cache-Control', 'no-cache');
    }
}
