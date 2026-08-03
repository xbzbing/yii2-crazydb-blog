<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Post\MarkdownRenderer;
use App\Post\Post;
use App\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Feed 输出结构断言（RSS/Atom XML 合法 + 关键元素）。
 */
final class FeedStructureTest extends TestCase
{
    private function rss(): ResponseInterface
    {
        $action = new \App\Web\FeedRss\Action(
            new \HttpSoft\Message\ResponseFactory(),
            $this->container()->get(\Yiisoft\Router\UrlGeneratorInterface::class),
            new Cache(new ArrayCache()),
            new MarkdownRenderer(new Cache(new ArrayCache())),
        );
        return $action();
    }

    private function atom(): ResponseInterface
    {
        $action = new \App\Web\FeedAtom\Action(
            new \HttpSoft\Message\ResponseFactory(),
            $this->container()->get(\Yiisoft\Router\UrlGeneratorInterface::class),
            new Cache(new ArrayCache()),
            new MarkdownRenderer(new Cache(new ArrayCache())),
        );
        return $action();
    }

    public function testRssIsValidXmlWithRequiredElements(): void
    {
        $response = $this->rss();
        self::assertStringContainsString('application/rss+xml', $response->getHeaderLine('Content-Type'));
        $xml = simplexml_load_string((string)$response->getBody());
        self::assertNotFalse($xml, 'RSS must be valid XML');
        self::assertSame('2.0', (string)$xml['version']);
        self::assertNotSame('', (string)$xml->channel->title);
        self::assertNotSame('', (string)$xml->channel->link);
        self::assertNotSame('', (string)$xml->channel->lastBuildDate);
    }

    public function testAtomIsValidXmlWithRequiredElements(): void
    {
        $response = $this->atom();
        self::assertStringContainsString('application/atom+xml', $response->getHeaderLine('Content-Type'));
        $xml = simplexml_load_string((string)$response->getBody());
        self::assertNotFalse($xml, 'Atom must be valid XML');
        self::assertSame('feed', $xml->getName());
        self::assertSame('http://www.w3.org/2005/Atom', (string)$xml->getNamespaces()['']);
        self::assertNotSame('', (string)$xml->title);
        self::assertNotSame('', (string)$xml->updated);
        self::assertNotSame('', (string)$xml->id);
    }

    public function testFeedEscapesControlCharacters(): void
    {
        $post = new Post();
        $post->title = "标题\x00\x1f控制字符";
        $post->alias = 'ctrl-test';
        $post->author_id = 1;
        $post->author_name = '测试';
        $post->content = '<p>正文</p>';
        $post->format = Post::FORMAT_HTML;
        $post->status = Post::STATUS_PUBLISHED;
        $post->post_time = time();
        $post->update_time = time();
        $post->create_time = time();
        $post->save();
        try {
            $body = (string)$this->rss()->getBody();
            self::assertNotFalse(simplexml_load_string($body), 'RSS must stay valid with control chars in title');
            $atomBody = (string)$this->atom()->getBody();
            self::assertNotFalse(simplexml_load_string($atomBody), 'Atom must stay valid with control chars in title');
        } finally {
            $post->delete();
        }
    }
}
