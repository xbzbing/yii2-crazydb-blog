<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Option\Option;
use App\Tests\TestCase;
use App\Web\Maintenance\MaintenanceMiddleware;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use HttpSoft\Message\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 维护模式中间件：维护中时拦截非 /admin 请求，放行 /admin。
 */
final class MaintenanceMiddlewareTest extends TestCase
{
    private function middleware(): MaintenanceMiddleware
    {
        $view = $this->container()->get(\Yiisoft\View\WebView::class);
        $renderer = new WebViewRenderer(
            new ResponseFactory(),
            new StreamFactory(),
            $this->container()->get(\Yiisoft\Aliases\Aliases::class),
            $view,
            viewPath: dirname(__DIR__, 2) . '/src/Web',
            layout: null,
        );
        return new MaintenanceMiddleware(new Cache(new ArrayCache()), $renderer);
    }

    private function handler(): RequestHandlerInterface
    {
        return new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new ResponseFactory())->createResponse(200);
            }
        };
    }

    private function setSiteStatus(string $status): void
    {
        /** @var ?Option $option */
        $option = Option::query()->where(['type' => 'sys', 'name' => Option::SITE_STATUS])->one();
        if ($option === null) {
            $option = new Option();
            $option->type = 'sys';
            $option->name = Option::SITE_STATUS;
        }
        $option->value = $status;
        $option->update_time = time();
        $option->save();
    }

    private function request(string $path): ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest('GET', $path);
    }

    public function testPassesThroughWhenRunning(): void
    {
        $this->setSiteStatus(Option::STATUS_RUNNING);
        try {
            $response = $this->middleware()->process($this->request('/archives'), $this->handler());
            self::assertSame(200, $response->getStatusCode(), 'running mode must pass through');
        } finally {
            $this->setSiteStatus(Option::STATUS_RUNNING);
        }
    }

    public function testFrontendBlockedWhenMaintenance(): void
    {
        $this->setSiteStatus(Option::STATUS_MAINTENANCE);
        try {
            foreach (['/', '/archives', '/no-such-page-404'] as $path) {
                $response = $this->middleware()->process($this->request($path), $this->handler());
                self::assertSame(200, $response->getStatusCode());
                $html = (string)$response->getBody();
                self::assertStringContainsString('<title>维护中</title>', $html, "path $path must show maintenance page");
            }
        } finally {
            $this->setSiteStatus(Option::STATUS_RUNNING);
        }
    }

    public function testAdminPassesThroughWhenMaintenance(): void
    {
        $this->setSiteStatus(Option::STATUS_MAINTENANCE);
        try {
            $response = $this->middleware()->process($this->request('/admin'), $this->handler());
            self::assertSame(200, $response->getStatusCode(), '/admin must stay accessible');

            $apiResponse = $this->middleware()->process($this->request('/admin/api/config'), $this->handler());
            self::assertSame(200, $apiResponse->getStatusCode(), '/admin/api must stay accessible');
        } finally {
            $this->setSiteStatus(Option::STATUS_RUNNING);
        }
    }

    public function testLoginPassesThroughWhenMaintenance(): void
    {
        $this->setSiteStatus(Option::STATUS_MAINTENANCE);
        try {
            $response = $this->middleware()->process($this->request('/login'), $this->handler());
            self::assertSame(200, $response->getStatusCode(), '/login must stay accessible during maintenance');

            $responseSlash = $this->middleware()->process($this->request('/login/'), $this->handler());
            self::assertSame(200, $responseSlash->getStatusCode(), '/login/ must stay accessible');
        } finally {
            $this->setSiteStatus(Option::STATUS_RUNNING);
        }
    }
}