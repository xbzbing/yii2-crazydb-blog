<?php

declare(strict_types=1);

/**
 * 演示数据生成（开发/验收用）：分类/标签/文章/评论/导航。
 * 幂等：先清空内容表再插入（仅限本地 dev 环境）。
 *
 * 用法：DB_HOST=127.0.0.1 DB_PORT=3306 REDIS_PASSWORD=redis.password php deploy/demo-data.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';
$root = dirname(__DIR__);
require_once $root . '/src/bootstrap.php';

use App\User\User;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Runner\Console\ConsoleApplicationRunner;

$runner = new ConsoleApplicationRunner(rootPath: $root, debug: false, checkEvents: false, environment: 'dev');
$container = $runner->getContainer();
foreach ((array) require $root . '/config/common/bootstrap.php' as $callable) {
    $callable($container);
}

$db = $container->get(ConnectionInterface::class);
$cmd = $db->createCommand();

echo "清空内容表（category/tag/post/comment/nav）...\n";
foreach (['comment', 'post', 'tag', 'category', 'nav'] as $t) {
    $cmd->setSql("DELETE FROM `$t`")->execute();
}

$now = time();
$day = 86400;

/** 分类树：pid 为父分类在数组中的下标（0-based），0 表示顶级 */
$cats = [
    ['技术', 'tech', -1, 100, 'PHP、数据库、运维等开发实践'],
    ['PHP', 'php', 0, 100, 'PHP 语言与框架'],
    ['数据库', 'database', 0, 90, 'MySQL/Redis 等存储'],
    ['运维部署', 'ops', 0, 80, 'Docker/Nginx/CI'],
    ['生活', 'life', -1, 90, '日常记录'],
    ['随笔', 'essay', 4, 90, '零碎想法'],
    ['读书', 'reading', -1, 80, '阅读笔记'],
];
$catIds = [];
echo "插入分类...\n";
foreach ($cats as $i => [$name, $alias, $pidIdx, $sort, $desc]) {
    $pid = $pidIdx < 0 ? 0 : $catIds[$pidIdx];
    $cmd->setSql(
        'INSERT INTO `category` (`name`, `alias`, `desc`, `pid`, `display`, `sort_order`, `keywords`, `update_time`)
         VALUES (:name, :alias, :desc, :pid, :display, :sort, :keywords, :ut)',
    )->bindValues([
        ':name' => $name, ':alias' => $alias, ':desc' => $desc, ':pid' => $pid,
        ':display' => 'list', ':sort' => $sort, ':keywords' => $name, ':ut' => $now,
    ])->execute();
    $catIds[] = (int)$db->getLastInsertID();
    echo "  - $name (alias=$alias)\n";
}
[$tech, $php, $dbCat, $ops, $life, $essay, $reading] = array_map(
    fn ($v) => $v,
    $catIds,
);

/** 作者 */
$dabing = User::findByUsername('dabing');
$admin = User::findByUsername('admin');
$dabingId = $dabing !== null ? (int)$dabing->id : 1;
$adminId = $admin !== null ? (int)$admin->id : $dabingId;

