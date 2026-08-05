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
    private const MAX_SIZE = 16 * 1024 * 1024;
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
            return $this->json(['code' => 1, 'msg' => '文件过大（最大 16MB）。']);
        }

        $ext = strtolower(pathinfo($uploaded->getClientFilename() ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return $this->json(['code' => 1, 'msg' => '仅支持 png/jpg/gif/webp 图片。']);
        }
        // 内容级校验（扩展名可伪造）：真实图片 magic bytes 校验 + 尺寸上限（防解压炸弹）
        $stream = $uploaded->getStream();
        $tmpFileMeta = $stream->getMetadata('uri');
        $tmpFile = is_string($tmpFileMeta) ? $tmpFileMeta : null;
        if ($tmpFile !== null && is_file($tmpFile)) {
            $info = @getimagesize($tmpFile);
            $mimeMap = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            if ($info === false || !isset($mimeMap[$info['mime'] ?? ''])) {
                return $this->json(['code' => 1, 'msg' => '文件内容不是有效图片。']);
            }
            if (($info[0] ?? 0) > 8000 || ($info[1] ?? 0) > 8000) {
                return $this->json(['code' => 1, 'msg' => '图片尺寸过大（最大 8000×8000）。']);
            }
        }

        $dir = $this->aliases->get('@public') . '/static/upload/' . date('Y/m');
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return $this->json(['code' => 1, 'msg' => '无法创建上传目录。']);
        }
        do {
            $fileName = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        } while (file_exists($dir . '/' . $fileName));
        $target = $dir . '/' . $fileName;
        try {
            // gif 跳过 GD 重编码（动图会静默丢帧；且 GIF 不携带 EXIF，剥离无收益）
            $reencoded = ($tmpFile !== null && $ext !== 'gif') ? $this->stripMetadata($tmpFile, $ext) : null;
            if ($reencoded !== null) {
                // GD 重编码成功（剥离 EXIF/GPS 等元数据），写入重编码结果
                if (file_put_contents($target, $reencoded) === false) {
                    return $this->json(['code' => 1, 'msg' => '保存文件失败。']);
                }
            } else {
                $uploaded->moveTo($target);
            }
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
     * GD 重编码剥离元数据（EXIF/GPS/评论等）。失败返回 null（回退原文件）。
     */
    private function stripMetadata(string $file, string $ext): ?string
    {
        $image = match ($ext) {
            'png' => @imagecreatefrompng($file),
            'gif' => @imagecreatefromgif($file),
            'webp' => @imagecreatefromwebp($file),
            default => @imagecreatefromjpeg($file),
        };
        if ($image === false) {
            return null;
        }
        ob_start();
        try {
            $ok = match ($ext) {
                'png' => imagepng($image),
                'gif' => imagegif($image),
                'webp' => imagewebp($image),
                default => imagejpeg($image, null, 90),
            };
            $data = (string) ob_get_clean();
        } catch (\Throwable) {
            ob_end_clean();
            imagedestroy($image);
            return null;
        }
        imagedestroy($image);
        return $ok ? $data : null;
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
