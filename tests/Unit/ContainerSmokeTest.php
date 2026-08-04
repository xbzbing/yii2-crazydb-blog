<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Comment\CommentService;
use App\Mail\NoticeService;
use App\Post\MarkdownRenderer;
use App\Tests\TestCase;
use App\User\AuthService;
use App\User\RegisterService;
use App\User\SessionAuthMethod;
use App\User\UserRepository;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\UrlMatcherInterface;

/**
 * 从真实 DI 容器解析服务的冒烟测试：防止"测试手动 new 依赖、装配实际不可用"类回归。
 */
final class ContainerSmokeTest extends TestCase
{
    public function testCacheInterfaceResolves(): void
    {
        self::assertInstanceOf(CacheInterface::class, $this->container()->get(CacheInterface::class));
    }

    public function testMailerInterfaceResolves(): void
    {
        self::assertInstanceOf(MailerInterface::class, $this->container()->get(MailerInterface::class));
    }

    public function testServicesResolve(): void
    {
        $container = $this->container();
        self::assertInstanceOf(CommentService::class, $container->get(CommentService::class));
        self::assertInstanceOf(NoticeService::class, $container->get(NoticeService::class));
        self::assertInstanceOf(AuthService::class, $container->get(AuthService::class));
        self::assertInstanceOf(RegisterService::class, $container->get(RegisterService::class));
        self::assertInstanceOf(SessionAuthMethod::class, $container->get(SessionAuthMethod::class));
        self::assertInstanceOf(UserRepository::class, $container->get(UserRepository::class));
    }

    public function testIdentityRepositoriesAliasesResolve(): void
    {
        $container = $this->container();
        self::assertInstanceOf(
            UserRepository::class,
            $container->get(IdentityRepositoryInterface::class),
        );
        self::assertInstanceOf(
            UserRepository::class,
            $container->get(IdentityWithTokenRepositoryInterface::class),
        );
    }

    public function testUrlMatcherResolves(): void
    {
        self::assertInstanceOf(UrlMatcherInterface::class, $this->container()->get(UrlMatcherInterface::class));
    }

    public function testMarkdownRendererResolves(): void
    {
        $renderer = $this->container()->get(MarkdownRenderer::class);
        self::assertInstanceOf(MarkdownRenderer::class, $renderer);
        self::assertStringContainsString(
            '<pre><code class="language-php">',
            $renderer->render("```php\nx\n```", 1),
        );
    }
}
