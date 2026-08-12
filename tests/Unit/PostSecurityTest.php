<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Admin\Api\ApiSerializer;
use App\Post\Post;
use PHPUnit\Framework\TestCase;

final class PostSecurityTest extends TestCase
{
    public function testAccessPasswordIsHashedAndVerified(): void
    {
        $post = new Post();
        $post->id = 7;
        $post->password = Post::hashAccessPassword(7, 'correct horse battery staple');

        self::assertNotSame('correct horse battery staple', $post->password);
        self::assertSame(32, strlen((string) $post->password));
        self::assertTrue($post->verifyAccessPassword('correct horse battery staple'));
        self::assertFalse($post->verifyAccessPassword('wrong password'));
        // 哈希依赖 post_id：不同文章的同一密码产生不同哈希
        self::assertNotSame(Post::hashAccessPassword(8, 'correct horse battery staple'), $post->password);
    }

    public function testSuccessfulLegacyPasswordVerificationMigratesItToHash(): void
    {
        $post = new Post();
        $post->id = 9;
        $post->password = 'legacy-password';

        self::assertTrue($post->verifyAccessPassword('legacy-password'));
        self::assertTrue($post->rehashAccessPasswordIfNeeded('legacy-password'));
        self::assertNotSame('legacy-password', $post->password);
        self::assertTrue($post->verifyAccessPassword('legacy-password'));
    }

    public function testPostDetailDoesNotExposeAccessPassword(): void
    {
        $post = new Post();
        $post->id = 1;
        $post->password = Post::hashAccessPassword(1, 'secret');

        $detail = ApiSerializer::postDetail($post);

        self::assertArrayNotHasKey('password', $detail);
        self::assertTrue($detail['is_locked']);
    }
}
