<?php

declare(strict_types=1);

namespace App\Admin\Api\Env;

use App\Admin\Api\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 后台环境信息 JSON API：还原 legacy admin/default/index 的系统信息，
 * 并补充当前 VPS 运行状态检查（负载/内存/磁盘/进程）。
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
        private ConnectionInterface $db,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->jsonResponse->ok([
            'system' => $this->system(),
            'php' => $this->php(),
            'database' => $this->database(),
            'vps' => $this->vps(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function system(): array
    {
        return [
            'os' => PHP_OS,
            'serverSoftware' => (string)($_SERVER['SERVER_SOFTWARE'] ?? 'unknown'),
            'serverName' => (string)($_SERVER['SERVER_NAME'] ?? gethostname()),
            'hostname' => (string)gethostname(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function php(): array
    {
        return [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'memoryLimit' => (string)ini_get('memory_limit'),
            'maxExecutionTime' => (string)ini_get('max_execution_time') . ' 秒',
            'uploadAllowed' => (bool)ini_get('file_uploads'),
            'uploadMaxSize' => (string)ini_get('upload_max_filesize'),
            'postMaxSize' => (string)ini_get('post_max_size'),
            'extensions' => implode(', ', get_loaded_extensions()),
            'currentMemoryUsage' => function_exists('memory_get_usage')
                ? \App\Common\XUtils::dataFormat(memory_get_usage())
                : '未知',
            'peakMemoryUsage' => function_exists('memory_get_peak_usage')
                ? \App\Common\XUtils::dataFormat(memory_get_peak_usage())
                : '未知',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function database(): array
    {
        $dbsize = 0;
        $tableCount = 0;
        try {
            $tables = $this->db->createCommand('SHOW TABLE STATUS')->queryAll();
            $tableCount = count($tables);
            foreach ($tables as $table) {
                $dbsize += (int)($table['Data_length'] ?? 0) + (int)($table['Index_length'] ?? 0);
            }
            $version = $this->db->createCommand('SELECT version() AS version')->queryOne();
        } catch (\Throwable $e) {
            return [
                'version' => '未知',
                'size' => '未知',
                'tableCount' => 0,
                'error' => $e->getMessage(),
            ];
        }
        return [
            'version' => (string)($version['version'] ?? '未知'),
            'size' => $dbsize ? \App\Common\XUtils::dataFormat($dbsize) : '未知',
            'tableCount' => $tableCount,
        ];
    }

    /**
     * VPS 运行状态检查（尽力而为，不可用字段返回 null）。
     *
     * @return array<string, mixed>
     */
    private function vps(): array
    {
        $load = null;
        if (is_readable('/proc/loadavg') && ($raw = @file_get_contents('/proc/loadavg')) !== false) {
            $parts = explode(' ', trim($raw));
            $load = [
                '1min' => (float)($parts[0] ?? 0),
                '5min' => (float)($parts[1] ?? 0),
                '15min' => (float)($parts[2] ?? 0),
            ];
        }

        $cpuCores = null;
        if (is_readable('/proc/cpuinfo') && preg_match_all('/^processor\s*:/m', (string)@file_get_contents('/proc/cpuinfo'), $m)) {
            $cpuCores = count($m[0]);
        }

        $memory = null;
        if (is_readable('/proc/meminfo')) {
            $lines = [];
            foreach (file('/proc/meminfo') ?: [] as $line) {
                if (preg_match('/^(MemTotal|MemFree|MemAvailable|SwapTotal|SwapFree|Buffers|Cached):\s+(\d+) kB/', $line, $m)) {
                    $lines[$m[1]] = (int)$m[2] * 1024;
                }
            }
            if (isset($lines['MemTotal'])) {
                $used = $lines['MemTotal'] - (int)($lines['MemAvailable'] ?? $lines['MemFree']);
                $memory = [
                    'total' => \App\Common\XUtils::dataFormat($lines['MemTotal']),
                    'used' => \App\Common\XUtils::dataFormat(max(0, $used)),
                    'free' => \App\Common\XUtils::dataFormat((int)($lines['MemAvailable'] ?? $lines['MemFree'])),
                    'usagePercent' => round($used / $lines['MemTotal'] * 100, 1),
                    'swapTotal' => \App\Common\XUtils::dataFormat((int)($lines['SwapTotal'] ?? 0)),
                    'swapFree' => \App\Common\XUtils::dataFormat((int)($lines['SwapFree'] ?? 0)),
                ];
            }
        }

        $disk = null;
        $diskTotalBytes = @disk_total_space('/');
        $diskFreeBytes = @disk_free_space('/');
        if ($diskTotalBytes !== false && $diskTotalBytes > 0) {
            $disk = [
                'total' => \App\Common\XUtils::dataFormat($diskTotalBytes),
                'free' => \App\Common\XUtils::dataFormat((float)$diskFreeBytes),
                'used' => \App\Common\XUtils::dataFormat($diskTotalBytes - (float)$diskFreeBytes),
                'usagePercent' => round(($diskTotalBytes - (float)$diskFreeBytes) / $diskTotalBytes * 100, 1),
            ];
        }

        $uptime = null;
        if (is_readable('/proc/uptime') && ($raw = @file_get_contents('/proc/uptime')) !== false) {
            $seconds = (float)explode(' ', $raw)[0];
            $uptime = $this->formatDuration((int)$seconds);
        }

        $processes = null;
        if (is_readable('/proc')) {
            $procs = glob('/proc/[0-9]*');
            $processes = is_array($procs) ? count($procs) : null;
        }

        $phpProcesses = null;
        if (is_readable('/proc')) {
            $procs = glob('/proc/[0-9]*/cmdline');
            if (is_array($procs)) {
                $count = 0;
                foreach ($procs as $cmdline) {
                    $content = @file_get_contents($cmdline);
                    if (is_string($content) && str_contains($content, 'php-fpm')) {
                        $count++;
                    }
                }
                $phpProcesses = $count;
            }
        }

        return [
            'load' => $load,
            'cpuCores' => $cpuCores,
            'memory' => $memory,
            'disk' => $disk,
            'uptime' => $uptime,
            'processes' => $processes,
            'phpProcesses' => $phpProcesses,
            'uname' => function_exists('php_uname') ? php_uname() : null,
        ];
    }

    private function formatDuration(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $mins = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;
        $parts = [];
        if ($days > 0) $parts[] = $days . ' 天';
        if ($hours > 0) $parts[] = $hours . ' 小时';
        if ($mins > 0) $parts[] = $mins . ' 分钟';
        $parts[] = $secs . ' 秒';
        return implode(' ', $parts);
    }
}
