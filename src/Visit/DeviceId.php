<?php

declare(strict_types=1);

namespace App\Visit;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 设备 ID 生成与校验（算法 C：26 随机 + YYMMDD + 8 CRC-32 = 40 字符）。
 *
 * 格式为固定槽位的"可还原日期 + 自校验"构造，仅用于统计去重（UV），
 * 不参与认证/授权——算法本身是防御性健壮设计，非安全机制。
 *
 * 日期还原控制台命令：see Console\DeviceIdDecodeCommand。
 */
final class DeviceId
{
    public const NAME = 'dbvid';
    /** 有效期秒数：365 天 */
    public const MAX_AGE = 86400 * 365;

    /** 日期槽位（6 个，YYMMDD）：索引 0..31 内每隔 5 位一个 */
    private const DATE_SLOTS = [2, 7, 12, 17, 22, 27];

    /** CRC 槽位：索引 32..39（末 8 位 hex） */
    private const CRC_START = 32;
    private const CRC_LEN = 8;

    /** 随机槽位数 = 32 - 6 = 26 */
    private const RAND_LEN = 26;

    /** 随机字符集（62 元） */
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * 生成一个新的设备 ID。
     */
    public static function generate(): string
    {
        $date = date('ymd');
        $s = array_fill(0, 40, '');
        $random = '';

        // 填充随机槽（索引 0..31 中非日期槽的位置）
        $rand = self::randomString(self::RAND_LEN);
        $r = 0;
        for ($i = 0; $i < 32; $i++) {
            if (!in_array($i, self::DATE_SLOTS, true)) {
                $s[$i] = $rand[$r++];
            }
        }
        // 填充日期槽（间隔插入）
        $d = 0;
        foreach (self::DATE_SLOTS as $slot) {
            $s[$slot] = $date[$d++];
        }
        // 填充 CRC 槽（CRC-32 → 8 hex）
        $crc = hash('crc32b', $rand . $date);
        for ($i = 0; $i < self::CRC_LEN; $i++) {
            $s[self::CRC_START + $i] = $crc[$i];
        }
        return implode('', $s);
    }

    /**
     * 校验并解析设备 ID。
     *
     * @return array{date: string, random: string, valid: bool}|array{date: null, random: null, valid: false}
     */
    public static function parse(string $id): array
    {
        $empty = ['date' => null, 'random' => null, 'valid' => false];
        if (strlen($id) !== 40) {
            return $empty;
        }
        // 字符集检查：0-9, a-z, A-Z
        if (!preg_match('/^[0-9A-Za-z]{40}$/', $id)) {
            return $empty;
        }

        // 提取随机槽（索引 0..31 中非日期槽的位置）
        $random = '';
        for ($i = 0; $i < 32; $i++) {
            if (!in_array($i, self::DATE_SLOTS, true)) {
                $random .= $id[$i];
            }
        }

        // 提取日期槽 → YYMMDD
        $ymd = '';
        foreach (self::DATE_SLOTS as $slot) {
            $ymd .= $id[$slot];
        }

        // CRC 校验（CRC-32，与生成算法同源）
        $crcStored = substr($id, self::CRC_START, self::CRC_LEN);
        $crcCalc = hash('crc32b', $random . $ymd);
        if (!hash_equals($crcCalc, $crcStored)) {
            return $empty;
        }

        // 世纪约定：20YY（设备 ID 均为当代生成，2100 后需调整）
        $yy = (int)substr($ymd, 0, 2);
        $month = (int)substr($ymd, 2, 2);
        $day = (int)substr($ymd, 4, 2);
        $year = 2000 + $yy;

        // 校验日期合法性（月 01-12，日 01-31），防止伪造 CRC 后的非法日期崩溃下游
        if (!checkdate($month, $day, $year)) {
            return $empty;
        }

        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

        // 未来日期无效（CRC 公开可自算，攻击者可能伪造未来日期 ID）
        if ($date > date('Y-m-d')) {
            return $empty;
        }

        return ['date' => $date, 'random' => $random, 'valid' => true];
    }

    /**
     * 从设备 ID 提取日期（格式 YYYY-MM-DD，已补全世纪）。
     * 失败返回 null。
     */
    public static function createdDate(string $id): ?string
    {
        $parsed = self::parse($id);
        return $parsed['valid'] ? $parsed['date'] : null;
    }

    /**
     * 构造 Set-Cookie 头值。
     *
     * @param bool $forceSecure 强制加 Secure（生产 https 部署开启；见 COOKIE_SECURE 配置）
     */
    public static function cookieValue(string $deviceId, ServerRequestInterface $request, bool $forceSecure = false): string
    {
        $secure = $forceSecure || str_starts_with($request->getUri()->getScheme(), 'https');
        $parts = [
            self::NAME . '=' . urlencode($deviceId),
            'Path=/',
            'Max-Age=' . self::MAX_AGE,
            'SameSite=Lax',
            'HttpOnly',
        ];
        if ($secure) {
            $parts[] = 'Secure';
        }
        return implode('; ', $parts);
    }

    /**
     * 从请求 cookie 解析设备 ID，若无则生成新 ID。
     * 不操纵 response（可在 handle 前调用）。
     *
     * @return array{id: string, needsCookie: bool}
     */
    public static function resolve(ServerRequestInterface $request): array
    {
        $existing = (string)($request->getCookieParams()[self::NAME] ?? '');
        if ($existing !== '') {
            $parsed = self::parse($existing);
            if ($parsed['valid']) {
                return ['id' => $existing, 'needsCookie' => false];
            }
        }
        return ['id' => self::generate(), 'needsCookie' => true];
    }

    /**
     * 生成指定长度的随机字符串（字母+数字）。
     */
    private static function randomString(int $length): string
    {
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= self::ALPHABET[random_int(0, 61)];
        }
        return $result;
    }
}
