<?php

declare(strict_types=1);

namespace App\Web\Register;

use App\Captcha\CaptchaService;
use App\Common\CMSUtils;
use App\Nav\Nav;
use App\Option\Option;
use App\User\AuthService;
use App\User\RegisterService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 注册（等价 Yii2 SiteController::actionRegister）：
 * 注册开关检查 → 表单校验（含验证码）→ RegisterService → flash 后跳登录页。
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
        private AuthService $authService,
        private RegisterService $registerService,
        private CaptchaService $captcha,
        private FlashInterface $flash,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->authService->isLoggedIn()) {
            return $this->redirectHome();
        }

        $siteConfig = CMSUtils::getSiteConfig($this->cache);
        $closed = CMSUtils::getSysConfig($this->cache, Option::ALLOW_REGISTER) !== Option::STATUS_OPEN;
        $errors = [];
        $form = ['username' => '', 'nickname' => '', 'email' => '', 'website' => '', 'info' => ''];

        if (!$closed && $request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            $data = is_array($body) ? $body : [];
            foreach ($form as $field => $_) {
                $form[$field] = trim((string)($data[$field] ?? ''));
            }

            if (!$this->captcha->validate((string)($data['captcha'] ?? ''))) {
                $errors['captcha'] = '验证码错误';
            } else {
                /** @var array{username?: string, nickname?: string, email?: string, password?: string, password_repeat?: string, website?: string, info?: string} $registerData */
                $registerData = $data;
                $result = $this->registerService->register($registerData, (string)($request->getServerParams()['REMOTE_ADDR'] ?? '0.0.0.0'));
                $errors = $result['errors'];
                if ($result['user'] !== null) {
                    $this->flash->set('flash_success', ['info' => '注册成功，请用刚才注册的帐号登录！']);
                    return $this->redirectLogin();
                }
            }
        }

        return $this->viewRenderer->render(
            __DIR__ . '/template',
            [
                'closed' => $closed,
                'errors' => $errors,
                'form' => $form,
                'siteConfig' => $siteConfig,
                'navTree' => Nav::getNavTree($this->cache, $this->urlGenerator),
                'showSidebar' => false,
            ],
        );
    }

    private function redirectHome(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('site/index'));
    }

    private function redirectLogin(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('site/login'));
    }
}
