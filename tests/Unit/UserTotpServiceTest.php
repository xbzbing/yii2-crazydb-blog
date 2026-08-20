<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\User\UserTotpService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * UserTotpService 单元测试（不依赖 DB）。
 */
final class UserTotpServiceTest extends TestCase
{
    private UserTotpService $service;

    protected function setUp(): void
    {
        $this->service = new UserTotpService();
    }

    #[Test]
    public function generateSecretReturnsBase32String(): void
    {
        $secret = $this->service->generateSecret();
        self::assertNotEmpty($secret);
        self::assertMatchesRegularExpression('/^[A-Z2-7=]+$/', $secret);
        self::assertGreaterThanOrEqual(32, strlen($secret));
    }

    #[Test]
    public function provisioningUriIsValidOtpauthUri(): void
    {
        $secret = $this->service->generateSecret();
        $uri = $this->service->provisioningUri($secret, 'admin');
        self::assertStringStartsWith('otpauth://totp/', $uri);
        self::assertStringContainsString('CrazyDB', $uri);
        self::assertStringContainsString('admin', $uri);
        self::assertStringContainsString('secret=', $uri);
    }

    #[Test]
    public function verifyCodeRejectsEmptyCode(): void
    {
        $secret = $this->service->generateSecret();
        self::assertFalse($this->service->verifyCode($secret, ''));
        self::assertFalse($this->service->verifyCode($secret, '123'));
    }

    #[Test]
    public function verifyCodeRejectsEmptySecretWithoutThrowing(): void
    {
        // 防御性边界：空/非法 secret 不得抛异常（登录热路径不能 500）
        self::assertFalse($this->service->verifyCode('', '123456'));
        self::assertFalse($this->service->verifyCode('!!!not-base32!!!', '123456'));
    }

    #[Test]
    public function verifyCodeRejectsWrongCode(): void
    {
        $secret = $this->service->generateSecret();
        self::assertFalse($this->service->verifyCode($secret, '000000'));
    }

    #[Test]
    public function verifyCodeAcceptsValidCode(): void
    {
        $secret = $this->service->generateSecret();
        // 通过 enable 流程获取 uri，从中提取 secret 对应的 TOTP 实例来生成有效码
        $uri = $this->service->provisioningUri($secret, 'test');
        // 从 uri 中提取 secret 参数来构造 TOTP
        $parsed = parse_url($uri);
        $query = [];
        parse_str($parsed['query'] ?? '', $query);
        // 使用 UserTotpService 的私有 createTotp 不可行，改用 TOTP::createFromSecret
        $totp = \OTPHP\TOTP::createFromSecret($secret);
        $validCode = $totp->now();
        self::assertMatchesRegularExpression('/^\d{6}$/', $validCode);
        self::assertTrue($this->service->verifyCode($secret, $validCode));
    }

    #[Test]
    public function verifyCodeRejectsCodeFromFarWindow(): void
    {
        $secret = $this->service->generateSecret();
        $totp = \OTPHP\TOTP::createFromSecret($secret);
        $now = time();
        $currentCode = $totp->at($now);
        self::assertTrue($this->service->verifyCode($secret, $currentCode), '当前窗口码应被接受');
        $oldCode = $totp->at($now - 2 * UserTotpService::PERIOD);
        self::assertFalse($this->service->verifyCode($secret, $oldCode), '2 个周期前的码应被拒绝');
    }

    #[Test]
    public function verifyCodeAcceptsPreviousWindowCode(): void
    {
        $secret = $this->service->generateSecret();
        $totp = \OTPHP\TOTP::createFromSecret($secret);
        // 固定基准时间，消除两次 time() 之间的 TOTP 窗口边界抖动
        $now = time();
        // WINDOW=29（秒粒度遍历）应兼容约一个周期前的码（手机时钟偏慢场景）
        $prevCode = $totp->at($now - UserTotpService::PERIOD);
        self::assertTrue($this->service->verifyCode($secret, $prevCode, $now), '1 个周期前的码应被接受');
    }

    #[Test]
    public function enableReturnsSecretAndUri(): void
    {
        $result = $this->service->enable('testuser');
        self::assertArrayHasKey('secret', $result);
        self::assertArrayHasKey('uri', $result);
        self::assertNotEmpty($result['secret']);
        self::assertStringStartsWith('otpauth://totp/', $result['uri']);
        self::assertStringContainsString('testuser', $result['uri']);
    }

    #[Test]
    public function constantsMatchRfc6238Defaults(): void
    {
        self::assertSame(6, UserTotpService::DIGITS);
        self::assertSame(30, UserTotpService::PERIOD);
        self::assertLessThan(UserTotpService::PERIOD, UserTotpService::WINDOW);
        self::assertGreaterThanOrEqual(24, UserTotpService::SECRET_BYTES);
    }
}
