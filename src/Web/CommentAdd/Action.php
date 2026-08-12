<?php

declare(strict_types=1);

namespace App\Web\CommentAdd;

use App\Comment\CommentService;
use App\Post\Post;
use App\User\SessionAuthMethod;
use App\User\User;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\Flash;

/**
 * 评论发布（POST）：调 CommentService 全流程，结果 flash 后重定向回文章页。
 * 等价 Yii2 CommentController::actionAdd。
 */
final readonly class Action
{
    public function __construct(
        private CommentService $commentService,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private Flash $flash,
        private SessionAuthMethod $authMethod,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] int $id,
    ): ResponseInterface {
        if ($request->getMethod() !== Method::POST) {
            return $this->redirect($id);
        }

        $post = Post::findVisibleById($id);
        if ($post === null) {
            return $this->redirect(null);
        }

        $body = $request->getParsedBody();
        /** @var array{content?: string, nickname?: string, email?: string, url?: string, reply_to?: int, sendMail?: bool, captcha?: string} $data */
        $data = is_array($body) ? $body : [];
        $result = $this->commentService->add(
            $id,
            $data,
            [
                'ip' => (string)($request->getServerParams()['REMOTE_ADDR'] ?? '0.0.0.0'),
                'userAgent' => (string)$request->getHeaderLine('User-Agent'),
            ],
            $this->currentUser($request),
        );

        $this->flash->set(
            $result['status'] === 'success' ? 'comment_success' : 'comment_error',
            ['info' => $result['info']],
        );

        return $this->redirect($id);
    }

    private function currentUser(ServerRequestInterface $request): ?User
    {
        $identity = $this->authMethod->authenticate($request);
        return $identity instanceof User ? $identity : null;
    }

    private function redirect(?int $postId): ResponseInterface
    {
        if ($postId === null) {
            $uri = $this->urlGenerator->generate('site/index');
        } else {
            $post = Post::findVisibleById($postId);
            $uri = $post !== null && $post->alias !== ''
                ? $this->urlGenerator->generate('post/show', ['alias' => $post->alias])
                : $this->urlGenerator->generate('site/index');
        }
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $uri);
    }
}
