<?php

declare(strict_types=1);

namespace App\Web\Placeholder;

use App\Web\NotFound\NotFoundResponder;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Placeholder handler for routes whose controllers are ported in later phases.
 * Returns 404 so the URL structure stays locked without exposing a 500.
 */
final class NotFoundAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return NotFoundResponder::respond($this->viewRenderer, $this->responseFactory, $this->urlGenerator);
    }
}