/** 文章：title/alias/cid/作者/time偏移/标签/摘要/正文 */
$posts = [
    [
        'title' => 'Yii2 博客迁移到 Yii3 的心路历程', 'alias' => 'yii2-to-yii3-migration', 'cid' => $php,
        'author' => $dabingId, 'days' => 30, 'tags' => ['php', 'yii3', '迁移'],
        'excerpt' => '从 Yii2 到 Yii3 是一次彻底的重写：配置体系、容器、路由、视图渲染全部换代。这篇文章记录迁移过程中的关键决策与踩坑。',
        'content' => <<<MD
## 为什么迁移

Yii2 已经进入维护期，而 Yii3 带来了完全不同的架构：**依赖注入容器**、**PSR 规范全面拥抱**、**组件化重构**。

### 最大的变化

1. 配置从"魔法数组"变成 PHP 原生代码
2. 数据库访问基于 PDO 抽象层，不再依赖 AR 魔法
3. 视图渲染引入 Theme 机制，主题切换变得简单

### 迁移路线

```php
// Yii2 时代的路由
'urlManager' => ['enablePrettyUrl' => true]

// Yii3：路由即代码
Route::get('/')->action(HomePage\Action::class)->name('site/index')
```

迁移后最大的收益是**类型安全**：Psalm 全程静态分析，重构不再心慌。
MD,
    ],
    [
        'title' => 'MySQL 索引优化实战', 'alias' => 'mysql-index-optimization', 'cid' => $dbCat,
        'author' => $dabingId, 'days' => 27, 'tags' => ['mysql', '索引', '性能'],
        'excerpt' => '一次慢查询排查，从 EXPLAIN 到覆盖索引，把 3 秒的查询优化到 30 毫秒。',
        'content' => <<<MD
## 慢查询背景

线上一个列表接口响应 3 秒，`SHOW PROCESSLIST` 显示大量 Sending data。

### EXPLAIN 分析

`type=ALL`，全表扫描 200 万行——没有走任何索引。

### 三个优化动作

1. **联合索引** `(status, post_time)` 覆盖最常见的筛选排序
2. **覆盖索引** 查询只取索引内字段，避免回表
3. **分页改写** 延迟关联，先取主键再回表取详情

### 结论

索引不是越多越好，要对着**真实查询模式**设计。
MD,
    ],
    [
        'title' => 'Redis 缓存失效的三大坑', 'alias' => 'redis-cache-pitfalls', 'cid' => $dbCat,
        'author' => $adminId, 'days' => 24, 'tags' => ['redis', '缓存'],
        'excerpt' => '缓存穿透、击穿、雪崩，以及"缓存与数据库一致性"这个老话题。',
        'content' => <<<MD
## 三大坑

### 穿透

查询不存在的数据，每次都打到数据库。**解法**：布隆过滤器 + 空值缓存。

### 击穿

热点 key 过期瞬间大量请求打到 DB。**解法**：互斥锁重建 + 逻辑过期。

### 雪崩

大量 key 同时过期。**解法**：过期时间加随机抖动。

### 一致性

本项目采用**版本化 key**：`config_sys.{MAX(update_time)}`，配置变更即换 key，天然避免脏读。
MD,
    ],
    [
        'title' => 'Docker 化部署 PHP 应用的实践', 'alias' => 'docker-php-deploy', 'cid' => $ops,
        'author' => $dabingId, 'days' => 20, 'tags' => ['docker', 'nginx', '部署'],
        'excerpt' => 'nginx + php-fpm + mysql + redis 四件套的 Compose 编排，与多环境配置管理。',
        'content' => <<<MD
## 架构

```
nginx (80/443) → php-fpm (9000) → mysql (3306) / redis (6379)
```

### 关键点

- php 容器用 **alpine 镜像**，安装 GD、PDO、Redis 扩展
- 代码卷挂载，本地改动即时生效
- 环境变量注入：`DB_HOST`、`REDIS_PASSWORD` 全部走 Compose

### 多环境

`docker-compose-dev.yml` 与生产分开，dev 开启 `CAPTCHA_DEBUG=1` 方便验收。
MD,
    ],
    [
        'title' => '初秋随笔', 'alias' => 'early-autumn-notes', 'cid' => $essay,
        'author' => $dabingId, 'days' => 15, 'tags' => ['随笔', '生活'],
        'excerpt' => '窗外梧桐开始落叶，一年中最舒服的季节到了。',
        'content' => <<<MD
## 初秋

九月的风终于凉了下来。楼下银杏还没有黄透，但早晨的露水已经带着凉意。

> 秋天是第二个春天，每一片叶子都是一朵花。——加缪

这个季节适合泡一杯热茶，把攒了半年的技术债慢慢还掉。
MD,
    ],
    [
        'title' => '读《禅与摩托车维修艺术》', 'alias' => 'zen-motorcycle-notes', 'cid' => $reading,
        'author' => $dabingId, 'days' => 12, 'tags' => ['读书笔记', '哲学'],
        'excerpt' => '良质（Quality）不是客观的，也不是主观的——而是主客体之间的那道裂缝。',
        'content' => <<<MD
## 良质

波西格沿着公路骑行，讨论一个朴素的命题：**什么是"好"？**

维修摩托车时你会遇到两种人：

1. 只看结果的"浪漫主义者"
2. 只看过程的"古典主义者"

而良质，是让两者统一的东西。

### 与编程的共鸣

写代码也是同样的修行。可维护性、清晰度、测试——这些"良质"无法量化，但每个有经验的工程师都能感受到。
MD,
    ],
    [
        'title' => 'PHP 8 新特性速览', 'alias' => 'php8-features-overview', 'cid' => $php,
        'author' => $adminId, 'days' => 9, 'tags' => ['php'],
        'excerpt' => '构造器属性提升、命名参数、match 表达式、enum 与 readonly——一次讲清楚。',
        'content' => <<<MD
## 值得升级的理由

### 构造器属性提升

```php
// 8.0 以前
class User {
    public function __construct(private string $name) {}
}

// 一行搞定
class User {
    public function __construct(private string $name) {}
}
```

### match 表达式

```php
$status = match ($code) {
    200, 201 => 'ok',
    404 => 'not_found',
    default => 'error',
};
```

### readonly 类

```php
readonly class Point {
    public function __construct(public float $x, public float $y) {}
}
```

PHP 8 让这门语言重新变得可爱。
MD,
    ],
    [
        'title' => 'Nginx 配置 HTTPS 全记录', 'alias' => 'nginx-https-setup', 'cid' => $ops,
        'author' => $dabingId, 'days' => 5, 'tags' => ['nginx', 'https', '运维'],
        'excerpt' => '从证书申请、443 监听、HTTP/2、到 HSTS 与安全头，一次到位。',
        'content' => <<<MD
## 基础配置

```nginx
server {
    listen 443 ssl http2;
    server_name blog.example.com;

    ssl_certificate     /etc/nginx/certs/fullchain.pem;
    ssl_certificate_key /etc/nginx/certs/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
}
```

### 安全头

```nginx
add_header Strict-Transport-Security "max-age=31536000" always;
add_header X-Content-Type-Options nosniff;
add_header X-Frame-Options SAMEORIGIN;
```

### 别忘了

- 80 端口 301 跳转
- OCSP Stapling
- `ssl_session_cache shared:SSL:10m`
MD,
    ],
    [
        'title' => '数据库设计：范式与反范式', 'alias' => 'db-normalization-design', 'cid' => $dbCat,
        'author' => $dabingId, 'days' => 3, 'tags' => ['数据库', '设计'],
        'excerpt' => '第三范式是教科书，但真实系统里你会在两个地方违反它。',
        'content' => <<<MD
## 教科书 vs 现实

三范式保证更新一致性，但代价是查询时的大量 JOIN。

### 该反范式的地方

1. **统计字段**：`comment_count` 冗余在文章表，比 COUNT(*) 快一个数量级
2. **冗余快照**：`author_name` 存在文章表，作者改名不影响历史文章

### 原则

- 核心业务数据严格范式化
- 读多写少的聚合数据反范式化
- 用代码保证冗余字段的一致性（写时更新）
MD,
    ],
    [
        'title' => '咖啡与代码', 'alias' => 'coffee-and-code', 'cid' => $essay,
        'author' => $dabingId, 'days' => 1, 'tags' => ['随笔'],
        'excerpt' => '手冲咖啡的注水曲线，和调试代码的过程意外地相似。',
        'content' => <<<MD
## 手冲与调试

注水要"由内向外、缓慢均匀"——就像排查问题要从核心向外扩散。

闷蒸 30 秒让咖啡粉排气，就像先让程序稳定复现。

### 器材

- 手冲壶：温控 92℃
- 磨豆机：中细研磨
- 粉水比：1:15

喝一口，接着写代码。
MD,
    ],
];

