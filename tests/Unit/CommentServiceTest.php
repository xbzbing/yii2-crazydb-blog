<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Captcha\CaptchaService;
use App\Comment\Comment;
use App\Comment\CommentService;
use App\Log\Log;
use App\Mail\NoticeService;
use App\Option\Option;
use App\Post\Post;
use App\Tests\TestCase;
use App\User\User;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Mailer\StubMailer;

final class CommentServiceTest extends TestCase
{

    private function seedOption(string $name, string $value): void
    {
        $option = Option::query()->where(['type' => 'sys', 'name' => $name])->one();
        if ($option === null) {
            $option = new Option();
            $option->type = 'sys';
            $option->name = $name;
        }
        $option->value = $value;
        $option->update_time = time();
        $option->save();
    }

    private function createPost(): array
    {
        $post = new Post();
        $post->title = '__comment_test_post__';
        $post->alias = '__comment_' . bin2hex(random_bytes(4));
        $post->author_id = 1;
        $post->author_name = 'dabing';
        $post->save();
        return ['post' => $post, 'cleanup' => static fn() => $post->delete()];
    }

    private function service(Cache $cache, StubMailer $mailer): CommentService
    {
        $captcha = new CaptchaService($this->sharedSession(), true);
        $notice = new NoticeService($mailer, 'admin@example.com', 'notice@example.com', false);
        return new CommentService($cache, $captcha, $notice, 'admin@example.com');
    }

    public function testAddCommentSuccessWithoutAudit(): void
    {
        $this->seedOption(Option::ALLOW_COMMENT, Option::STATUS_OPEN);
        $this->seedOption(Option::AUDIT_ON_COMMENT, 'close');
        $cache = new Cache(new ArrayCache());
        $mailer = new StubMailer();
        $service = $this->service($cache, $mailer);
        $created = $this->createPost();
        try {
            $result = $service->add($created['post']->id, [
                'nickname' => '评论者甲',
                'email' => 'commenter@example.com',
                'content' => '这是一条中文评论内容。',
            ], ['ip' => '203.0.113.5', 'userAgent' => 'TestAgent/1.0']);

            self::assertSame('success', $result['status']);
            $comment = $result['comment'];
            self::assertInstanceOf(Comment::class, $comment);
            try {
                self::assertSame(Comment::STATUS_APPROVED, $comment->status);
                self::assertSame(1, $result['display']);
                self::assertSame('203.0.113.5', $comment->ip);
                self::assertGreaterThan(0, $comment->create_time);
                // 管理员通知邮件
                self::assertCount(1, $mailer->getMessages());
                self::assertSame('admin@example.com', $mailer->getMessages()[0]->getTo());
            } finally {
                $comment->delete();
            }
        } finally {
            $created['cleanup']();
            $this->cleanOptions();
        }
    }

    public function testAddCommentRequiresAuditWhenEnabled(): void
    {
        $this->seedOption(Option::ALLOW_COMMENT, Option::STATUS_OPEN);
        $this->seedOption(Option::AUDIT_ON_COMMENT, Option::STATUS_OPEN);
        $cache = new Cache(new ArrayCache());
        $service = $this->service($cache, new StubMailer());
        $created = $this->createPost();
        try {
            $result = $service->add($created['post']->id, [
                'nickname' => '评论者乙',
                'email' => 'commenter2@example.com',
                'content' => '这是一条待审核的中文评论。',
            ], ['ip' => '203.0.113.6', 'userAgent' => 'TestAgent/1.0']);

            self::assertSame('success', $result['status']);
            self::assertSame(0, $result['display']);
            self::assertSame(Comment::STATUS_UNAPPROVED, $result['comment']->status);
            $result['comment']->delete();
        } finally {
            $created['cleanup']();
            $this->cleanOptions();
        }
    }

    public function testCommentClosedWhenOptionOff(): void
    {
        $this->seedOption(Option::ALLOW_COMMENT, 'close');
        $cache = new Cache(new ArrayCache());
        $service = $this->service($cache, new StubMailer());
        $result = $service->add(1, [
            'nickname' => '评论者丙',
            'email' => 'commenter3@example.com',
            'content' => '这是一条中文评论。',
        ], ['ip' => '203.0.113.7', 'userAgent' => 'TestAgent/1.0']);

        self::assertSame('fail', $result['status']);
        self::assertSame('留言功能已被关闭。', $result['info']);
        $this->cleanOptions();
    }

    public function testRejectsNonHttpUrlScheme(): void
    {
        $this->seedOption(Option::ALLOW_COMMENT, Option::STATUS_OPEN);
        $this->seedOption(Option::AUDIT_ON_COMMENT, 'close');
        $cache = new Cache(new ArrayCache());
        $service = $this->service($cache, new StubMailer());
        $created = $this->createPost();
        try {
            $result = $service->add($created['post']->id, [
                'nickname' => '评论者丁',
                'email' => 'commenter4@example.com',
                'content' => '这是一条中文评论。',
                'url' => 'javascript://alert(1)',
            ], ['ip' => '203.0.113.12', 'userAgent' => 'TestAgent/1.0']);

            self::assertSame('fail', $result['status']);
            self::assertStringContainsString('http', $result['info']);
        } finally {
            $created['cleanup']();
            $this->cleanOptions();
        }
    }

