<?php

declare(strict_types=1);

namespace App\Web\Sitemap;

use App\Category\Category;
use App\Post\Post;
use App\Tag\Tag;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * XML Sitemap：固定页 + 文章（published）+ 分类 + 标签。
 */
final readonly class Action
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(): ResponseInterface
    {
        $urls = [
            ['loc' => $this->urlGenerator->generateAbsolute('site/index'), 'priority' => '1.0'],
            ['loc' => $this->urlGenerator->generateAbsolute('post/list'), 'priority' => '0.8'],
            ['loc' => $this->urlGenerator->generateAbsolute('post/archives'), 'priority' => '0.6'],
            ['loc' => $this->urlGenerator->generateAbsolute('tag/list'), 'priority' => '0.6'],
        ];

        /** @var list<Post> $posts */
        $posts = Post::query()
            ->select('id,alias,title,update_time')
            ->where(['status' => Post::STATUS_PUBLISHED])
            ->orderBy(['update_time' => SORT_DESC])
            ->all();
        foreach ($posts as $post) {
            $url = $post->getUrl($this->urlGenerator, true);
            if ($url !== null) {
                $urls[] = [
                    'loc' => $url,
                    'lastmod' => date('c', (int)$post->update_time),
                    'priority' => '0.9',
                ];
            }
        }

        /** @var list<Category> $categories */
        $categories = Category::query()->all();
        foreach ($categories as $category) {
            $urls[] = [
                'loc' => $this->urlGenerator->generateAbsolute('category/show', ['alias' => $category->alias]),
                'priority' => '0.7',
            ];
        }

        /** @var list<array{name: string}> $tagRows */
        $tagRows = Tag::query()->select('name')->distinct()->asArray()->all();
        foreach ($tagRows as $row) {
            $urls[] = [
                'loc' => $this->urlGenerator->generateAbsolute('tag/show', ['name' => (string)$row['name']]),
                'priority' => '0.5',
            ];
        }

        $body = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $body .= "  <url>\n"
                . '    <loc>' . $this->escape($url['loc']) . "</loc>\n"
                . (isset($url['lastmod']) ? '    <lastmod>' . $this->escape($url['lastmod']) . "</lastmod>\n" : '')
                . '    <priority>' . $url['priority'] . "</priority>\n"
                . "  </url>\n";
        }
        $body .= '</urlset>';

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($body);
        return $response
            ->withHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->withHeader('Cache-Control', 'no-cache');
    }

    private function escape(string $text): string
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text) ?? $text;
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
