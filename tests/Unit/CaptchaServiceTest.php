<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Captcha\CaptchaService;
use App\Tests\TestCase;

final class CaptchaServiceTest extends TestCase
{

    public function testGenerateStoresCodeInSessionAndReturnsPng(): void
    {
        $service = new CaptchaService($this->sharedSession(), false);
        $session = $this->sharedSession();
        $session->open();
        try {
            $png = $service->generate();
            self::assertStringStartsWith("\x89PNG", $png);
            $code = $session->get(CaptchaService::SESSION_KEY);
            self::assertIsString($code);
            self::assertSame(4, strlen($code));
        } finally {
            $session->remove(CaptchaService::SESSION_KEY);
        }
    }

    public function testValidateMatchesCaseInsensitivelyAndConsumesOnce(): void
    {
        $service = new CaptchaService($this->sharedSession(), false);
        $session = $this->sharedSession();
        $session->open();
        $session->set(CaptchaService::SESSION_KEY, 'AbCd');
        try {
            self::assertTrue($service->validate('abcd'));
            self::assertNull($session->get(CaptchaService::SESSION_KEY), 'code consumed after successful validation');
            self::assertFalse($service->validate('abcd'), 'second validation must fail');
        } finally {
            $session->remove(CaptchaService::SESSION_KEY);
        }
    }

    public function testValidateWrongCode(): void
    {
        $service = new CaptchaService($this->sharedSession(), false);
        $session = $this->sharedSession();
        $session->open();
        $session->set(CaptchaService::SESSION_KEY, 'AbCd');
        try {
            self::assertFalse($service->validate('XyZz'));
            self::assertNull(
                $session->get(CaptchaService::SESSION_KEY),
                'wrong input must consume code (anti replay)',
            );
        } finally {
            $session->remove(CaptchaService::SESSION_KEY);
        }
    }

    public function testDebugModeBypassesValidation(): void
    {
        $service = new CaptchaService($this->sharedSession(), true);
        $session = $this->sharedSession();
        $session->open();
        try {
            self::assertTrue($service->validate('whatever'));
            self::assertTrue($service->validate(''), 'debug mode bypasses entirely');
        } finally {
            $session->remove(CaptchaService::SESSION_KEY);
        }
    }

    public function testCharsetExcludesConfusableCharacters(): void
    {
        $service = new CaptchaService($this->sharedSession(), false);
        $session = $this->sharedSession();
        $session->open();
        try {
            for ($i = 0; $i < 50; $i++) {
                $service->generate();
                $code = (string) $session->get(CaptchaService::SESSION_KEY);
                self::assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{4}$/', $code);
            }
        } finally {
            $session->remove(CaptchaService::SESSION_KEY);
        }
    }
}
