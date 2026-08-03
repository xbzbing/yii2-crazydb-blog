<?php

declare(strict_types=1);

namespace App\Common;

use HTMLPurifier;
use HTMLPurifier_Config;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Session\Flash\Flash;

final class XUtils
{
    /**
     * 格式化时间：三天内显示具体发布时间，超过三天仅显示发布日期。
     */
    public static function xDateFormatter(int $timestamp, int $limit = 3): string
    {
        $time = $limit * 24 * 60 * 60 + $timestamp;
        if ($time > time()) {
            return date('m-d H:i', $timestamp);
        }
        return date('Y-m-d', $timestamp);
    }

    /**
     * 格式化容量显示。
     */
    public static function dataFormat(float $size, int $dec = 2): string
    {
        $a = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $pos = 0;
        while ($size >= 1024 && $pos < count($a) - 1) {
            $size /= 1024;
            $pos++;
        }
        return round($size, $dec) . ' ' . $a[$pos];
    }

    /**
     * 截取字符串，并自动闭合 html 标签。
     */
    public static function strimwidthWithTag(
        string $string,
        int $start,
        int $width,
        string $trimmarker = '...',
    ): string {
        $string = mb_strimwidth($string, $start, $width, $trimmarker, 'utf-8');
        return self::htmlPurify($string);
    }

    /**
     * 获取客户端 IP 地址（优先 REMOTE_ADDR，其次转发头，取第一个有效 IP）。
     */
    public static function getClientIP(?array $server = null): string
    {
        $server ??= $_SERVER;
        $ips = [];
        if (isset($server['REMOTE_ADDR'])) {
            $ips[] = $server['REMOTE_ADDR'];
        }
        if (isset($server['HTTP_CLIENT_IP'])) {
            $ips[] = $server['HTTP_CLIENT_IP'];
        }
        if (isset($server['HTTP_X_FORWARDED_FOR'])) {
            $ips = array_merge($ips, explode(',', $server['HTTP_X_FORWARDED_FOR']));
        }
        foreach ($ips as $value) {
            $valid = filter_var(trim((string)$value), FILTER_VALIDATE_IP);
            if ($valid !== false) {
                return $valid;
            }
        }
        return '0.0.0.0';
    }

    /**
     * 富文本过滤。
     *
     * @param string $content
     * @param array<string, mixed> $params HTMLPurifier 配置项
     */
    public static function htmlPurify(string $content, array $params = []): string
    {
        if (isset($params['Attr.AllowedFrameTargets'])) {
            $params['Attr.AllowedFrameTargets'] = ['_blank'];
        }
        $config = HTMLPurifier_Config::createDefault();
        foreach ($params as $key => $value) {
            $config->set($key, $value);
        }
        return (new HTMLPurifier($config))->purify($content);
    }

    /**
     * 获取头像：本地缓存（@public/static/avatar/{md5}-{size}.png），超过三天远程获取 gravatar。
     */
    public static function getAvatar(Aliases $aliases, string $email, int $size = 40): string
    {
        $email = md5($email);
        $fileName = "{$email}-{$size}.png";
        $filePath = $aliases->get('@public') . '/static/avatar/' . $fileName;
        $return = $aliases->get('@baseUrl') . 'static/avatar/' . $fileName;
        $gravatar = "https://en.gravatar.com/avatar/{$email}?s={$size}&r=g";

        if (!is_file($filePath) || (filemtime($filePath) + 3 * 24 * 60 * 60 < time())) {
            try {
                $img = @file_get_contents($gravatar);
                if ($img !== false && !is_dir(dirname($filePath))) {
                    mkdir(dirname($filePath), 0755, true);
                }
                if ($img !== false) {
                    file_put_contents($filePath, $img);
                    return $return;
                }
            } catch (\Throwable $e) {
                return $gravatar;
            }
            return $gravatar;
        }
        return $return;
    }

    /**
     * 向 session 写入操作记录（flash，读取后即失效）。
     */
    public static function actionMessage(Flash $flash, string $key, array $message): void
    {
        $flash->set($key, $message);
    }
}
