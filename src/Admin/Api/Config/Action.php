<?php

declare(strict_types=1);

namespace App\Admin\Api\Config;

use App\Admin\Api\JsonResponse;
use App\Common\CMSUtils;
use App\Option\Option;
use App\Theme\ThemeFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\CacheInterface;

/**
 * 后台站点配置 JSON API：
 * - GET  /admin/api/config      读取全部配置 + 字段定义 + 主题下拉
 * - POST /admin/api/config/save 保存配置（逐字段校验，失败明细返回）
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
        'theme' => ['label' => '前台主题', 'type' => 'sys'],
        'seo_title' => ['label' => 'SEO 标题', 'type' => 'seo'],
        'seo_keywords' => ['label' => 'SEO 关键词', 'type' => 'seo'],
        'seo_description' => ['label' => 'SEO 描述', 'type' => 'seo'],
    ];

    /** @var array<string, string> */
    private const THEME_LABELS = [
        '' => '默认主题',
        'crazydb' => 'Crazydb（经典 Yii2 风格）',
        'magazine' => '墨刊（杂志风格）',
    ];

    public function __construct(
        private JsonResponse $jsonResponse,
        private CacheInterface $cache,
    ) {
    }

    public function read(ServerRequestInterface $request): ResponseInterface
    {
        $values = [];
        foreach (self::FIELDS as $name => $field) {
            $values[$name] = CMSUtils::getSysConfig($this->cache, $name, true) ?? '';
        }
        return $this->jsonResponse->ok([
            'values' => $values,
            'fields' => self::FIELDS,
            'themeOptions' => $this->themeOptions(),
        ]);
    }

    public function save(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $data = is_array($body) ? $body : [];
        $failed = [];
        $values = [];
        $switchFields = ['allow_comment', 'allow_register', 'need_approve'];
        foreach (self::FIELDS as $name => $field) {
            $value = trim((string)($data[$name] ?? ''));
            if (in_array($name, $switchFields, true) && !in_array($value, ['open', 'close'], true)) {
                $failed[] = $name . '（须为 open/close）';
                continue;
            }
            if ($name === 'theme' && !isset(ThemeFactory::AVAILABLE_THEMES[$value])) {
                $failed[] = $name . '（未知主题）';
                continue;
            }
            if (!$this->saveOption($field['type'], $name, $value)) {
                $failed[] = $name;
            }
            $values[$name] = $value;
        }
        CMSUtils::getSiteConfig($this->cache, 'sys', true);
        CMSUtils::getSiteConfig($this->cache, 'seo', true);

        if ($failed === []) {
            return $this->jsonResponse->ok(['message' => '配置已保存。']);
        }
        return $this->jsonResponse->ok([
            'ok' => false,
            'message' => '部分配置保存失败：' . implode(', ', $failed),
            'failed' => $failed,
            'values' => $values,
        ]);
    }

    /**
     * @return array<string, string> 以 ThemeFactory::AVAILABLE_THEMES 为唯一白名单源
     */
    private function themeOptions(): array
    {
        $options = [];
        foreach (ThemeFactory::AVAILABLE_THEMES as $value => $_dir) {
            $options[$value] = self::THEME_LABELS[$value] ?? $value;
        }
        return $options;
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
