<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Admin\Api\Config\Action;
use App\Admin\Api\JsonResponse;
use App\Option\Option;
use App\Tests\TestCase;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;

/**
 * 后台配置 API 读取回归测试：
 * FIELDS 中 seo 组字段（seo_title 等）保存后必须能在 GET /admin/api/config 读回，
 * 防止再次出现 read() 只按 sys 组读取导致 SEO 字段刷新后为空。
 */
final class ConfigApiActionTest extends TestCase
{
    /**
     * 各测试改动前的字段状态快照：existed=true 的字段 tearDown 恢复原值，
     * existed=false 的字段（由 save 新建）tearDown 删除。
     *
     * @var list<array{name: string, type: string, value: ?string, existed: bool}>
     */
    private array $snapshot = [];

    private function action(): Action
    {
        return new Action(new JsonResponse(new ResponseFactory()), new Cache(new ArrayCache()));
    }

    /**
     * @return array{values: array<string, string>, fields: array<string, array{label: string, type: string}>}
     */
    private function readData(): array
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/admin/api/config');
        $response = $this->action()->read($request);
        self::assertSame(200, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertIsArray($payload['data']['values']);
        self::assertIsArray($payload['data']['fields']);
        return $payload['data'];
    }

    /** 记录 FIELDS 各字段当前 DB 状态，供 tearDown 恢复（须在 save 改动前调用）。 */
    private function snapshotFields(): void
    {
        $data = $this->readData();
        foreach ($data['fields'] as $name => $field) {
            $row = Option::query()->where(['type' => $field['type'], 'name' => $name])->one();
            $this->snapshot[] = [
                'name' => $name,
                'type' => $field['type'],
                'value' => $row instanceof Option ? $row->value : null,
                'existed' => $row instanceof Option,
            ];
        }
    }

    /** 覆盖指定 (type, name) 行：先备份原值，再写测试值（存在则更新，不存在则插入）。 */
    private function seedOption(string $type, string $name, string $value): void
    {
        $row = Option::query()->where(['type' => $type, 'name' => $name])->one();
        $this->snapshot[] = [
            'name' => $name,
            'type' => $type,
            'value' => $row instanceof Option ? $row->value : null,
            'existed' => $row instanceof Option,
        ];
        if ($row instanceof Option) {
            $row->value = $value;
            $row->update_time = time();
            $row->save();
            return;
        }
        $new = new Option();
        $new->type = $type;
        $new->name = $name;
        $new->value = $value;
        $new->save();
    }

    protected function tearDown(): void
    {
        foreach ($this->snapshot as $row) {
            $current = Option::query()->where(['type' => $row['type'], 'name' => $row['name']])->one();
            if ($row['existed']) {
                if ($current instanceof Option) {
                    $current->value = $row['value'];
                    $current->update_time = time();
                    $current->save();
                } else {
                    // 极端情况：行被外部删除，按原值重建
                    $new = new Option();
                    $new->type = $row['type'];
                    $new->name = $row['name'];
                    $new->value = $row['value'];
                    $new->save();
                }
            } elseif ($current instanceof Option) {
                (new Option())->deleteAll(['type' => $row['type'], 'name' => $row['name']]);
            }
        }
        parent::tearDown();
    }

    public function testReadReturnsSeoGroupValues(): void
    {
        // 回归：seo 组配置此前被 getSysConfig（固定读 sys 组）漏读，保存后刷新字段为空
        $this->seedOption('seo', 'seo_title', '回归测试 SEO 标题');
        $this->seedOption('seo', 'seo_keywords', 'blog,regression');

        $values = $this->readData()['values'];
        self::assertSame('回归测试 SEO 标题', $values['seo_title']);
        self::assertSame('blog,regression', $values['seo_keywords']);
    }

    public function testSaveThenReadRoundTripSeoValues(): void
    {
        // 完整复现：SPA 读全量配置 -> 只改 SEO 字段 -> POST 保存 -> 重新 GET，
        // SEO 字段必须非空且等于保存值（修复前后 read 行为差异的核心回归点）。
        $this->snapshotFields();

        $data = $this->readData();
        $body = $data['values'];
        $body['seo_title'] = '__回归_SEO_标题__';
        $body['seo_keywords'] = 'blog,regression';
        $body['seo_description'] = '__回归_SEO_描述__';

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/admin/api/config/save')
            ->withParsedBody($body);
        $response = $this->action()->save($request);
        self::assertSame(200, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['ok'], '保存应全部成功: ' . ($payload['message'] ?? ''));

        $values = $this->readData()['values'];
        self::assertSame('__回归_SEO_标题__', $values['seo_title']);
        self::assertSame('blog,regression', $values['seo_keywords']);
        self::assertSame('__回归_SEO_描述__', $values['seo_description']);
    }
}