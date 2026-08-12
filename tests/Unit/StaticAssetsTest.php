<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;

/**
 * 本地化静态资源完整性：拦 Vditor 资源路径类回归（此前 C1 教训）。
 */
final class StaticAssetsTest extends TestCase
{
    /**
     * @return iterable<array{array{string}}>
     */
    public static function vditorFiles(): iterable
    {
        yield [['public/static/vditor/dist/index.css']];
        yield [['public/static/vditor/dist/index.min.js']];
        yield [['public/static/vditor/dist/js/lute/lute.min.js']];
        yield [['public/static/vditor/dist/js/highlight.js/highlight.pack.js']];
        yield [['public/static/vditor/dist/js/highlight.js/styles/github.css']];
        yield [['public/static/vditor/dist/js/icons/ant.js']];
        yield [['public/static/vditor/dist/js/katex/katex.min.js']];
    }

    /**
     * @param array{string} $case
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('vditorFiles')]
    public function testVditorAssetExists(array $case): void
    {
        $file = dirname(__DIR__, 2) . '/' . $case[0];
        self::assertFileExists($file, $case[0] . ' must exist');
        self::assertGreaterThan(1000, filesize($file), $case[0] . ' must not be a 404 stub');
    }

    public function testMagazineCssExists(): void
    {
        self::assertFileExists(dirname(__DIR__, 2) . '/assets/magazine/magazine.css');
    }
}
