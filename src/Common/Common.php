<?php

declare(strict_types=1);

namespace App\Common;

use Yiisoft\Session\Session;

final class Common
{
    /**
     * 语言偏好存于 session（Yii2 原为 cookie；如后台需要浏览器端持久，
     * 阶段 F 装配 CookieMiddleware 后切换为 cookie 语义）。
     */
    public static function setLanguage(Session $session, string $language = ''): void
    {
        $session->set('language', $language);
    }

    public static function getLanguage(Session $session): string|false
    {
        $language = $session->get('language');
        return $language !== null ? (string)$language : false;
    }
}