    public function testAntiSpamBlocksNonChineseContent(): void
    {
        $this->seedOption(Option::ALLOW_COMMENT, Option::STATUS_OPEN);
        $this->seedOption(Option::AUDIT_ON_COMMENT, 'close');
        $cache = new Cache(new ArrayCache());
        $mailer = new StubMailer();
        $service = $this->service($cache, $mailer);
        $created = $this->createPost();
        try {
            $result = $service->add($created['post']->id, [
                'nickname' => 'spammer',
                'email' => 'spam@example.com',
                'content' => 'buy cheap viagra now!!!',
            ], ['ip' => '203.0.113.8', 'userAgent' => 'TestAgent/1.0']);

            self::assertSame('fail', $result['status']);
            self::assertStringContainsString('anti-spam', $result['info']);
            self::assertSame([], $mailer->getMessages(), 'anti-spam rejection must not send mails');
        } finally {
            $created['cleanup']();
            $this->cleanOptions();
        }
    }

    public function testRegisteredUserIdentityPrefill(): void
    {
        $this->seedOption(Option::ALLOW_COMMENT, Option::STATUS_OPEN);
        $this->seedOption(Option::AUDIT_ON_COMMENT, 'close');
        $cache = new Cache(new ArrayCache());
        $service = $this->service($cache, new StubMailer());
        $created = $this->createPost();

        $user = new User();
        $user->username = '__cuser_' . bin2hex(random_bytes(3));
        $user->nickname = '登录用户';
        $user->email = '__cuser' . bin2hex(random_bytes(3)) . '@example.com';
        $user->password = 'password123';
        $user->fillDefaultsForInsert();
        $user->save();
        try {
            $result = $service->add($created['post']->id, [
                'nickname' => '冒用者',
                'email' => 'spoof@example.com',
                'content' => '这是一条中文评论。',
            ], ['ip' => '203.0.113.9', 'userAgent' => 'TestAgent/1.0'], $user);

            self::assertSame('success', $result['status']);
            self::assertSame($user->nickname, $result['comment']->nickname);
            self::assertSame($user->email, $result['comment']->email);
            self::assertSame($user->id, $result['comment']->uid);
            $result['comment']->delete();
        } finally {
            $user->delete();
            $created['cleanup']();
            $this->cleanOptions();
        }
    }

    public function testGuestCannotSpoofRegisteredIdentity(): void
    {
        $this->seedOption(Option::ALLOW_COMMENT, Option::STATUS_OPEN);
        $this->seedOption(Option::AUDIT_ON_COMMENT, 'close');
        $cache = new Cache(new ArrayCache());
        $service = $this->service($cache, new StubMailer());
        $created = $this->createPost();

        $user = new User();
        $user->username = '__spoof_' . bin2hex(random_bytes(3));
        $user->nickname = '被保护用户';
        $user->email = '__spoof' . bin2hex(random_bytes(3)) . '@example.com';
        $user->password = 'password123';
        $user->fillDefaultsForInsert();
        $user->save();
        try {
            $result = $service->add($created['post']->id, [
                'nickname' => '被保护用户',
                'email' => 'someone@example.com',
                'content' => '这是一条中文评论。',
            ], ['ip' => '203.0.113.10', 'userAgent' => 'TestAgent/1.0']);

            self::assertSame('fail', $result['status']);
            self::assertStringContainsString('已经被注册', $result['info']);
        } finally {
            $user->delete();
            $created['cleanup']();
            $this->cleanOptions();
        }
    }

    public function testSpoofRejectionEscapesNicknameAgainstXss(): void
    {
        $this->seedOption(Option::ALLOW_COMMENT, Option::STATUS_OPEN);
        $this->seedOption(Option::AUDIT_ON_COMMENT, 'close');
        $cache = new Cache(new ArrayCache());
        $service = $this->service($cache, new StubMailer());
        $created = $this->createPost();

        $user = new User();
        $user->username = '__xss_' . bin2hex(random_bytes(3));
        $user->nickname = 'XSS<b>alert</b>用户';
        $user->email = '__xss' . bin2hex(random_bytes(3)) . '@example.com';
        $user->password = 'password123';
        $user->fillDefaultsForInsert();
        $user->save();
        try {
            $result = $service->add($created['post']->id, [
                'nickname' => 'XSS<b>alert</b>用户',
                'email' => 'someone@example.com',
                'content' => '这是一条中文评论。',
            ], ['ip' => '203.0.113.11', 'userAgent' => 'TestAgent/1.0']);

            self::assertSame('fail', $result['status']);
            self::assertStringNotContainsString('<b>alert</b>', $result['info'], 'user input must be escaped in response');
            self::assertStringContainsString('&lt;b&gt;', $result['info']);
        } finally {
            $user->delete();
            $created['cleanup']();
            $this->cleanOptions();
        }
    }

    private function cleanOptions(): void
    {
        foreach ([Option::ALLOW_COMMENT, Option::AUDIT_ON_COMMENT] as $name) {
            $option = Option::query()->where(['type' => 'sys', 'name' => $name])->one();
            $option?->delete();
        }
    }
}
