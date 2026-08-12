<?php

declare(strict_types=1);

namespace App\Admin\NavForm;

use App\Nav\Nav;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 后台导航新建/编辑（等价 Yii2 admin NavController::actionCreate/actionUpdate）。
 * 校验/保存/清缓存逻辑见 NavFormService（与 JSON API 双入口共享）。
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private NavFormService $service,
        private FlashInterface $flash,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] ?int $id = null,
    ): ResponseInterface {
        /** @var ?Nav $nav */
        $nav = $id !== null ? Nav::query()->findByPk($id) : null;
        if ($id !== null && $nav === null) {
            return $this->redirectList();
        }
        $isNew = $nav === null;
        $nav ??= new Nav();
        $errors = [];

        $parents = $this->service->parents();

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            $data = is_array($body) ? $body : [];
            $result = $this->service->save($data, $id);
            if ($result['ok'] && $result['nav'] instanceof Nav) {
                $nav = $result['nav'];
                $this->flash->set('flash_success', ['info' => $result['message'] ?? '']);
                return $this->redirectList();
            }
            $errors = $result['errors'] ?? [];
            if ($result['nav'] instanceof Nav) {
                $nav = $result['nav'];
            } else {
                return $this->redirectList();
            }
        }

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Admin/layout.php')
            ->render(
                __DIR__ . '/template',
                ['nav' => $nav, 'isNew' => $isNew, 'parents' => $parents, 'errors' => $errors],
            );
    }

    private function redirectList(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('admin/nav/list'));
    }
}
