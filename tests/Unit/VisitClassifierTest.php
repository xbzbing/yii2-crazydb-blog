<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;
use App\Visit\VisitClassifier;

final class VisitClassifierTest extends TestCase
{
    // ---------- parseKeywords ----------

    public function testParseKeywordsSplitsByCommaTrimsAndLowercases(): void
    {
        self::assertSame(
            ['spider', 'bingbot', 'bot.html'],
            VisitClassifier::parseKeywords('spider, BingBot ,bot.html'),
        );
    }

    public function testParseKeywordsDropsEmptyAndWhitespaceOnly(): void
    {
        self::assertSame([], VisitClassifier::parseKeywords(''));
        self::assertSame([], VisitClassifier::parseKeywords('  ,  ,'));
        self::assertSame(['curl'], VisitClassifier::parseKeywords(' , curl , '));
    }

    public function testParseKeywordsToleratesChineseComma(): void
    {
        // 中文逗号/顿号归一为英文逗号，避免整段被当成单个关键词
        self::assertSame(
            ['spider', 'bingbot', 'bot.html'],
            VisitClassifier::parseKeywords('spider，bingbot、bot.html'),
        );
        self::assertSame(
            ['python-', 'curl', 'wget'],
            VisitClassifier::parseKeywords("python-， curl 、wget"),
        );
    }

    // ---------- classify ----------

    public function testCrawlerKeywordsMatchCaseInsensitively(): void
    {
        foreach (VisitClassifier::DEFAULT_BOT_KEYWORDS as $keyword) {
            // 关键词大小写无关：UA 里可能大写/混写
            $uaCased = 'Mozilla/5.0 (compatible; ' . ucfirst($keyword) . '; +http://example.com)';
            self::assertSame(
                VisitClassifier::TYPE_CRAWLER,
                VisitClassifier::classify($uaCased, VisitClassifier::DEFAULT_BOT_KEYWORDS, VisitClassifier::DEFAULT_SCRIPT_KEYWORDS),
                "UA 含爬虫关键词 {$keyword} 应判定 crawler",
            );
        }
    }

    public function testScriptKeywordsMatch(): void
    {
        $scripts = [
            'Python-urllib/3.8',
            'curl/7.68.0',
            'Wget/1.20.3',
            'axios/0.27.2',
            'java-http-client/17.0.1',
            'Java/11.0.2',
            'HeadlessChrome/90.0',
        ];
        foreach ($scripts as $ua) {
            self::assertSame(
                VisitClassifier::TYPE_SCRIPT,
                VisitClassifier::classify($ua, VisitClassifier::DEFAULT_BOT_KEYWORDS, VisitClassifier::DEFAULT_SCRIPT_KEYWORDS),
                "UA {$ua} 应判定 script",
            );
        }
    }

    public function testNormalBrowserUaIsNormal(): void
    {
        $normal = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1',
            '',
            '   ',
        ];
        foreach ($normal as $ua) {
            self::assertSame(
                VisitClassifier::TYPE_NORMAL,
                VisitClassifier::classify($ua, VisitClassifier::DEFAULT_BOT_KEYWORDS, VisitClassifier::DEFAULT_SCRIPT_KEYWORDS),
                "UA '{$ua}' 应判定 normal",
            );
        }
    }

    public function testBotTakesPrecedenceOverScript(): void
    {
        // 同时命中爬虫与脚本关键词：爬虫优先
        $ua = 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm) python-requests';
        self::assertSame(
            VisitClassifier::TYPE_CRAWLER,
            VisitClassifier::classify($ua, VisitClassifier::DEFAULT_BOT_KEYWORDS, VisitClassifier::DEFAULT_SCRIPT_KEYWORDS),
        );
    }

    public function testCustomKeywordConfigurationIsUsed(): void
    {
        $bot = ['mycrawler', 'sitesearch'];
        $script = ['foo-bar', 'scraper'];
        self::assertSame(VisitClassifier::TYPE_CRAWLER, VisitClassifier::classify('UA mycrawler', $bot, $script));
        self::assertSame(VisitClassifier::TYPE_CRAWLER, VisitClassifier::classify('sitesearch/1.0', $bot, $script));
        self::assertSame(VisitClassifier::TYPE_SCRIPT, VisitClassifier::classify('foo-bar client', $bot, $script));
        self::assertSame(VisitClassifier::TYPE_SCRIPT, VisitClassifier::classify('X scraper', $bot, $script));
        self::assertSame(VisitClassifier::TYPE_NORMAL, VisitClassifier::classify('Chrome normal', $bot, $script));
    }

    public function testEmptyKeywordListFallsBackToNormal(): void
    {
        // 关键词列表为空时，任何 UA 都算 normal（配置被清空的防御）
        self::assertSame(VisitClassifier::TYPE_NORMAL, VisitClassifier::classify('spider', [], []));
    }

    public function testDefaultsMatchRequirement(): void
    {
        self::assertSame(['spider', 'bingbot', 'bot.html'], VisitClassifier::DEFAULT_BOT_KEYWORDS);
        self::assertSame(
            ['python-', 'curl', 'wget', 'axios', 'java-http-client', 'java/', 'headless'],
            VisitClassifier::DEFAULT_SCRIPT_KEYWORDS,
        );
        // 配置值恰好是默认值逗号串
        self::assertSame(
            'spider,bingbot,bot.html',
            implode(',', VisitClassifier::DEFAULT_BOT_KEYWORDS),
        );
        self::assertSame(
            'python-,curl,wget,axios,java-http-client,java/,headless',
            implode(',', VisitClassifier::DEFAULT_SCRIPT_KEYWORDS),
        );
    }
}
