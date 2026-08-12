<?php

declare(strict_types=1);

namespace App\Admin\Config;

use App\Common\CMSUtils;
use App\Option\Option;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 后台站点配置（等价 Yii2 admin SiteController::actionConfig）：
 * sys 与 seo 两组 Option 的编辑保存。
 */
final readonly class Action
{
    /** @var array<string, array{label: string, type: string}> */
    private const FIELDS = [
        'site_name' => ['label' => '站点名称', 'type' => 'sys'],
        'admin_email' => ['label' => '管理员邮箱', 'type' => 'sys'],
        'allow_comment' => ['label' => '允许评论（open/close）', 'type' => 'sys'],
        'allow_register' => ['label' => '允许注册（open/close）', 'type' => 'sys'],
        'need_approve' => ['label' => '评论需审核（open/close）', 'type' => 'sys'],
        'seo_title' => ['label' => 'SEO 标题', 'type' => 'seo'],
        'seo_keywords' => ['label' => 'SEO 关键词', 'type' => 'seo'],
        'seo_description' => ['label' => 'SEO 描述', 'type' => 'seo'],
    ];

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
        private FlashInterface $flash,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $values = [];
        foreach (self::FIELDS as $name => $field) {
            $values[$name] = CMSUtils::getSysConfig($this->cache, $name, true) ?? '';
        }

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            $data = is_array($body) ? $body : [];
            $failed = [];
            foreach (self::FIELDS as $name => $field) {
                $value = trim((string)($data[$name] ?? ''));
                if (!$this->saveOption($field['type'], $name, $value)) {
                    $failed[] = $name;
                }
                $values[$name] = $value;
            }
            CMSUtils::getSiteConfig($this->cache, 'sys', true);
            CMSUtils::getSiteConfig($this->cache, 'seo', true);
            if ($failed === []) {
                $this->flash->set('flash_success', ['info' => '配置已保存。']);
            } else {
                $this->flash->set('flash_error', ['info' => '部分配置保存失败：' . implode(', ', $failed)]);
            }
            return $this->responseFactory
                ->createResponse(Status::FOUND)
                ->withHeader('Location', $this->urlGenerator->generate('admin/config'));
        }

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Admin/layout.php')
            ->render(
                __DIR__ . '/template',
                ['values' => $values, 'fields' => self::FIELDS],
            );
    }

    private function saveOption(string $type, string $name, string $value): bool
    {
        /** @var ?Option $option */
        $option = Option::query()->where(['type' => $type, 'name' => $name])->one();
        if ($option === null) {
            $option = new Option();
            $option->type = $type;
            $option->name = $name;
        }
        $option->value = $value;
        $option->update_time = time();
        try {
            $option->save();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
