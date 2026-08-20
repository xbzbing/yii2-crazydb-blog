<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Post\PostViewKeys;
use PHPUnit\Framework\TestCase;

final class PostViewKeysTest extends TestCase
{
    public function testKeysAreCumulativeWithoutDaySplit(): void
    {
        self::assertSame('crazydb:post-view:count:42', PostViewKeys::counterKey(42));
        self::assertSame('crazydb:post-view:synced:42', PostViewKeys::syncedKey(42));
        self::assertSame('crazydb:post-view:uv:42', PostViewKeys::uvKey(42));
    }

    public function testPendingAndTopKeys(): void
    {
        self::assertSame('crazydb:post-view:pending', PostViewKeys::pendingKey());
        self::assertSame('crazydb:post-view:top:20260806', PostViewKeys::topKey('20260806'));
        self::assertSame('crazydb:post-view:top:20260820', PostViewKeys::topDateKey(new \DateTimeImmutable('2026-08-20')));
    }
}
