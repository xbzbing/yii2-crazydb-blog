<?php

declare(strict_types=1);

namespace App\Admin\PostDelete;

use App\Category\Category;
use App\Comment\Comment;
use App\Post\Post;
use App\Tag\Tag;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\FlashInterface;

/**
 * 后台删除文章（POST + CSRF，连带删除评论，等价 Yii2 admin actionDelete 的 POST 约束）。
 */
final readonly class Action
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private FlashInterface $flash,
        private CacheInterface $cache,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] int $id,
    ): ResponseInterface {
        if ($request->getMethod() === Method::POST) {
            $post = Post::query()->findByPk($id);
            if ($post instanceof Post) {
                (new Comment())->deleteAll(['pid' => $id]);
                (new Tag())->deleteAll(['pid' => $id]);
                $post->delete();
                Tag::invalidateCache($this->cache);
                Category::invalidateSummaryCache($this->cache);
                $this->flash->set('flash_success', ['info' => '文章已删除。']);
            }
        }
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('admin/post/list'));
    }
}
