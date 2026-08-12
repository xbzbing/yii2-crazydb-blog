<?php

declare(strict_types=1);

namespace App\Admin\Upload;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Http\Method;

/**
 * 图片上传（Vditor 编辑器调用，admin group 内受守卫保护）。
 * 返回 Vditor 约定 JSON：{"code":0,"data":{"url":"..."},"msg":""}
 */
final readonly class Action
{
    private const MAX_SIZE = 2 * 1024 * 1024;
    private const ALLOWED_EXT = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Aliases $aliases,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== Method::POST) {
            return $this->json(['code' => 1, 'msg' => '仅支持 POST 上传。']);
        }

        $files = $request->getUploadedFiles();
        $uploaded = $files['file'] ?? null;
        if (!$uploaded instanceof \Psr\Http\Message\UploadedFileInterface) {
            return $this->json(['code' => 1, 'msg' => '未收到文件。']);
        }
        if ($uploaded->getError() !== UPLOAD_ERR_OK) {
            return $this->json(['code' => 1, 'msg' => '文件上传失败（错误码 ' . $uploaded->getError() . '）。']);
        }
        if ($uploaded->getSize() > self::MAX_SIZE) {
            return $this->json(['code' => 1, 'msg' => '文件过大（最大 2MB）。']);
        }

        $ext = strtolower(pathinfo($uploaded->getClientFilename() ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return $this->json(['code' => 1, 'msg' => '仅支持 png/jpg/gif/webp 图片。']);
        }
        // 内容级校验（扩展名可伪造）：真实图片 magic bytes 校验
        $stream = $uploaded->getStream();
        $tmpFile = $stream->getMetadata('uri');
        if (is_string($tmpFile) && is_file($tmpFile)) {
            $info = @getimagesize($tmpFile);
            $mimeMap = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            if ($info === false || !isset($mimeMap[$info['mime'] ?? ''])) {
                return $this->json(['code' => 1, 'msg' => '文件内容不是有效图片。']);
            }
        }

        $dir = $this->aliases->get('@public') . '/static/upload/' . date('Y/m');
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return $this->json(['code' => 1, 'msg' => '无法创建上传目录。']);
        }
        do {
            $fileName = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        } while (file_exists($dir . '/' . $fileName));
        try {
            $uploaded->moveTo($dir . '/' . $fileName);
        } catch (\Throwable) {
            return $this->json(['code' => 1, 'msg' => '保存文件失败。']);
        }

        return $this->json([
            'code' => 0,
            'data' => ['url' => '/static/upload/' . date('Y/m') . '/' . $fileName],
            'msg' => '',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(array $payload): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write((string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json; charset=UTF-8');
    }
}
