<?php

declare(strict_types=1);

namespace App\Web\PostUnlock;

use App\Post\Post;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\SessionInterface;

/**
 * 隐藏文章密码验证（POST）：密码正确则写 session 解锁标记，重定向回文章页。
 * 对齐 Yii2 加锁文档逻辑（该功能在 Yii2 中被注释停用，本次在 Yii3 恢复）。
 */
final readonly class Action
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private SessionInterface $session,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] int $id,
    ): ResponseInterface {
        $post = Post::query()->findByPk($id);
        if (!$post instanceof Post) {
            return $this->redirect($id);
        }
        $expected = (string)$post->password;
        $body = $request->getParsedBody();
        $input = trim((string)(is_array($body) ? ($body['password'] ?? '') : ''));
        if ($expected !== '' && hash_equals($expected, $input)) {
            $this->session->set('unlocked_post_' . (int)$post->id, $expected);
        }
        return $this->redirect($id);
    }

    private function redirect(int $postId): ResponseInterface
    {
        $post = Post::query()->findByPk($postId);
        $uri = $post instanceof Post ? ($post->getUrl($this->urlGenerator) ?: '/') : '/';
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $uri);
    }
}
