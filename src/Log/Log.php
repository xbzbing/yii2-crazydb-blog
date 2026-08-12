<?php

declare(strict_types=1);

namespace App\Log;

use Yiisoft\ActiveRecord\ActiveRecord;

final class Log extends ActiveRecord
{
    public const TYPE_PERMISSION_DENY = 'permission deny';
    public const TYPE_LOGIN = 'login';
    public const TYPE_DEFAULT = 'default';
    public const TYPE_COMMENT = 'comment';
    public const TYPE_DELETE_LOG = 'delete log';

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    public ?int $id = null;
    public int $uid = 0;
    public string $type = 'default';
    public string $action = '';
    public string $result = '';
    public string $key = '';
    public string $detail = '';
    public int $create_time = 0;
    public string $ip = '';
    public string $user_agent = '';

    public function tableName(): string
    {
        return 'log';
    }

    /**
     * 记录一条日志（等价 Yii2 Log::record）。
     *
     * @param string $type 操作类型
     * @param string $action 操作 action
     * @param string $key 识别 key
     * @param string $result 操作结果
     * @param string|null $detail 操作细节
     * @param int $uid 用户 ID（默认 0 = 游客）
     */
    public function record(
        string $type,
        string $action,
        string $key,
        string $result,
        ?string $detail = null,
        int $uid = 0,
        string $ip = '',
        string $userAgent = '',
    ): void {
        $log = new self();
        $log->type = $type;
        $log->action = $action;
        $log->key = $key;
        $log->result = $result;
        $log->detail = mb_substr($detail ?? '', 0, 250);
        $log->uid = $uid;
        $now = time();
        $log->create_time = $now;
        $log->ip = htmlspecialchars($ip, ENT_QUOTES);
        $log->user_agent = htmlspecialchars($userAgent, ENT_QUOTES);
        try {
            $log->save();
        } catch (\Throwable) {
            // 日志写入失败不应阻断业务（对齐 Yii2 save() 返回 false 被忽略的语义）。
        }
    }
}
