<?php

declare(strict_types=1);

namespace App\Option;

use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Composite primary key (type, name).
 */
final class Option extends ActiveRecord
{
    public const ALLOW_REGISTER = 'allow_register';
    public const ALLOW_COMMENT = 'allow_comment';
    public const AUDIT_ON_COMMENT = 'need_approve';
    public const STATUS_OPEN = 'open';

    public string $type = '';
    public string $name = '';
    public ?string $value = null;
    public string $description = '';
    public int $update_time = 0;

    public function tableName(): string
    {
        return '{{%option}}';
    }
}
