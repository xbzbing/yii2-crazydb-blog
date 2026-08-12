<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Mail\NoticeService;
use App\Tests\TestCase;
use Yiisoft\Mailer\StubMailer;

final class NoticeServiceTest extends TestCase
{
    public function testSendNoticeBuildsCorrectMessage(): void
    {
        $mailer = new StubMailer();
        $service = new NoticeService($mailer, 'admin@example.com', 'notice@example.com', false);
        $service->sendNotice(
            'user@example.com',
            '张三',
            '你的留言有新的回复',
            '<p>有人回复了你</p>',
        );

        $messages = $mailer->getMessages();
        self::assertCount(1, $messages);
        $message = $messages[0];
        self::assertSame('notice@example.com', $message->getFrom());
        self::assertSame('user@example.com', $message->getTo());
        self::assertSame('你的留言有新的回复', $message->getSubject());
        self::assertStringContainsString('<strong>张三：</strong>', $message->getHtmlBody());
        self::assertStringContainsString('<p>有人回复了你</p>', $message->getHtmlBody());
    }

    public function testDebugModeRedirectsToAdmin(): void
    {
        $mailer = new StubMailer();
        $service = new NoticeService($mailer, 'admin@example.com', 'notice@example.com', true);
        $service->sendNotice('user@example.com', '李四', '标题', '<p>内容</p>');

        $messages = $mailer->getMessages();
        self::assertCount(1, $messages);
        self::assertSame('admin@example.com', $messages[0]->getTo());
    }

    public function testDisabledWhenEmailsNotConfigured(): void
    {
        $mailer = new StubMailer();
        $service = new NoticeService($mailer, '', '');
        self::assertFalse($service->isEnabled());

        $service->sendNotice('user@example.com', '王五', '标题', '<p>内容</p>');
        self::assertSame([], $mailer->getMessages());
    }

    public function testHtmlEscapesNickname(): void
    {
        $mailer = new StubMailer();
        $service = new NoticeService($mailer, 'admin@example.com', 'notice@example.com', false);
        $service->sendNotice('user@example.com', '<script>x</script>', '标题', '<p>内容</p>');

        $messages = $mailer->getMessages();
        self::assertCount(1, $messages);
        self::assertStringContainsString('&lt;script&gt;x&lt;/script&gt;', $messages[0]->getHtmlBody());
        self::assertStringNotContainsString('<script>', $messages[0]->getHtmlBody());
    }
}
