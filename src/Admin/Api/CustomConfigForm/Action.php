<?php

declare(strict_types=1);

namespace App\Admin\Api\CustomConfigForm;

use App\Admin\Api\ApiSerializer;
use App\Admin\Api\JsonResponse;
use App\CustomConfig\CustomConfig;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * 自定义配置新建/编辑/删除 JSON API：
 * - GET  /admin/api/custom-config/{id}          详情（回填）
 * - POST /admin/api/custom-config/save          新建
 * - POST /admin/api/custom-config/update/{id}   更新
 * - POST /admin/api/custom-config/delete/{id}   删除
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
    ) {
    }

    public function detail(ServerRequestInterface $request, #[RouteArgument] int $id): ResponseInterface
    {
        $config = CustomConfig::query()->findByPk($id);
        if (!$config instanceof CustomConfig) {
            return $this->jsonResponse->fail('配置不存在。', 404);
        }
        return $this->jsonResponse->ok(['config' => ApiSerializer::customConfig($config)]);
    }

    public function save(ServerRequestInterface $request): ResponseInterface
    {
        return $this->persist($request, null);
    }

    public function update(ServerRequestInterface $request, #[RouteArgument] int $id): ResponseInterface
    {
        return $this->persist($request, $id);
    }

    public function delete(ServerRequestInterface $request, #[RouteArgument] int $id): ResponseInterface
    {
        $config = CustomConfig::query()->findByPk($id);
        if (!$config instanceof CustomConfig) {
            return $this->jsonResponse->fail('配置不存在。', 404);
        }
        try {
            $config->delete();
        } catch (\Throwable $e) {
            return $this->jsonResponse->fail('删除失败：' . $e->getMessage());
        }
        return $this->jsonResponse->ok(['message' => '配置已删除。']);
    }

    private function persist(ServerRequestInterface $request, ?int $id): ResponseInterface
    {
        /** @var ?CustomConfig $config */
        $config = $id !== null ? CustomConfig::query()->findByPk($id) : null;
        if ($id !== null && !$config instanceof CustomConfig) {
            return $this->jsonResponse->fail('配置不存在。', 404);
        }
        $isNew = $config === null;
        $config ??= new CustomConfig();

        $body = $request->getParsedBody();
        $data = is_array($body) ? $body : [];
        $config->category = trim((string)($data['category'] ?? ''));
        $config->key = trim((string)($data['key'] ?? ''));
        $config->name = trim((string)($data['name'] ?? ''));
        $config->value = trim((string)($data['value'] ?? ''));
        $config->data_type = trim((string)($data['data_type'] ?? CustomConfig::TYPE_TEXT));
        if (!in_array($config->data_type, CustomConfig::TYPES, true)) {
            $config->data_type = CustomConfig::TYPE_TEXT;
        }
        $config->priority = (int)($data['priority'] ?? 0);
        $config->description = trim((string)($data['description'] ?? ''));

        if ($config->category === '' || $config->key === '' || $config->name === '') {
            return $this->jsonResponse->fail('分类、键、名称均为必填项。');
        }
        // 分类内 key 唯一性校验
        $exists = CustomConfig::query()
            ->where(['category' => $config->category, 'key' => $config->key])
            ->andWhere(['!=', 'id', (int)$config->id])
            ->exists();
        if ($exists) {
            return $this->jsonResponse->fail('该分类下已存在同名配置键。');
        }

        $now = time();
        if ($isNew) {
            $config->create_time = $now;
        }
        $config->update_time = $now;

        try {
            $config->save();
        } catch (\Throwable $e) {
            return $this->jsonResponse->fail('保存失败：' . $e->getMessage());
        }
        return $this->jsonResponse->ok(['message' => $isNew ? '配置已创建。' : '配置已更新。']);
    }
}
