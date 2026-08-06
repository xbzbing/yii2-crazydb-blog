<?php

declare(strict_types=1);

namespace App\Common;

use HTMLPurifier;
use HTMLPurifier_Config;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Session\Flash\Flash;

final class XUtils
{
    /** @var array<string, HTMLPurifier> */
    private static array $htmlPurifiers = [];

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
     * 获取客户端 IP。仅当直接连接地址属于明确的可信代理集合时，才解析 X-Forwarded-For。
     *
     * @param list<string>|null $trustedProxyIps null 时读取 TRUSTED_PROXY_IPS（逗号分隔）
     */
    public static function getClientIP(?array $server = null, ?array $trustedProxyIps = null): string
    {
        $server ??= $_SERVER;
        $remote = filter_var(trim((string) ($server['REMOTE_ADDR'] ?? '')), FILTER_VALIDATE_IP);
        if ($remote === false) {
            return '0.0.0.0';
        }

        $trustedProxyIps ??= array_values(array_filter(array_map(
            'trim',
            explode(',', (string) ($_ENV['TRUSTED_PROXY_IPS'] ?? getenv('TRUSTED_PROXY_IPS') ?: '')),
        )));
        if (!in_array($remote, $trustedProxyIps, true)) {
            return $remote;
        }

        foreach (explode(',', (string) ($server['HTTP_X_FORWARDED_FOR'] ?? '')) as $value) {
            $forwarded = filter_var(trim($value), FILTER_VALIDATE_IP);
            if ($forwarded !== false) {
                return $forwarded;
            }
        }

        return $remote;
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
        ksort($params);
        $cacheKey = hash('sha256', serialize($params));
        if (!isset(self::$htmlPurifiers[$cacheKey])) {
            $config = HTMLPurifier_Config::createDefault();
            foreach ($params as $key => $value) {
                $config->set($key, $value);
            }
            self::$htmlPurifiers[$cacheKey] = new HTMLPurifier($config);
        }

        return self::$htmlPurifiers[$cacheKey]->purify($content);
    }

    /**
     * 生成 URL 别名（对齐 Yii2 beforeSave：空时用标题，空格/%/斜杠归一化为连字符，去标签转义）。
     */
    public static function generateAlias(string $text): string
    {
        $alias = $text === '' ? 'untitled' : $text;
        $alias = str_replace([' ', '%', '/', '\\'], ['-', '-', '-', '-'], trim($alias));
        $alias = strip_tags($alias);
        return htmlspecialchars($alias, ENT_QUOTES);
    }

    /**
     * 获取头像 URL。
     *
     * 逻辑（对齐 Yii2 getAvatar + gravatar 不可达兜底）：
     * 1. 本地缓存存在且新鲜（<3 天）→ 直接返回本地 URL；
     * 2. 缓存过期 → 尝试远程 gravatar：成功则更新缓存返回本地；
     *    失败则继续使用本地旧缓存（避免裂图）；
     * 3. 无本地缓存 → 尝试远程：成功保存返回本地；
     *    失败则回退本地默认头像（/static/images/avatar/default-{size}.png，不裂图）；
     * 4. 同一 email 存在其他尺寸缓存时，用 GD 就近缩放生成目标尺寸，
     *    减少 gravatar 请求（尤其 gravatar 被墙环境下不裂图）。
     */
    public static function getAvatar(Aliases $aliases, string $email, int $size = 40): string
    {
        $email = md5($email);
        $fileName = "{$email}-{$size}.png";
        $avatarDir = $aliases->get('@public') . '/static/avatar';
        $filePath = $avatarDir . '/' . $fileName;
        // 注意：@baseUrl 运行时可能解析为空串（站点在根域），须用 '@baseUrl/...' 拼接模式，
        // 直接字符串拼接会生成相对路径（/archive/ 下渲染时 404）。
        $return = $aliases->get('@baseUrl/static/avatar/' . $fileName);
        $gravatar = "https://en.gravatar.com/avatar/{$email}?s={$size}&r=g";

        // 1. 新鲜缓存直接返回
        if (is_file($filePath) && filemtime($filePath) + 3 * 24 * 60 * 60 >= time()) {
            return $return;
        }

        // 2/3. 尝试远程获取（过期或无缓存都尝试刷新）
        $img = self::fetchRemote($gravatar);
        if ($img !== null) {
            if (!is_dir($avatarDir)) {
                mkdir($avatarDir, 0755, true);
            }
            file_put_contents($filePath, $img);
            return $return;
        }

        // 4. 就近缩放：同 email 其他尺寸缓存 → GD 缩放生成目标尺寸
        $resized = self::scaleAvatarFromOtherSize($avatarDir, $email, $size);
        if ($resized !== null) {
            return $aliases->get('@baseUrl/static/avatar/' . basename($resized));
        }

        // 远程失败且无缓存：回退本地默认头像（避免裂图）
        if (is_file($filePath)) {
            return $return;
        }
        return $aliases->get('@baseUrl/static/images/avatar/default-' . $size . '.png');
    }

