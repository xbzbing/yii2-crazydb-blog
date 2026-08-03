<?php

declare(strict_types=1);

namespace App\Captcha;

use Yiisoft\Session\Session;

/**
 * 简易验证码：GD 绘制 + session 存储（D7 决策：yiisoft/captcha 不存在，自实现）。
 * dev 模式（CAPTCHA_DEBUG=1）下验证直接通过，便于测试绕过。
 */
final class CaptchaService
{
    public const SESSION_KEY = 'captcha_code';
    private const CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // 去除易混淆字符
    private const LENGTH = 4;

    public function __construct(
        private Session $session,
        private bool $debug = false,
    ) {
    }

    /**
     * 生成验证码图片（PNG）。
     */
    public function generate(int $width = 120, int $height = 40): string
    {
        $code = '';
        $max = strlen(self::CHARSET) - 1;
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::CHARSET[random_int(0, $max)];
        }
        $this->session->set(self::SESSION_KEY, $code);

        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new \RuntimeException('Unable to create captcha image (GD imagecreatetruecolor failed).');
        }
        $bg = imagecolorallocate($image, 245, 245, 245);
        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        for ($i = 0; $i < 5; $i++) {
            $line = imagecolorallocate($image, random_int(0, 200), random_int(0, 200), random_int(0, 200));
            imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $line);
        }
        for ($i = 0; $i < 100; $i++) {
            $dot = imagecolorallocate($image, random_int(100, 220), random_int(100, 220), random_int(100, 220));
            imagesetpixel($image, random_int(0, $width), random_int(0, $height), $dot);
        }

        $x = 8;
        foreach (str_split($code) as $char) {
            $color = imagecolorallocate($image, random_int(0, 80), random_int(0, 80), random_int(0, 80));
            imagestring($image, 5, $x, random_int(5, 12), $char, $color);
            $x += intdiv($width - 16, self::LENGTH);
        }

        ob_start();
        imagepng($image);
        return (string) ob_get_clean();
    }

    /**
     * 验证验证码（大小写不敏感，验证后立即清除防重放）。
     */
    public function validate(string $input): bool
    {
        if ($this->debug) {
            return true;
        }
        $expected = $this->session->get(self::SESSION_KEY);
        $this->session->remove(self::SESSION_KEY);
        return is_string($expected) && $expected !== ''
            && strcasecmp($expected, $input) === 0;
    }
}
