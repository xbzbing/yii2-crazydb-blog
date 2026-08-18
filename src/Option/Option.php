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

    /** 站点状态配置键 */
    public const SITE_STATUS = 'site_status';
    /** 站点状态：运行中 */
    public const STATUS_RUNNING = 'running';
    /** 站点状态：维护中 */
    public const STATUS_MAINTENANCE = 'maintenance';
    /** 维护文案配置键 */
    public const MAINTENANCE_MESSAGE = 'maintenance_message';
    /** 维护文案默认值 */
    public const MAINTENANCE_MESSAGE_DEFAULT = '系统升级中';

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
