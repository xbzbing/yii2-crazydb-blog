<?php

declare(strict_types=1);

namespace App\Admin\Api\Otp;

use App\Admin\Api\JsonResponse;
use App\User\AuthService;
use App\User\User;
use App\User\UserTotpService;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\SessionInterface;

/**
 * 后台 OTP 管理 API（仅当前登录管理员自己操作）：
 * - GET  /admin/api/otp/status   当前 OTP 状态
 * - POST /admin/api/otp/setup    生成密钥 + provisioning URI
 * - POST /admin/api/otp/enable   验证 TOTP 码并启用
 * - POST /admin/api/otp/disable  验证当前 TOTP 码并禁用
 *
 * setup 生成的 secret/uri 暂存 session，enable 确认后才落库。
 * 会话键使用 UserTotpService::SESSION_SETUP_*（AuthService 登出时同键清理）。
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
        private ResponseFactoryInterface $responseFactory,
        private AuthService $authService,
        private UserTotpService $totpService,
        private SessionInterface $session,
    ) {
    }

    public function status(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return $this->jsonResponse->fail('未登录', 401);
        }
        return $this->jsonResponse->ok([
            'otp_enabled' => (bool)$user->otp_enabled,
        ]);
    }

    /**
     * 生成密钥 + provisioning URI（仅返回一次，前端用 img src 渲染 QR）。
     * 生成的 secret + uri 存 session，待 enable 时落库。
     */
    public function setup(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return $this->jsonResponse->fail('未登录', 401);
        }

        $result = $this->totpService->enable($user->username);

        // secret + uri 存 session，enable 时校验并落库
        $this->session->set(UserTotpService::SESSION_SETUP_SECRET, $result['secret']);
        $this->session->set(UserTotpService::SESSION_SETUP_URI, $result['uri']);

        return $this->jsonResponse->ok([
            'uri' => $result['uri'],
            'secret' => $result['secret'],
        ]);
    }

    /**
     * 返回当前 setup 阶段的 QR 码 PNG（供 <img src> 直接引用）。
     * 该路由在 /admin/api 组内，受 AdminApiGuardMiddleware 登录态保护；
     * GET 请求经 CSRF 中间件白名单放行，无需 token。
     */
    public function qr(ServerRequestInterface $request): ResponseInterface
    {
        $uri = $this->session->get(UserTotpService::SESSION_SETUP_URI);
        if ($uri === null || !is_string($uri) || $uri === '') {
            return $this->jsonResponse->fail('请先执行 setup 获取密钥。');
        }

        $qr = new QRCode(new QROptions([
            // chillerlan v5：outputType 相关常量已弃用（issue #223，暂无替代），
            // 内部仍按 outputType === 'custom' 分发到 outputInterface，故用等价字符串
            'outputType' => 'custom',
            'outputInterface' => QRGdImagePNG::class,
            'eccLevel' => EccLevel::M,
            'imageTransparent' => false,
            'imageBase64' => false,
        ]));

        $pngData = $qr->render($uri);

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'image/png')
            ->withHeader('Cache-Control', 'no-store');
        $response->getBody()->write((string)$pngData);
        return $response;
    }

    /**
     * 启用 OTP：校验用户输入的 TOTP 码，确认后落库。
     */
    public function enable(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return $this->jsonResponse->fail('未登录', 401);
        }

        if ($user->otp_enabled) {
            return $this->jsonResponse->fail('OTP 已启用，请先关闭后再重新开启。');
        }

        $secret = $this->session->get(UserTotpService::SESSION_SETUP_SECRET);
        if ($secret === null || !is_string($secret) || $secret === '') {
            return $this->jsonResponse->fail('请先执行 setup 获取密钥。');
        }

        $body = $request->getParsedBody();
        $code = trim((string)($body['otp_code'] ?? ''));

        if (!$this->totpService->verifyCode($secret, $code)) {
            return $this->jsonResponse->fail('验证码错误，请重试。');
        }

        $user->otp_secret = $secret;
        $user->otp_enabled = 1;
        $user->touch();
        try {
            $user->save();
        } catch (\Throwable) {
            return $this->jsonResponse->fail('保存失败，请稍后再试。');
        }

        // 启用成功后立即清理 setup 临时状态（secret 与 uri 都要清）
        $this->session->remove(UserTotpService::SESSION_SETUP_SECRET);
        $this->session->remove(UserTotpService::SESSION_SETUP_URI);

        return $this->jsonResponse->ok(['message' => 'OTP 二次验证已启用。']);
    }

    /**
     * 禁用 OTP：验证当前 TOTP 码确认后禁用。
     */
    public function disable(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return $this->jsonResponse->fail('未登录', 401);
        }

        if (!$user->otp_enabled) {
            return $this->jsonResponse->fail('OTP 未启用，无需关闭。');
        }

        $body = $request->getParsedBody();
        $code = trim((string)($body['otp_code'] ?? ''));

        if (!$this->totpService->verifyCode((string)$user->otp_secret, $code)) {
            return $this->jsonResponse->fail('验证码错误，请重试。');
        }

        $user->otp_secret = null;
        $user->otp_enabled = 0;
        $user->touch();
        try {
            $user->save();
        } catch (\Throwable) {
            return $this->jsonResponse->fail('保存失败，请稍后再试。');
        }

        return $this->jsonResponse->ok(['message' => 'OTP 二次验证已关闭。']);
    }
}