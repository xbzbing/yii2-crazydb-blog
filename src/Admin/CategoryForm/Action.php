<?php

declare(strict_types=1);

namespace App\Admin\CategoryForm;

use App\Category\Category;
use App\Common\XUtils;
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
 * 后台分类新建/编辑/删除（等价 Yii2 admin CategoryController）。
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
        private FlashInterface $flash,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] string $action,
        #[RouteArgument] ?int $id = null,
    ): ResponseInterface {
        if ($action === 'delete') {
            return $this->delete($id);
        }

        /** @var ?Category $category */
        $category = $id !== null ? Category::query()->findByPk($id) : null;
        $isNew = $category === null;
        $category ??= new Category();
        $errors = [];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            $data = is_array($body) ? $body : [];
            $category->name = trim((string)($data['name'] ?? ''));
            $category->alias = trim((string)($data['alias'] ?? ''));
            $category->desc = trim((string)($data['desc'] ?? '')) ?: null;
            $category->keywords = trim((string)($data['keywords'] ?? ''));
            $category->sort_order = (int)($data['sort_order'] ?? 0);
            $category->pid = (int)($data['pid'] ?? 0);

            if ($category->name === '') {
                $errors['name'] = '分类名称不能为空。';
            }
            if ($category->alias !== '' && Category::query()->where(['alias' => $category->alias])->andWhere(['!=', 'id', (int)$category->id])->exists()) {
                $errors['alias'] = '别名已存在。';
            }

            if ($errors === []) {
                $category->desc = $category->desc !== null ? XUtils::htmlPurify($category->desc) : null;
                $category->update_time = time();
                try {
                    $category->save();
                } catch (\Throwable) {
                    $errors['save'] = '保存失败。';
                }
                if ($errors === []) {
                    $this->cache->remove('__category_summary.' . (int)Category::query()->max('update_time'));
                    $this->flash->set('flash_success', ['info' => $isNew ? '分类已创建。' : '分类已更新。']);
                    return $this->redirectList();
                }
            }
        }

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Admin/layout.php')
            ->render(
                __DIR__ . '/template',
                ['category' => $category, 'isNew' => $isNew, 'errors' => $errors],
            );
    }

    private function delete(?int $id): ResponseInterface
    {
        if ($id !== null) {
            $category = Category::query()->findByPk($id);
            if ($category instanceof Category) {
                $category->delete();
                $this->flash->set('flash_success', ['info' => '分类已删除。']);
            }
        }
        return $this->redirectList();
    }

    private function redirectList(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('admin/category/list'));
    }
}
