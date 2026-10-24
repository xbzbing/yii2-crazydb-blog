<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Web\Shared\SeoKeywords;
use PHPUnit\Framework\TestCase;

final class SeoKeywordsTest extends TestCase
{
    public function testTagsBeforeSiteKeywords(): void
    {
        self::assertSame('AI,DSH,blog,crazydb', SeoKeywords::merge('AI,DSH', 'blog,crazydb'));
    }

    public function testEmptySegmentsSkipped(): void
    {
        self::assertSame('blog,crazydb', SeoKeywords::merge('', 'blog,crazydb'));
        self::assertSame('AI', SeoKeywords::merge('AI', ''));
        self::assertSame('', SeoKeywords::merge('', ''));
    }

    public function testWhitespaceAndDuplicatesRemoved(): void
    {
        self::assertSame('AI,blog', SeoKeywords::merge(' AI , blog ', 'blog,  ,AI'));
    }

    public function testDuplicateRemovedCaseInsensitivelyKeepingFirstPosition(): void
    {
        self::assertSame('AI,BLOG', SeoKeywords::merge('AI', 'ai,BLOG'));
    }
}
