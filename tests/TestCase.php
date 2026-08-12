<?php

declare(strict_types=1);

namespace App\Tests;

use App\Environment;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Connection\ConnectionProvider;
use Yiisoft\Session\Session;
use Yiisoft\Yii\Runner\Console\ConsoleApplicationRunner;

abstract class TestCase extends BaseTestCase
{
    private static ?ContainerInterface $container = null;

    /**
     * 所有测试类共享的 Session 单例：避免多实例各自 open() 时
     * 后打开的实例拿不到 sessionId，导致 Flash::get() 短路。
     */
    private static ?Session $sharedSession = null;

    protected Aliases $aliases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aliases = $this->container()->get(Aliases::class);
    }

    protected function tearDown(): void
    {
        // 清理测试生成的头像缓存文件（getAvatar 测试产物不入库）
        $avatarDir = $this->aliases->get('@public') . '/static/avatar';
        if (is_dir($avatarDir)) {
            foreach (glob($avatarDir . '/*.png') ?: [] as $file) {
                if (filesize($file) === 0) {
                    @unlink($file);
                }
            }
        }
        parent::tearDown();
    }

    protected function sharedSession(): Session
    {
        if (self::$sharedSession === null) {
            self::$sharedSession = new Session();
            self::$sharedSession->open();
        }
        return self::$sharedSession;
    }

    protected function container(): ContainerInterface
    {
        if (self::$container === null) {
            $root = dirname(__DIR__);
            require_once $root . '/src/bootstrap.php';

            $runner = new ConsoleApplicationRunner(
                rootPath: $root,
                debug: Environment::appDebug(),
                checkEvents: Environment::appDebug(),
                environment: Environment::appEnv(),
            );
            self::$container = $runner->getContainer();

            foreach ((array) require $root . '/config/common/bootstrap.php' as $callable) {
                $callable(self::$container);
            }
        }

        return self::$container;
    }

    protected function db(): ConnectionInterface
    {
        return $this->container()->get(ConnectionInterface::class);
    }
}
