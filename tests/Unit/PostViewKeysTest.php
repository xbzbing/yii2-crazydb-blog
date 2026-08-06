<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Post\PostViewKeys;
use PHPUnit\Framework\TestCase;

final class PostViewKeysTest extends TestCase
{
    public function testDailyCounterAndCursorKeysAreSeparated(): void
    {
        self::assertSame(
            'crazydb:post-view:count:20260806:42',
            PostViewKeys::counterKey(42, '20260806'),
        );
        self::assertSame(
            'crazydb:post-view:synced:20260806:42',
            PostViewKeys::syncedKey(42, '20260806'),
        );
    }
}
