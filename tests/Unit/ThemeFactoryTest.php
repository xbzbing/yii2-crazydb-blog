<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Option\Option;
use App\Tests\TestCase;
use App\Theme\ThemeFactory;
use Psr\Container\ContainerInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\View\Theme;
use Yiisoft\View\WebView;

/**
 * 动态主题工厂：option theme 配置 → pathMap（白名单校验）。
 */
final class ThemeFactoryTest extends TestCase
{
    /** 测试前存在的 theme 配置（测试后原样恢复，避免破坏验收环境） */
    private ?Option $backupTheme = null;

    protected function setUp(): void
    {
        parent::setUp();
        // 备份原始 theme 配置（若有），测试后恢复；同时清理上次异常中断的残留行。
        $this->backupTheme = Option::query()->where(['type' => 'sys', 'name' => 'theme'])->one();
        $this->deleteThemeOption();
    }

    protected function tearDown(): void
    {
        $this->deleteThemeOption();
        if ($this->backupTheme !== null) {
            $restore = new Option();
            $restore->type = $this->backupTheme->type;
            $restore->name = $this->backupTheme->name;
            $restore->value = $this->backupTheme->value;
            $restore->update_time = $this->backupTheme->update_time;
            $restore->save();
        }
        parent::tearDown();
    }

    private function deleteThemeOption(): void
    {
        foreach (Option::query()->where(['type' => 'sys', 'name' => 'theme'])->all() as $option) {
            $option->delete();
        }
    }

    private function factoryWithOption(?string $theme): Theme
    {
        $container = $this->container();
        /** @var CacheInterface $cache */
        $cache = $container->get(CacheInterface::class);
        if ($theme !== null) {
            $option = new Option();
            $option->type = 'sys';
            $option->name = 'theme';
            $option->value = $theme;
            // getSiteConfig 的缓存 key 依赖 MAX(update_time)：必须严格大于当前最大值，
            // 否则 key 不变 → Redis 命中旧配置（无 theme），测试不稳定。
            $option->update_time = (int)Option::query()->max('update_time') + 1;
            $option->save();
            // 显式清掉该 key 可能残留的旧缓存（共享 Redis，跨测试运行持久）。
            $cache->remove('config_sys.' . $option->update_time);
        }
        try {
            return ThemeFactory::create($container);
        } finally {
            $this->deleteThemeOption();
        }
    }

    /** 测试环境根路径，模拟一次真实的模板渲染路径（与容器内 @src/Web 一致）。 */
    private function sampleViewPath(): string
    {
        return $this->aliases->get('@src/Web') . '/HomePage/template.php';
    }

    private function magazineViewPath(): string
    {
        return $this->aliases->get('@root/themes/magazine') . '/HomePage/template.php';
    }

    public function testMagazineThemeBuildsPathMap(): void
    {
        $theme = $this->factoryWithOption('magazine');
        self::assertSame(
            $this->magazineViewPath(),
            $theme->applyTo($this->sampleViewPath()),
            'src/Web path must be remapped to themes/magazine',
        );
    }

    public function testUnknownThemeFallsBackToEmptyPathMap(): void
    {
        $theme = $this->factoryWithOption('../../etc');
        self::assertSame(
            $this->sampleViewPath(),
            $theme->applyTo($this->sampleViewPath()),
            'path traversal value must be rejected (no remap)',
        );
    }

    public function testEmptyThemeMeansDefault(): void
    {
        $theme = $this->factoryWithOption('');
        self::assertSame(
            $this->sampleViewPath(),
            $theme->applyTo($this->sampleViewPath()),
            'empty value = default theme',
        );
    }

    public function testMissingOptionMeansDefault(): void
    {
        $theme = $this->factoryWithOption(null);
        self::assertSame(
            $this->sampleViewPath(),
            $theme->applyTo($this->sampleViewPath()),
        );
    }

    public function testContainerWiringWebViewUsesDynamicTheme(): void
    {
        // 覆盖 config/common/di/view.php 的完整链路：WebView def withTheme(Theme::class)
        // → Theme::class 被 ThemeFactory 覆盖 → 后台 theme 配置生效。
        // 用一次性新容器（模拟一次真实请求），避免依赖进程内共享容器的首个解析顺序。
        $option = new Option();
        $option->type = 'sys';
        $option->name = 'theme';
        $option->value = 'magazine';
        $option->update_time = (int)Option::query()->max('update_time') + 1;
        $option->save();
        try {
            $container = $this->freshContainer();
            /** @var CacheInterface $cache */
            $cache = $container->get(CacheInterface::class);
            $cache->remove('config_sys.' . $option->update_time);
            $view = $container->get(WebView::class);
            self::assertSame(
                $this->magazineViewPath(),
                $view->getTheme()?->applyTo($this->sampleViewPath()) ?? '',
                'WebView theme must come from the admin-configured option',
            );
        } finally {
            $this->deleteThemeOption();
        }
    }

    /** 一次性容器：模拟一次独立 HTTP 请求（真实请求每请求重建容器，主题配置即时生效）。 */
    private function freshContainer(): ContainerInterface
    {
        $root = dirname(__DIR__, 2);
        require_once $root . '/src/bootstrap.php';

        $runner = new \Yiisoft\Yii\Runner\Console\ConsoleApplicationRunner(
            rootPath: $root,
            debug: false,
            checkEvents: false,
            environment: \App\Environment::appEnv(),
        );
        $container = $runner->getContainer();
        foreach ((array) require $root . '/config/common/bootstrap.php' as $callable) {
            $callable($container);
        }
        return $container;
    }
}
