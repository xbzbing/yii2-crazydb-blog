<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Common\CMSUtils;
use App\Common\Common;
use App\Common\XUtils;
use App\Option\Option;
use App\Tests\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Session\Flash\Flash;

final class CommonComponentsTest extends TestCase
{

    public function testXDateFormatterWithinLimit(): void
    {
        $past = time() - 60 * 60;
        self::assertMatchesRegularExpression('/^\d{2}-\d{2} \d{2}:\d{2}$/', XUtils::xDateFormatter($past));
    }

    public function testXDateFormatterBeyondLimit(): void
    {
        $past = time() - 4 * 24 * 60 * 60;
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', XUtils::xDateFormatter($past));
    }

    public function testDataFormat(): void
    {
        self::assertSame('1 KB', XUtils::dataFormat(1024));
        self::assertSame('1.5 MB', XUtils::dataFormat(1024 * 1024 * 1.5));
    }

    public function testStrimwidthWithTagTrims(): void
    {
        self::assertSame('...', XUtils::strimwidthWithTag('一二三四五六', 0, 4));
        self::assertSame('a...', XUtils::strimwidthWithTag('abc一二三', 0, 4));
    }

    public function testGetClientIPPrefersRemoteAddr(): void
    {
        $ip = XUtils::getClientIP(['REMOTE_ADDR' => '203.0.113.7', 'HTTP_X_FORWARDED_FOR' => '198.51.100.2']);
        self::assertSame('203.0.113.7', $ip);
    }

    public function testGetClientIPFallsBackToForwarded(): void
    {
        $ip = XUtils::getClientIP(['HTTP_X_FORWARDED_FOR' => '203.0.113.9, 198.51.100.2']);
        self::assertSame('203.0.113.9', $ip);
    }

    public function testGetClientIPDefaults(): void
    {
        $ip = XUtils::getClientIP(['REMOTE_ADDR' => 'not-an-ip']);
        self::assertSame('0.0.0.0', $ip);
    }

    public function testHtmlPurifyStripsScript(): void
    {
        $out = XUtils::htmlPurify('<p>hello</p><script>alert(1)</script>');
        self::assertSame('<p>hello</p>', $out);
    }

    public function testGetAvatarReturnsLocalPath(): void
    {
        $aliases = $this->aliases;
        $email = 'test@example.com';
        $file = $aliases->get('@public') . '/static/avatar/' . md5($email) . '-40.png';
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }
        if (!is_file($file)) {
            file_put_contents($file, '');
        }
        self::assertStringContainsString(md5($email), XUtils::getAvatar($aliases, $email));
    }

    public function testGetSmiliesHasExpectedNames(): void
    {
        $smilies = CMSUtils::getSmilies();
        self::assertContains('smile', $smilies);
        self::assertContains('cool', $smilies);
        self::assertCount(22, $smilies);
    }

    public function testGetSiteConfigReadsOptionTable(): void
    {
        $seed = ['type' => 'sys', 'name' => '__test_key__', 'value' => '__test_value__'];
        $option = new Option();
        $option->type = $seed['type'];
        $option->name = $seed['name'];
        $option->value = $seed['value'];
        $option->save();

        try {
            $cache = new ArrayCache();
            $config = CMSUtils::getSiteConfig($cache);
            self::assertSame('__test_value__', $config['__test_key__']);
            self::assertSame('__test_value__', CMSUtils::getSysConfig($cache, '__test_key__'));

            $option->value = '__updated__';
            $option->save();
            $refresh = CMSUtils::getSiteConfig($cache, 'sys', true);
            self::assertSame('__updated__', $refresh['__test_key__']);
        } finally {
            $option->delete();
        }
    }

    public function testActionMessageWritesFlash(): void
    {
        $session = $this->sharedSession();
        XUtils::actionMessage(new Flash($session), 'op', ['action' => 'delete', 'status' => 'ok']);

        $flash = new Flash($session);
        self::assertSame(['action' => 'delete', 'status' => 'ok'], $flash->get('op'));

        $nextRequest = new Flash($session);
        self::assertNull($nextRequest->get('op'), 'flash should be consumed on the next request');
    }

    public function testLanguageSessionRoundtrip(): void
    {
        $session = $this->sharedSession();
        Common::setLanguage($session, 'zh-CN');
        self::assertSame('zh-CN', Common::getLanguage($session));
    }

    public function testGetThemeList(): void
    {
        $dir = sys_get_temp_dir() . '/__themes_' . bin2hex(random_bytes(4));
        mkdir($dir . '/magazine', 0755, true);
        mkdir($dir . '/.hidden', 0755, true);
        touch($dir . '/file.txt');

        try {
            self::assertSame(['magazine' => 'magazine'], CMSUtils::getThemeList($dir));
        } finally {
            unlink($dir . '/file.txt');
            rmdir($dir . '/.hidden');
            rmdir($dir . '/magazine');
            rmdir($dir);
        }
    }
}
