<?php

declare(strict_types=1);

namespace App\User;

use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;

/**
 * TOTP 二次验证服务：生成密钥、校验码、开启/关闭 OTP。
 *
 * 底层算法委托 spomky-labs/otphp（RFC 6238），本服务仅封装业务逻辑。
 * 防重放由调用方（登录 Action）通过 Redis 缓存实现（otp_last_code.{userId}，TTL=30s）。
 */
final class UserTotpService
{
    /** 码位数 */
    public const DIGITS = 6;
    /** 时间窗口秒数 */
    public const PERIOD = 30;
    /** 容差窗口（秒，必须 < PERIOD） */
    public const WINDOW = 29;
    /** 密钥字节数（24 字节 = 192bit） */
    public const SECRET_BYTES = 24;

    /** session 键：setup 阶段生成的密钥（AuthService 登出清理，Otp Action 读写） */
    public const SESSION_SETUP_SECRET = 'otp_setup_secret';
    /** session 键：setup 阶段生成的 provisioning URI（同上） */
    public const SESSION_SETUP_URI = 'otp_setup_uri';

    /**
     * 生成随机密钥（Base32 编码，无填充）。
     */
    public function generateSecret(): string
    {
        return Base32::encodeUpper(random_bytes(self::SECRET_BYTES));
    }

    /**
     * 生成 otpauth:// URI，供前端 QR 渲染。
     *
     * @param string $secret Base32 编码的密钥（空值由 createTotp 抛异常兜底）
     * @param string $username 用户名（QR 扫码后显示的账户名）
     */
    public function provisioningUri(string $secret, string $username): string
    {
        $totp = $this->createTotp($secret);
        $totp->setIssuer('CrazyDB Blog');
        if ($username !== '') {
            $totp->setLabel($username);
        }
        return $totp->getProvisioningUri();
    }

    /**
     * 校验 TOTP 码（容差 ±WINDOW 秒，秒粒度遍历，可能触及相邻窗口）。
     *
     * 防御性边界：空/非法 secret 或异常一律返回 false，不向调用方抛出
     * （避免 otphp 的 SecretDecodingException 在登录热路径上变成 500）。
     *
     * @param string $secret Base32 密钥（空值返回 false）
     * @param string $code 6 位数字码
     * @param int|null $now 验证基准时间戳（默认当前时间；测试传固定值可消除时钟边界抖动）
     */
    public function verifyCode(string $secret, string $code, ?int $now = null): bool
    {
        $code = trim($code);
        if ($secret === '' || $code === '' || strlen($code) !== self::DIGITS) {
            return false;
        }
        try {
            $totp = $this->createTotp($secret);
            // OTPHP 要求时间戳非负；$now 来自 time()（恒 ≥0）或 null，这里 clamp 以符合其类型约束
            $verifyNow = $now !== null ? max(0, $now) : null;
            return $totp->verify($code, $verifyNow, self::WINDOW);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 开启 OTP：验证一次码后返回密钥和 URI（由调用方负责持久化）。
     *
     * @return array{secret: string, uri: string}
     */
    public function enable(string $username): array
    {
        $secret = $this->generateSecret();
        return [
            'secret' => $secret,
            'uri' => $this->provisioningUri($secret, $username),
        ];
    }

    /**
     * 创建 TOTP 实例（统一参数）。
     */
    private function createTotp(string $secret): TOTP
    {
        if ($secret === '') {
            throw new \InvalidArgumentException('TOTP secret must not be empty.');
        }
        return TOTP::create(
            secret: $secret,
            period: self::PERIOD,
            digits: self::DIGITS,
            digest: 'sha1',
        );
    }
}
