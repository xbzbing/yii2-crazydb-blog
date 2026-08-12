<?php

declare(strict_types=1);

namespace App\Mail;

use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Mailer\Message;

/**
 * 博客通知邮件服务：对齐 Yii2 CMSUtils::notice + mail/notice.php 模板
 * （HTML 直接构建，不引入视图层）。
 */
final class NoticeService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $adminEmail,
        private string $noticeEmail,
        private bool $debug = false,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->noticeEmail !== '' && $this->adminEmail !== '';
    }

    /**
     * 发送通知：debug 模式下邮件只发往 admin_email（对齐 Yii2 YII_DEBUG 语义）。
     *
     * @param string $to 收件人邮箱
     * @param string $nickname 收件人昵称（用于正文抬头）
     * @param string $title 主题
     * @param string $content 通知正文（HTML）
     */
    public function sendNotice(string $to, string $nickname, string $title, string $content): void
    {
        if (!$this->isEnabled()) {
            return;
        }
        $message = (new Message())
            ->withFrom($this->noticeEmail)
            ->withTo($this->debug ? $this->adminEmail : $to)
            ->withSubject($title)
            ->withHtmlBody($this->buildBody($nickname, $content));
        $this->mailer->send($message);
    }

    private function buildBody(string $nickname, string $content): string
    {
        return '<strong>' . htmlspecialchars($nickname, ENT_QUOTES) . '：</strong>'
            . '<div class="mail-content"><p>您好，</p><p>' . $content . '</p></div>';
    }
}