    /**
     * 从同一 email 其他尺寸的缓存头像缩放生成目标尺寸文件。
     *
     * @return string|null 生成后的文件名（不含路径），失败返回 null
     */
    private static function scaleAvatarFromOtherSize(string $avatarDir, string $emailHash, int $size): ?string
    {
        if (!is_dir($avatarDir)) {
            return null;
        }
        $matches = glob($avatarDir . '/' . $emailHash . '-*.png');
        if ($matches === false) {
            return null;
        }
        foreach ($matches as $candidate) {
            if ($candidate === $avatarDir . "/{$emailHash}-{$size}.png") {
                continue;
            }
            // gravatar 下载内容可能实际为 JPEG（历史缓存文件名为 .png）
            $src = @imagecreatefrompng($candidate);
            if ($src === false) {
                $src = @imagecreatefromjpeg($candidate);
            }
            if ($src === false) {
                continue;
            }
            $srcW = imagesx($src);
            $srcH = imagesy($src);
            $target = imagecreatetruecolor($size, $size);
            if ($target === false) {
                imagedestroy($src);
                continue;
            }
            // 保持透明背景
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            if ($transparent !== false) {
                imagefill($target, 0, 0, $transparent);
            }
            imagecopyresampled($target, $src, 0, 0, 0, 0, $size, $size, $srcW, $srcH);
            $out = $avatarDir . "/{$emailHash}-{$size}.png";
            $ok = imagepng($target, $out);
            imagedestroy($src);
            imagedestroy($target);
            if ($ok) {
                return $out;
            }
            break;
        }
        return null;
    }

    /**
     * 用 cURL 下载远程图片内容（比 file_get_contents 可控：超时、限大小、跟随重定向）。
     *
     * @param string $url 远程地址
     * @param int $maxBytes 最大下载字节数，超出即中止（防恶意大文件）
     * @param int $timeout 传输超时（秒）
     * @return string|null 成功返回内容，失败/超时/超限返回 null
     */
    private static function fetchRemote(string $url, int $maxBytes = 1_000_000, int $timeout = 10): ?string
    {
        if (!function_exists('curl_init')) {
            $content = @file_get_contents($url);
            return $content === false || $content === '' ? null : $content;
        }
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        $buffer = '';
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Crazydb-Blog)',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            // 限流：超过 maxBytes 即返回 0 让 curl 中止
            CURLOPT_WRITEFUNCTION => static function (mixed $ch, string $data) use (&$buffer, $maxBytes): int {
                if (strlen($buffer) + strlen($data) > $maxBytes) {
                    return 0;
                }
                $buffer .= $data;
                return strlen($data);
            },
        ]);
        $result = curl_exec($ch);
        // PHP 8.0+ cURL handle 自动释放，curl_close() 已弃用无需调用
        return ($result === false || $buffer === '') ? null : $buffer;
    }

    /**
     * 向 session 写入操作记录（flash，读取后即失效）。
     */
    public static function actionMessage(Flash $flash, string $key, array $message): void
    {
        $flash->set($key, $message);
    }
}
