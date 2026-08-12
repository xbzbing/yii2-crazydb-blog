<?php

declare(strict_types=1);

namespace App\Web\ToolCaptcha;

use App\Captcha\CaptchaService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 验证码图片输出（等价 Yii2 SiteController::actionCaptcha）。
 */
final readonly class Action
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private CaptchaService $captcha,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($this->captcha->generate());
        return $response
            ->withHeader('Content-Type', 'image/png')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache');
    }
}
