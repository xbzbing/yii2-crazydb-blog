<?php

declare(strict_types=1);

namespace App\Visit;

/**
 * 前台访问类型分类：按 UA 判定「爬虫 / 脚本 / 正常」。
 *
 * 关键词从 option（sys）配置读取，英文逗号「,」拆分；配置为空/缺失时回退到
 * 本类默认值（见 DEFAULT_*，与需求给定的默认一致）。
 *
 * 判定优先级（先命中先返回）：
 *   1. 命中任一爬虫关键词 → 'crawler'
 *   2. 命中任一脚本关键词 → 'script'
 *   3. 其余 → 'normal'
 * 匹配大小写不敏感（实践里 UA 大小写混杂，如 `Mozilla/... Spider`）。
 */
final class VisitClassifier
{
    /** option key：爬虫访问关键词 */
    public const OPTION_BOT_KEYWORDS = 'visit_bot_keywords';
    /** option key：脚本访问关键词 */
    public const OPTION_SCRIPT_KEYWORDS = 'visit_script_keywords';

    /** 中间件关键词配置的缓存键（固定键 + 短 TTL，避免每请求触发 DB MAX 查询） */
    public const KEYWORDS_CACHE_KEY = 'visit_keywords_config';
    /** 关键词配置缓存 TTL（秒）：后台修改后最长该时长内生效 */
    public const KEYWORDS_CACHE_TTL = 300;

    public const TYPE_CRAWLER = 'crawler';
    public const TYPE_SCRIPT = 'script';
    public const TYPE_NORMAL = 'normal';

    /** @var list<string> 爬虫关键词默认值 */
    public const DEFAULT_BOT_KEYWORDS = ['spider', 'bingbot', 'bot.html'];
    /** @var list<string> 脚本关键词默认值 */
    public const DEFAULT_SCRIPT_KEYWORDS = ['python-', 'curl', 'wget', 'axios', 'java-http-client', 'java/', 'headless'];

    /**
     * 解析英文逗号分隔的关键词配置为小写列表（去空白、去空项）。
     * 兼容中文逗号「，」/「、」：先归一为英文逗号，避免整段被当成一个关键词导致分类失效。
     *
     * @return list<string>
     */
    public static function parseKeywords(string $raw): array
    {
        $raw = str_replace(['，', '、'], ',', $raw);
        $result = [];
        foreach (explode(',', $raw) as $part) {
            $part = strtolower(trim($part));
            if ($part !== '') {
                $result[] = $part;
            }
        }
        return $result;
    }

    /**
     * 按 UA 判定访问类型（爬虫优先，其次脚本，其余正常）。
     *
     * @param list<string> $botKeywords 爬虫关键词（小写）
     * @param list<string> $scriptKeywords 脚本关键词（小写）
     */
    public static function classify(string $userAgent, array $botKeywords, array $scriptKeywords): string
    {
        $ua = strtolower(trim($userAgent));
        if ($ua === '') {
            return self::TYPE_NORMAL;
        }
        foreach ($botKeywords as $keyword) {
            if ($keyword !== '' && str_contains($ua, $keyword)) {
                return self::TYPE_CRAWLER;
            }
        }
        foreach ($scriptKeywords as $keyword) {
            if ($keyword !== '' && str_contains($ua, $keyword)) {
                return self::TYPE_SCRIPT;
            }
        }
        return self::TYPE_NORMAL;
    }
}
