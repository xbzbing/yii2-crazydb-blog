<?php

declare(strict_types=1);

namespace App\Comment;

use App\Captcha\CaptchaService;
use App\Common\CMSUtils;
use App\Common\XUtils;
use App\Log\Log;
use App\Mail\NoticeService;
use App\Option\Option;
use App\Post\Post;
use App\User\User;
use Yiisoft\Cache\CacheInterface;

/**
 * 评论发布服务：对齐 Yii2 CommentController::actionAdd 全流程
 * （选项开关 → 身份填充/防冒用 → antiSpam → 保存 → 邮件通知）。
 */
final class CommentService
{
    public function __construct(
        private CacheInterface $cache,
        private CaptchaService $captcha,
        private NoticeService $noticeService,
        private string $adminEmail,
    ) {
    }

    /**
     * @param array{content?: string, nickname?: string, email?: string, url?: string, reply_to?: int, sendMail?: bool, captcha?: string} $data
     * @param array{ip: string, userAgent: string} $request
     * @return array{status: string, info: string, comment?: Comment, display?: int}
     */
    public function add(int $postId, array $data, array $request, ?User $currentUser = null): array
    {
        if (CMSUtils::getSysConfig($this->cache, Option::ALLOW_COMMENT) !== Option::STATUS_OPEN) {
            return ['status' => 'fail', 'info' => '留言功能已被关闭。'];
        }

        $info = '留言成功！';
        $display = 1;
        $status = Comment::STATUS_UNAPPROVED;
        if (CMSUtils::getSysConfig($this->cache, Option::AUDIT_ON_COMMENT) === Option::STATUS_OPEN) {
            $info .= '您的留言需要经过管理员的审核才可以显示出来。';
            $display = 0;
        } else {
            $status = Comment::STATUS_APPROVED;
        }

        $comment = new Comment();
        $comment->pid = $postId;
        $comment->nickname = trim((string)($data['nickname'] ?? ''));
        $comment->email = trim((string)($data['email'] ?? ''));
        $comment->reply_to = isset($data['reply_to']) ? (int)$data['reply_to'] : null;
        $comment->content = (string)($data['content'] ?? '');
        $comment->status = $status;

        // 登录用户：身份取自会话并覆盖表单提交（防伪造），后续校验随之走会话数据
        if ($currentUser !== null) {
            $comment->uid = $currentUser->id;
            $comment->nickname = $currentUser->nickname;
            $comment->email = $currentUser->email;
        }

        if ($comment->content === '') {
            return ['status' => 'fail', 'info' => '请填写留言内容'];
        }
        if ($comment->nickname === '' || $comment->email === '') {
            return ['status' => 'fail', 'info' => '请填写留言内容'];
        }
        if (mb_strlen($comment->nickname) > 80) {
            return ['status' => 'fail', 'info' => '昵称过长（最多 80 字符）。'];
        }
        if (mb_strlen($comment->email) > 100) {
            return ['status' => 'fail', 'info' => '邮箱过长（最多 100 字符）。'];
        }
        if ($comment->reply_to !== null) {
            $replyTarget = Comment::query()->findByPk($comment->reply_to);
            if (!$replyTarget instanceof Comment
                || $replyTarget->pid !== $postId
                || $replyTarget->status !== Comment::STATUS_APPROVED
            ) {
                $comment->reply_to = null;
            }
        }
        if (!$this->captcha->validate($data['captcha'] ?? '')) {
            return ['status' => 'fail', 'info' => '验证码错误'];
        }

        // 游客专属：邮箱格式与防冒用注册用户身份（登录用户身份由会话保证，天然可信）
        if ($currentUser === null) {
            if (!filter_var($comment->email, FILTER_VALIDATE_EMAIL)) {
                return ['status' => 'fail', 'info' => '不是有效的E-mail地址。'];
            }

            // 保护注册用户不被冒用身份
            /** @var ?User $user */
            $user = User::query()
                ->where(['or', ['email' => $comment->email], ['nickname' => $comment->nickname]])
                ->one();
            if ($user !== null) {
                $info = '留言失败！';
                if ($user->email === $comment->email) {
                    $info .= " 当前邮箱<{$comment->email}>已经被注册，";
                }
                if ($user->nickname === $comment->nickname) {
                    $info .= " 当前用户昵称<{$comment->nickname}>已经被注册，";
                }
                $info .= ' 为防止用户身份被冒用，请登录后再留言。';
                return ['status' => 'fail', 'info' => $info];
            }
        }

        $log = new Log();
        if (!$comment->passAntiSpam($log, $request['ip'], $request['userAgent'])) {
            return ['status' => 'fail', 'info' => 'Your message is blocked by anti-spam policy, please try again.'];
        }

        $comment->sanitize();
        $comment->fillDefaultsForInsert($request['ip'], $request['userAgent']);

        try {
            $comment->save();
        } catch (\Throwable) {
            return ['status' => 'fail', 'info' => '留言失败！'];
        }

        $this->notify($comment, filter_var($data['sendMail'] ?? false, FILTER_VALIDATE_BOOLEAN), $display === 1);
        return ['status' => 'success', 'info' => $info, 'comment' => $comment, 'display' => $display];
    }

    /**
     * 邮件通知：回复者 + 管理员（对齐 Yii2 通知逻辑）。
     * 邮件发送失败不阻断评论发布（与 Log::record 语义一致）。
     */
    private function notify(Comment $comment, bool $sendMail, bool $approved): void
    {
        try {
            $this->doNotify($comment, $sendMail, $approved);
        } catch (\Throwable) {
        }
    }

    private function doNotify(Comment $comment, bool $sendMail, bool $approved): void
    {
        if (!$this->noticeService->isEnabled()) {
            return;
        }
        $post = $comment->getPost();
        $postTitle = $post === null ? '' : $post->title;
        $escapedTitle = htmlspecialchars($postTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escapedNickname = htmlspecialchars($comment->nickname, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escapedContent = htmlspecialchars((string)$comment->content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        if ($sendMail && $approved && $comment->isReply()) {
            $replyTo = $comment->getReply();
            if ($replyTo !== null && $replyTo->email !== $comment->email && $replyTo->email !== $this->adminEmail) {
                $this->noticeService->sendNotice(
                    $replyTo->email,
                    $replyTo->nickname,
                    '你的留言有新的回复',
                    "<p>{$escapedNickname} 在《{$escapedTitle}》上回复了你：</p><br><p>{$escapedContent}</p>",
                );
            }
        }
        if ($this->adminEmail !== $comment->email) {
            $this->noticeService->sendNotice(
                $this->adminEmail,
                '网站管理员',
                '网站有新的留言',
                "<p>{$escapedNickname} 在《{$escapedTitle}》发表了评论：</p><br><p>{$escapedContent}</p>",
            );
        }
    }
}
