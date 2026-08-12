<?php

declare(strict_types=1);

namespace App\CustomConfig;

use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * 自定义配置（个性化设置）。
 *
 * category + key 唯一；value 按 data_type 解释
 * （text/markdown/html/image/url/base64/hex），priority 越大越靠前。
 */
final class CustomConfig extends ActiveRecord
{
    public const TYPE_TEXT = 'text';
    public const TYPE_MARKDOWN = 'markdown';
    public const TYPE_HTML = 'html';
    public const TYPE_IMAGE = 'image';
    public const TYPE_URL = 'url';
    public const TYPE_BASE64 = 'base64';
    public const TYPE_HEX = 'hex';

    public const TYPES = [
        self::TYPE_TEXT,
        self::TYPE_MARKDOWN,
        self::TYPE_HTML,
        self::TYPE_IMAGE,
        self::TYPE_URL,
        self::TYPE_BASE64,
        self::TYPE_HEX,
    ];

    public ?int $id = null;
    public string $category = '';
    public string $key = '';
    public string $name = '';
    public ?string $value = null;
    public string $data_type = self::TYPE_TEXT;
    public int $priority = 0;
    public string $description = '';
    public int $create_time = 0;
    public int $update_time = 0;

    public function tableName(): string
    {
        return 'custom_config';
    }

    /**
     * 按 category+key 读取配置值（不存在返回 null）。
     */
    public static function value(string $category, string $key): ?string
    {
        $config = self::query()->where(['category' => $category, 'key' => $key])->one();
        return $config instanceof self ? $config->value : null;
    }
}
