<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Common\AssetMinifyService;
use App\Tests\TestCase;

final class AssetMinifyServiceTest extends TestCase
{
    private AssetMinifyService $service;

    /** @var list<string> 测试生成的临时文件，tearDown 清理 */
    private array $tmpFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AssetMinifyService();
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->tmpFiles = [];
        parent::tearDown();
    }

    private function tmpFile(string $content): string
    {
        $file = sys_get_temp_dir() . '/asset-minify-test-' . bin2hex(random_bytes(6)) . '.css';
        file_put_contents($file, $content);
        $this->tmpFiles[] = $file;
        return $file;
    }

    public function testFmtBytes(): void
    {
        self::assertSame('0 B', AssetMinifyService::fmtBytes(0));
        self::assertSame('1023 B', AssetMinifyService::fmtBytes(1023));
        self::assertSame('1.0 KB', AssetMinifyService::fmtBytes(1024));
        self::assertSame('12.5 KB', AssetMinifyService::fmtBytes(12800));
    }

    public function testMinifyCssWritesWhenSmaller(): void
    {
        // 有大量可压缩空白，压缩必然有收益
        $verbose = ".a {\n    color: red;\n    margin: 0 0 0 0;\n}\n.b { color: blue; }\n";
        $file = $this->tmpFile($verbose);
        $saved = $this->service->minifyCss($file);
        self::assertNotNull($saved);
        self::assertGreaterThan(0, $saved);
        self::assertLessThan(strlen($verbose), filesize($file));
    }

    public function testMinifyCssDryRunDoesNotWrite(): void
    {
        $verbose = ".a {\n    color: red;\n    margin: 0 0 0 0;\n}\n";
        $file = $this->tmpFile($verbose);
        $origSize = filesize($file);
        $saved = $this->service->minifyCss($file, dryRun: true);
        self::assertNotNull($saved);
        // dry-run 不写回
        self::assertSame($origSize, filesize($file));
    }

    public function testMinifyCssMissingFileReturnsNull(): void
    {
        $missing = sys_get_temp_dir() . '/definitely-missing-' . bin2hex(random_bytes(6)) . '.css';
        self::assertNull($this->service->minifyCss($missing));
    }

    public function testAlreadyMinifiedFilesAreSkipped(): void
    {
        // 已是压缩产物（*.min.css / *.min.js）不二次压缩：保护 vendor 的 sourceMappingURL
        $css = sys_get_temp_dir() . '/asset-minify-test-' . bin2hex(random_bytes(6)) . '.min.css';
        file_put_contents($css, ".a{\n color:red;\n}/*# sourceMappingURL=x.css.map */\n");
        $this->tmpFiles[] = $css;
        self::assertNull($this->service->minifyCss($css));
        // 文件未被改写（sourceMappingURL 注释保留）
        self::assertStringContainsString('sourceMappingURL', (string)file_get_contents($css));

        $js = sys_get_temp_dir() . '/asset-minify-test-' . bin2hex(random_bytes(6)) . '.min.js';
        file_put_contents($js, "var a=1;\n//# sourceMappingURL=x.js.map\n");
        $this->tmpFiles[] = $js;
        self::assertNull($this->service->minifyJs($js));
    }

    public function testMinifyJs(): void
    {
        $file = sys_get_temp_dir() . '/asset-minify-test-' . bin2hex(random_bytes(6)) . '.js';
        file_put_contents($file, "function a( ) {\n    return 1 + 2 ;\n}\n");
        $this->tmpFiles[] = $file;
        $saved = $this->service->minifyJs($file);
        self::assertNotNull($saved);
        self::assertGreaterThan(0, $saved);
    }

    public function testFindJsFilesRecurses(): void
    {
        $dir = sys_get_temp_dir() . '/asset-minify-dir-' . bin2hex(random_bytes(6));
        mkdir($dir);
        file_put_contents($dir . '/a.js', 'var a=1;');
        mkdir($dir . '/sub');
        file_put_contents($dir . '/sub/b.js', 'var b=2;');
        file_put_contents($dir . '/sub/readme.txt', 'hello');
        $this->tmpFiles[] = $dir . '/a.js';
        $this->tmpFiles[] = $dir . '/sub/b.js';
        $this->tmpFiles[] = $dir . '/sub/readme.txt';

        $found = $this->service->findJsFiles($dir);
        self::assertCount(2, $found);
        foreach ($found as $f) {
            self::assertStringEndsWith('.js', $f);
        }
        // 清理目录
        @unlink($dir . '/sub/b.js');
        @unlink($dir . '/sub/readme.txt');
        @rmdir($dir . '/sub');
        @unlink($dir . '/a.js');
        @rmdir($dir);
    }

    public function testThemesConstant(): void
    {
        self::assertContains('crazydb', AssetMinifyService::THEMES);
        self::assertContains('main', AssetMinifyService::THEMES);
        self::assertContains('magazine', AssetMinifyService::THEMES);
    }
}
