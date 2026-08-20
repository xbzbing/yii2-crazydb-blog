<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;
use App\Visit\DeviceId;

final class DeviceIdTest extends TestCase
{
    public function testGenerateReturns40CharString(): void
    {
        $id = DeviceId::generate();
        $this->assertSame(40, strlen($id));
        $this->assertMatchesRegularExpression('/^[0-9A-Za-z]{40}$/', $id);
    }

    public function testGenerateIsRandomized(): void
    {
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $ids[] = DeviceId::generate();
        }
        $this->assertSame(10, count(array_unique($ids)));
    }

    public function testParseValidId(): void
    {
        $id = DeviceId::generate();
        $parsed = DeviceId::parse($id);
        $this->assertTrue($parsed['valid']);
        $this->assertNotNull($parsed['date']);
        $this->assertNotNull($parsed['random']);
        // 日期应为今天（YYYY-MM-DD 格式）
        $this->assertSame(date('Y-m-d'), $parsed['date']);
    }

    public function testCreatedDateReturnsDateOrFalse(): void
    {
        $id = DeviceId::generate();
        $date = DeviceId::createdDate($id);
        $this->assertSame(date('Y-m-d'), $date);
        $this->assertNull(DeviceId::createdDate('invalid'));
    }

    public function testCRCValidationDetectsTamper(): void
    {
        $id = DeviceId::generate();
        $tampered = substr($id, 0, 5) . 'Z' . substr($id, 6);
        $this->assertFalse(DeviceId::parse($tampered)['valid']);
    }

    public function testCRCValidationDetectsTruncation(): void
    {
        $id = DeviceId::generate();
        $this->assertFalse(DeviceId::parse(substr($id, 0, 39))['valid']);
    }

    public function testCRCValidationDetectsTamperedDate(): void
    {
        $id = DeviceId::generate();
        // 把月份改成 13（CRC 不匹配 → 无效）
        $tampered = substr($id, 0, 4) . '13' . substr($id, 6);
        $this->assertFalse(DeviceId::parse($tampered)['valid']);
    }

    public function testCheckdateRejectsInvalidMonth(): void
    {
        // 构造一个 CRC 有效但日期为13月的 ID
        // 先生成一个合法 ID，提取 random，构造日期为 '261320'（月份13，day=20）
        $id = DeviceId::generate();
        $random = '';
        // 索引 0..31 中非日期槽[2,7,12,17,22,27]的位置
        $dateSlots = [2, 7, 12, 17, 22, 27];
        for ($i = 0; $i < 32; $i++) {
            if (!in_array($i, $dateSlots, true)) {
                $random .= $id[$i];
            }
        }
        $badYmd = '261320'; // YYMMDD，月份=13
        $crc = hash('crc32b', $random . $badYmd);
        // 在固定槽位填入日期，再写入 CRC
        $slots = array_fill(0, 40, '');
        $r = 0;
        for ($i = 0; $i < 32; $i++) {
            if (in_array($i, $dateSlots, true)) {
                $d = 0;
                foreach ($dateSlots as $s) {
                    if ($s === $i) { $slots[$i] = $badYmd[$d]; break; }
                    $d++;
                }
            } else {
                $slots[$i] = $random[$r++];
            }
        }
        for ($i = 0; $i < 8; $i++) {
            $slots[32 + $i] = $crc[$i];
        }
        $badId = implode('', $slots);
        // CRC 匹配但 checkdate 月=13 → invalid
        $this->assertSame(40, strlen($badId));
        $this->assertFalse(DeviceId::parse($badId)['valid']);
    }

    public function testCookieValueContainsNameAndMaxAge(): void
    {
        $request = $this->createServerRequest();
        $cookie = DeviceId::cookieValue(DeviceId::generate(), $request);
        $this->assertStringContainsString('dbvid=', $cookie);
        $this->assertStringContainsString('Max-Age=31536000', $cookie);
        $this->assertStringContainsString('HttpOnly', $cookie);
        $this->assertStringContainsString('SameSite=Lax', $cookie);
    }

    public function testCookieValueIncludesSecureForHttps(): void
    {
        $request = $this->createServerRequest('https');
        $cookie = DeviceId::cookieValue(DeviceId::generate(), $request);
        $this->assertStringContainsString('Secure', $cookie);
    }

    public function testCookieValueExcludesSecureForHttp(): void
    {
        $request = $this->createServerRequest('http');
        $cookie = DeviceId::cookieValue(DeviceId::generate(), $request);
        $this->assertStringNotContainsString('Secure', $cookie);
    }

    private function createServerRequest(string $scheme = 'http'): \Psr\Http\Message\ServerRequestInterface
    {
        return (new \HttpSoft\Message\ServerRequestFactory())
            ->createServerRequest('GET', "{$scheme}://localhost/");
    }
}
