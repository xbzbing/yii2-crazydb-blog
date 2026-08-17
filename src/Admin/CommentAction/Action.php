<?php

declare(strict_types=1);

namespace App\Admin\CommentAction;

use App\Comment\Comment;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\FlashInterface;

/**
 * 后台评论操作：通过审核 / 删除（POST + CSRF，等价 Yii2 admin CommentController）。
 */
final readonly class Action
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private FlashInterface $flash,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] string $action,
        #[RouteArgument] int $id,
    ): ResponseInterface {
        if ($request->getMethod() === Method::POST) {
            $comment = Comment::query()->findByPk($id);
            if ($comment instanceof Comment) {
                if ($action === 'approve' && $comment->status !== Comment::STATUS_APPROVED) {
                    $comment->status = Comment::STATUS_APPROVED;
                    $comment->update_time = time();
                    $comment->save();
                    $this->flash->set('flash_success', ['info' => '评论已通过审核。']);
                } elseif ($action === 'delete') {
                    $comment->delete();
                    $this->flash->set('flash_success', ['info' => '评论已删除。']);
                }
            }
        }
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('admin/comment/list'));
    }
}
