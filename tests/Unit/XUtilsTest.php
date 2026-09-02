<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Common\XUtils;
use PHPUnit\Framework\TestCase;

final class XUtilsTest extends TestCase
{
    public function testPlusSignReplacedWithHyphen(): void
    {
        self::assertSame(
            '一切皆插件的-DeepSeek-Harness-dsh-auth-gateway',
            XUtils::generateAlias('一切皆插件的 DeepSeek Harness + dsh-auth-gateway'),
        );
    }

    public function testEmptyTextFallsBackToUntitled(): void
    {
        self::assertSame('untitled', XUtils::generateAlias(''));
    }

    public function testConsecutiveHyphensFoldedToSingle(): void
    {
        self::assertSame('A-B', XUtils::generateAlias('A + B'));
        self::assertSame('hello-world', XUtils::generateAlias('hello  world'));
    }

    public function testHtmlTagsAreStrippedBeforeNormalization(): void
    {
        self::assertSame('bold', XUtils::generateAlias('<b>bold</b>'));
        self::assertSame('A-B-C', XUtils::generateAlias('A <b>B</b> + C'));
        self::assertSame('alert-1-', XUtils::generateAlias('<script>alert(1)</script>'));
    }

    public function testUrlSpecialCharsReplacedWithHyphen(): void
    {
        self::assertSame('a-b-c-d-e', XUtils::generateAlias('a & b = c ? d # e'));
        self::assertSame('100-50', XUtils::generateAlias('100% / 50'));
        self::assertSame('a-b', XUtils::generateAlias('a \\ b'));
    }

    public function testAliasNeverEmptyForSpecialOnlyText(): void
    {
        self::assertSame('-', XUtils::generateAlias('+++'));
    }
}