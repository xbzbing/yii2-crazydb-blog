<?php

declare(strict_types=1);

namespace App\Admin\PostForm;

use App\Category\Category;
use App\Common\XUtils;
use App\Post\Post;
use App\User\AuthService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 后台文章新建/编辑表单（等价 Yii2 admin PostController::actionCreate/actionUpdate）。
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
        private AuthService $authService,
        private FlashInterface $flash,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] ?int $id = null,
    ): ResponseInterface {
        /** @var ?Post $post */
        $post = $id !== null ? Post::query()->findByPk($id) : null;
        $isNew = $post === null;
        if (!$isNew && !$post instanceof Post) {
            return $this->redirectList();
        }
        $post ??= new Post();

        $categories = Category::getAllCategories($this->cache);
        $errors = [];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            $data = is_array($body) ? $body : [];
            $post->title = trim((string)($data['title'] ?? ''));
            $post->alias = trim((string)($data['alias'] ?? ''));
            $post->cid = (int)($data['cid'] ?? 0);
            $post->status = (string)($data['status'] ?? Post::STATUS_DRAFT);
            $post->format = (string)($data['format'] ?? Post::FORMAT_HTML);
            $post->tags = trim((string)($data['tags'] ?? ''));
            $post->excerpt = trim((string)($data['excerpt'] ?? '')) ?: null;
            $post->content = (string)($data['content'] ?? '');
            $post->is_top = (int)($data['is_top'] ?? 0) > 0 ? 1 : 0;
            $post->password = trim((string)($data['password'] ?? '')) ?: null;
            $post->post_time = (int)($data['post_time'] ?? time());

            if ($post->title === '') {
                $errors['title'] = '标题不能为空。';
            } elseif ($post->alias !== '' && Post::query()->where(['alias' => $post->alias])->andWhere(['!=', 'id', (int)$post->id])->exists()) {
                $errors['alias'] = '别名已存在。';
            }

            if ($errors === []) {
                if ($isNew) {
                    $user = $this->authService->currentUser();
                    $post->author_id = (int)$user?->id;
                    $post->author_name = (string)$user?->nickname;
                    $post->type = Post::TYPE_POST;
                    $post->create_time = time();
                    if ($post->post_time === 0) {
                        $post->post_time = time();
                    }
                }
                $post->update_time = time();
                $post->content = XUtils::htmlPurify($post->content);
                $post->excerpt = $post->excerpt !== null ? XUtils::htmlPurify($post->excerpt) : null;
                try {
                    $post->save();
                } catch (\Throwable) {
                    $errors['save'] = '保存失败，请检查字段内容。';
                }
                if ($errors === []) {
                    $this->flash->set('flash_success', ['info' => $isNew ? '文章已创建。' : '文章已更新。']);
                    return $this->redirectList();
                }
            }
        }

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Admin/layout.php')
            ->render(
                __DIR__ . '/template',
                [
                    'post' => $post,
                    'isNew' => $isNew,
                    'categories' => $categories,
                    'errors' => $errors,
                ],
            );
    }

    private function redirectList(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('admin/post/list'));
    }
}
