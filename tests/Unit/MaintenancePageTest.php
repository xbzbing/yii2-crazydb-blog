<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Option\Option;
use App\Shared\ApplicationParams;
use PHPUnit\Framework\TestCase;

/**
 * 站点维护页渲染断言（不依赖 DB）。
 */
final class MaintenancePageTest extends TestCase
{
    private function render(array $siteConfig, string $maintenanceMessage): string
    {
        $urlGenerator = null;
        $applicationParams = new ApplicationParams(name: '默认站点');
        ob_start();
        include dirname(__DIR__, 2) . '/src/Web/HomePage/maintenance.php';
        return (string)ob_get_clean();
    }

    public function testMaintenancePageShowsMessageAndTitle(): void
    {
        $html = $this->render(
            ['site_name' => '测试博客'],
            '系统升级中',
        );
        self::assertStringContainsString('<title>维护中</title>', $html);
        self::assertStringContainsString('<h1>维护中</h1>', $html);
        self::assertStringContainsString('系统升级中', $html);
        self::assertStringContainsString('测试博客', $html);
    }

    public function testCustomMaintenanceMessageIsShown(): void
    {
        $html = $this->render(
            ['site_name' => '测试博客'],
            '今晚 22:00 恢复',
        );
        self::assertStringContainsString('今晚 22:00 恢复', $html);
        self::assertStringNotContainsString('系统升级中', $html);
    }

    public function testEmptyMessageFallsBackToDefault(): void
    {
        $html = $this->render(['site_name' => '测试博客'], '');
        self::assertStringContainsString('系统升级中', $html);
    }

    public function testMessageIsHtmlEscaped(): void
    {
        $html = $this->render(['site_name' => '测试博客'], '<script>alert(1)</script>');
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testOptionConstants(): void
    {
        self::assertSame('running', Option::STATUS_RUNNING);
        self::assertSame('maintenance', Option::STATUS_MAINTENANCE);
        self::assertSame('系统升级中', Option::MAINTENANCE_MESSAGE_DEFAULT);
    }
}