echo "插入文章...\n";
$postIds = [];
foreach ($posts as $i => $p) {
    $postTime = $now - $p['days'] * $day;
    $cmd->setSql(
        'INSERT INTO `post`
         (`cid`, `author_id`, `author_name`, `type`, `title`, `alias`, `excerpt`, `content`, `format`,
          `status`, `create_time`, `post_time`, `update_time`, `tags`, `comment_count`, `view_count`, `is_top`)
         VALUES (:cid, :author, :authorName, :type, :title, :alias, :excerpt, :content, :format,
          :status, :ct, :pt, :ut, :tags, 0, :vc, :top)',
    )->bindValues([
        ':cid' => $p['cid'],
        ':author' => $p['author'],
        ':authorName' => $p['author'] === $dabingId ? 'dabing' : 'admin',
        ':type' => 'post',
        ':title' => $p['title'],
        ':alias' => $p['alias'],
        ':excerpt' => '<p>' . $p['excerpt'] . '</p>',
        ':content' => $p['content'],
        ':format' => 'markdown',
        ':status' => 'published',
        ':ct' => $postTime,
        ':pt' => $postTime,
        ':ut' => $postTime,
        ':tags' => implode(',', $p['tags']),
        ':vc' => random_int(50, 900),
        ':top' => $i === 0 ? 1 : 0,
    ])->execute();
    $postIds[] = (int)$db->getLastInsertID();
    echo "  - [{$postIds[count($postIds) - 1]}] {$p['title']}\n";
}

echo "插入标签...\n";
$tagCount = 0;
foreach ($posts as $i => $p) {
    foreach ($p['tags'] as $tag) {
        $cmd->setSql(
            'INSERT INTO `tag` (`name`, `pid`, `cid`, `create_time`) VALUES (:name, :pid, :cid, :ct)',
        )->bindValues([':name' => $tag, ':pid' => $postIds[$i], ':cid' => $p['cid'], ':ct' => $now])->execute();
        $tagCount++;
    }
}
echo "  - $tagCount 条标签关联\n";

echo "插入评论...\n";
$comments = [
    // [文章idx, 昵称, 邮箱, 内容, 状态, replyToIdx(0=无)]
    [0, '访客小明', 'xiaoming@example.com', '迁移到 Yii3 的收益这么明显吗？我正在犹豫要不要从 Yii2 升级。', 'approved', 0],
    [0, 'dabing', 'dabing@example.com', '如果项目还在活跃开发，强烈建议迁移；纯维护的话可以再等等。', 'approved', 0],
    [0, '路过的人', 'passer@example.com', '感谢分享，路由从配置到代码这个变化最直观。', 'approved', 0],
    [1, 'DBA老王', 'laowang@example.com', '覆盖索引那一段太实用了，我们线上也有类似的慢查询。', 'approved', 0],
    [2, '缓存小白', 'cache@example.com', '版本化 key 这个思路很妙，学习了！', 'approved', 0],
    [3, '运维老王', 'ops@example.com', 'alpine 镜像小，但编译扩展的时候容易踩坑，建议上 multi-stage。', 'approved', 0],
    [4, '匿名访客', 'anon@example.com', '加缪那句话我也很喜欢。', 'approved', 0],
    [6, '面试者', 'job@example.com', 'match 表达式真的比 switch 优雅太多了。', 'approved', 0],
    [6, '待审核用户', 'spam@example.com', '（这是一条待审核评论，用来测试后台审核流程）', 'unapproved', 0],
    [8, '强迫症', 'ocd@example.com', '手冲 1:15 是不是浓了点？我喜欢 1:16.5。', 'approved', 0],
    [8, 'dabing', 'dabing@example.com', '看豆子，浅烘我会用 1:16，深烘 1:14 左右。', 'approved', 0],
];
$commentIds = [];
foreach ($comments as [$pidx, $nick, $email, $content, $status, $replyIdx]) {
    $ct = $now - (($posts[$pidx]['days'] - 1) * $day + random_int(0, 20) * 3600);
    $cmd->setSql(
        'INSERT INTO `comment` (`pid`, `uid`, `nickname`, `email`, `reply_to`, `url`, `ip`, `user_agent`, `create_time`, `update_time`, `content`, `status`)
         VALUES (:pid, :uid, :nick, :email, :reply, NULL, :ip, :ua, :ct, :ct, :content, :status)',
    )->bindValues([
        ':pid' => $postIds[$pidx],
        ':uid' => in_array($nick, ['dabing', 'admin'], true) ? ($nick === 'dabing' ? $dabingId : $adminId) : null,
        ':nick' => $nick,
        ':email' => $email,
        ':reply' => $replyIdx > 0 ? $commentIds[$replyIdx - 1] : null,
        ':ip' => '127.0.0.1',
        ':ua' => 'Mozilla/5.0 (demo seed)',
        ':ct' => $ct,
        ':content' => $content,
        ':status' => $status,
    ])->execute();
    $commentIds[] = (int)$db->getLastInsertID();
    echo "  - [{$commentIds[count($commentIds) - 1]}] $nick: " . mb_substr($content, 0, 20) . "...\n";
}

echo "回填 comment_count...\n";
foreach ($posts as $i => $p) {
    $cnt = 0;
    foreach ($comments as [$pidx, , , , $st, ]) {
        if ($pidx === $i && $st === 'approved') {
            $cnt++;
        }
    }
    $cmd->setSql('UPDATE `post` SET `comment_count` = :c WHERE `id` = :id')
        ->bindValues([':c' => $cnt, ':id' => $postIds[$i]])->execute();
}

echo "插入导航...\n";
$navs = [
    ['首页', 'site/index', 1, 100],
    ['归档', 'post/archives', 1, 90],
    ['标签', 'tag/list', 1, 80],
    ['RSS', 'feed/rss', 1, 70],
];
foreach ($navs as [$name, $url, $route, $sort]) {
    $cmd->setSql(
        'INSERT INTO `nav` (`pid`, `name`, `url`, `route`, `sort_order`, `create_time`, `update_time`)
         VALUES (0, :name, :url, :route, :sort, :ct, :ct)',
    )->bindValues([':name' => $name, ':url' => $url, ':route' => $route, ':sort' => $sort, ':ct' => $now])->execute();
    echo "  - $name ($url)\n";
}

// 确保墨刊主题仍在（验收默认外观）
$cmd->setSql(
    "INSERT INTO `option` (`type`, `name`, `value`, `update_time`) VALUES ('sys', 'theme', 'magazine', :ut)
     ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `update_time` = VALUES(`update_time`)",
)->bindValues([':ut' => $now])->execute();

echo "\n完成！分类 " . count($cats) . ' 个、文章 ' . count($posts) . ' 篇、标签 ' . $tagCount . ' 条、评论 ' . count($comments) . ' 条、导航 ' . count($navs) . ' 条。' . PHP_EOL;
echo "主题：magazine（墨刊）。\n";
