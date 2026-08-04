<?php

declare(strict_types=1);

/**
 * Mock 文章数据（开发/验收用）：在现有文章基础上追加 15 篇有意义的文章，
 * 使后台文章列表可翻页（pageSize=20）。幂等：按 alias 去重，已存在则跳过。
 *
 * 用法：DB_HOST=127.0.0.1 DB_PORT=3306 REDIS_PASSWORD=redis.password php deploy/mock-posts.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';
$root = dirname(__DIR__);
require_once $root . '/src/bootstrap.php';

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Runner\Console\ConsoleApplicationRunner;

$runner = new ConsoleApplicationRunner(rootPath: $root, debug: false, checkEvents: false, environment: 'dev');
$container = $runner->getContainer();
foreach ((array) require $root . '/config/common/bootstrap.php' as $callable) {
    $callable($container);
}

$db = $container->get(ConnectionInterface::class);
$cmd = $db->createCommand();

/** 分类 alias → id */
$catId = [];
foreach ($db->createCommand('SELECT id, alias FROM category')->queryAll() as $row) {
    $catId[$row['alias']] = (int)$row['id'];
}

$now = time();
$day = 86400;

/** [alias, 标题, 分类alias, 作者username, 天数偏移, 标签, 摘要, 正文] */
$posts = [
    [
        'alias' => 'php-composer-best-practices', 'title' => 'Composer 依赖管理的十个最佳实践',
        'cid' => 'php', 'author' => 'dabing', 'days' => 60, 'tags' => ['php', 'composer', '工程化'],
        'excerpt' => '从锁文件到语义化版本，从 require-dev 到自定义仓库——Composer 的正确打开方式。',
        'content' => <<<'MD'
## 为什么要规范依赖管理

一个项目的依赖管理混乱，往往比业务代码混乱更致命。Composer 是 PHP 世界的包管理器，用好它能让项目长期可维护。

### 1. 始终提交 composer.lock

应用项目必须把 `composer.lock` 提交到版本库，保证所有环境安装完全一致的依赖版本。只有库（library）项目才不提交。

### 2. 区分 require 与 require-dev

```js
{
    "require": {
        "php": "^8.1",
        "symfony/console": "^6.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    }
}
```

### 3. 使用语义化版本约束

- `^1.2` 允许 1.x 内升级，不包括 2.0
- `~1.2.3` 允许补丁升级，不包括次版本
- 尽量少用 `*` 通配，避免不可控升级

### 4. 自定义仓库与镜像

国内环境建议配置镜像源，加速安装。内网私有包用 `repositories` 字段指定 VCS 或 dist 仓库。

### 5. autoload 优化

生产环境使用 `composer install --no-dev --optimize-autoloader`，生成权威类映射，提升加载性能。

规范依赖管理，是从"能跑"到"能维护"的分水岭。
MD,
    ],
    [
        'alias' => 'mysql-transaction-isolation', 'title' => 'MySQL 事务隔离级别详解',
        'cid' => 'database', 'author' => 'dabing', 'days' => 55, 'tags' => ['mysql', '事务', '数据库'],
        'excerpt' => '脏读、不可重复读、幻读——四种隔离级别如何取舍，Read Committed 为什么是默认。',
        'content' => <<<'MD'
## 从并发问题说起

事务并发会带来三类问题：脏读、不可重复读、幻读。

### 隔离级别

| 级别 | 脏读 | 不可重复读 | 幻读 |
| --- | --- | --- | --- |
| Read Uncommitted | 可能 | 可能 | 可能 |
| Read Committed | 避免 | 可能 | 可能 |
| Repeatable Read | 避免 | 避免 | 可能 |
| Serializable | 避免 | 避免 | 避免 |

### 实现机制

- **Read Committed**：使用一致性快照（MVCC），每个语句一个快照
- **Repeatable Read**：整个事务一个快照，靠 undo log 保证一致性读
- 幻读在 InnoDB 中通过 **Next-Key Lock**（间隙锁）解决

### 实践建议

1. 大多数业务选 Read Committed（MySQL 默认就是它）
2. 需要事务内多次读一致时才用 Repeatable Read
3. 宁可把事务做短，也不要盲目提升隔离级别
MD,
    ],
    [
        'alias' => 'nginx-location-rules', 'title' => 'Nginx location 匹配规则完全指南',
        'cid' => 'ops', 'author' => 'admin', 'days' => 50, 'tags' => ['nginx', '运维'],
        'excerpt' => '=、^~、~、普通前缀——四种 location 的匹配优先级，一次讲清楚。',
        'content' => <<<'MD'
## location 匹配规则

Nginx location 的匹配优先级是不少运维的痛点。

### 修饰符

| 修饰符 | 含义 |
| --- | --- |
| `=` | 精确匹配 |
| `^~` | 前缀匹配，不再做正则 |
| `~` | 区分大小写的正则 |
| `~*` | 不区分大小写的正则 |

### 匹配顺序

1. 精确匹配 `=`
2. 最长前缀匹配（记录最长者）
3. 正则匹配（按配置文件顺序，命中即停）
4. 若最长前缀带 `^~`，跳过正则
5. 否则用命中正则；无正则命中则用最长前缀

### 典型配置

```plain
location = /favicon.ico {
    log_not_found off;
    access_log off;
}

location ^~ /static/ {
    expires 30d;
    gzip_static on;
}

location ~ \\.php\$ {
    fastcgi_pass php-fpm;
    include fastcgi_params;
}
```

理解匹配优先级，很多"奇怪"的路由行为就迎刃而解。
MD,
    ],
    [
        'alias' => 'php-swoole-intro', 'title' => 'Swoole 协程初探：从阻塞到并发',
        'cid' => 'php', 'author' => 'dabing', 'days' => 45, 'tags' => ['php', 'swoole', '性能'],
        'excerpt' => '用 500 行代码理解 Swoole 的协程调度，以及它和传统 PHP-FPM 的本质区别。',
        'content' => <<<'MD'
## PHP 的并发困境

传统 PHP-FPM 是"请求进来 → 处理 → 退出"，每个请求独立进程，天然无状态也天然低效。

### Swoole 的思路

常驻内存 + 事件驱动 + 协程。IO 等待时让出 CPU，其他请求继续跑。

```php
use Swoole\\Coroutine;

Coroutine::create(function () {
    \$result = \\Swoole\\Coroutine\\System::sleep(1);
    echo "协程1完成\\n";
});
```

### 关键点

- 协程是用户态调度，切换开销远小于进程/线程
- 必须使用协程安全的客户端（`Swoole\\Coroutine\\MySQL` 等），否则会阻塞整个进程
- 内存常驻意味着要小心全局状态和内存泄漏

### 何时用

高并发 IO 密集场景（网关、IM、推送）非常适合；计算密集场景收益有限。
MD,
    ],
    [
        'alias' => 'database-design-index', 'title' => '索引为什么能加速查询',
        'cid' => 'database', 'author' => 'dabing', 'days' => 42, 'tags' => ['mysql', '索引'],
        'excerpt' => 'B+ 树、聚簇索引、覆盖索引、最左前缀——从数据结构层面理解索引。',
        'content' => <<<'MD'
## 从 B+ 树说起

InnoDB 用 B+ 树作为索引结构，叶节点存数据（聚簇索引）或指向主键（二级索引）。

### 为什么 B+ 树

- 树高通常 3-4 层，一次查询最多几次磁盘 IO
- 叶节点用链表串联，范围查询高效
- 数据按主键物理有序，顺序 IO 快

### 覆盖索引

```sql
-- 只需要 name,age，而索引 (name, age) 已包含
SELECT name, age FROM user WHERE name = '张三';
```

此时无需回表，直接从索引取数据。

### 最左前缀原则

联合索引 `(a, b, c)` 能命中 `a`、`a,b`、`a,b,c`，但不能跳过 `a` 直接用 `b`。

设计索引前先分析查询，用 EXPLAIN 验证是否走索引，是每个 DBA 的基本功。
MD,
    ],
    [
        'alias' => 'redis-data-types', 'title' => 'Redis 五种数据类型的正确使用',
        'cid' => 'database', 'author' => 'admin', 'days' => 38, 'tags' => ['redis'],
        'excerpt' => 'String、Hash、List、Set、ZSet——每种类型的适用场景与常见误区。',
        'content' => <<<'MD'
## 选对类型，事半功倍

### String

计数器、缓存、限流。`INCR` 是原子操作，适合计数场景。

### Hash

对象字段的存储。适合表示结构化数据的部分更新。

```bash
HSET user:1001 name "张三" age 30
HGETALL user:1001
```

### List

消息队列、最新列表。`LPUSH` + `BRPOP` 实现可靠队列。

### Set

去重、交集并集。适合标签、关注关系。

### ZSet

排行榜、延时队列。`score` 排序天然适合。

**误区**：把大对象塞进一个 String key、滥用 keys 全量扫描、缓存数据不一致不处理——都是常见的坑。
MD,
    ],
    [
        'alias' => 'git-workflow-best', 'title' => 'Git 工作流实践：从提交信息到分支策略',
        'cid' => 'ops', 'author' => 'dabing', 'days' => 33, 'tags' => ['git', '工程化'],
        'excerpt' => '规范的 commit message、合理的分支模型、rebase 与 merge 的取舍。',
        'content' => <<<'MD'
## 提交信息即文档

好的提交信息让人在 6 个月后还能理解这次变更的意图。

### Conventional Commits

```
feat: 新增用户注册功能
fix: 修复登录态过期未跳转
docs: 补充部署文档
refactor: 重构评论模块
```

### 分支策略

- `main`：随时可发布
- `feature/*`：功能分支，合并前过 CI
- `fix/*`：紧急修复
- `release/*`：发布准备

### rebase 还是 merge

- 公共分支用 merge，保留合并历史
- 本地特性分支用 rebase，让历史线性清晰

### 小步提交

每次提交只做一件事，能回滚、能定位、能 review。这是最重要的习惯。
MD,
    ],
    [
        'alias' => 'php-enum-match', 'title' => 'PHP 8 enum 与 match 实战',
        'cid' => 'php', 'author' => 'dabing', 'days' => 30, 'tags' => ['php'],
        'excerpt' => '用枚举替代魔法字符串，用 match 替代冗长的 switch——状态机的最佳实践。',
        'content' => <<<'MD'
## 告别魔法字符串

订单状态用字符串散落各处，是项目腐化的开始。

### enum 定义

```php
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '待支付',
            self::Paid => '已支付',
            self::Shipped => '已发货',
            self::Cancelled => '已取消',
        };
    }
}
```

### match 状态流转

```php
function next(OrderStatus \$s): OrderStatus
{
    return match (\$s) {
        OrderStatus::Pending => OrderStatus::Paid,
        OrderStatus::Paid => OrderStatus::Shipped,
        default => throw new \\LogicException('非法流转'),
    };
}
```

类型安全 + 穷尽匹配，让状态机不再容易出错。
MD,
    ],
    [
        'alias' => 'docker-multi-stage', 'title' => 'Docker 多阶段构建实战',
        'cid' => 'ops', 'author' => 'admin', 'days' => 26, 'tags' => ['docker', '部署'],
        'excerpt' => '从 1.2G 到 120M——用多阶段构建缩小 PHP 镜像体积。',
        'content' => <<<'MD'
## 为什么镜像越来越臃肿

每个 RUN 步骤都会叠加一层，构建工具（gcc、composer 开发依赖）不该出现在运行时镜像里。

### 多阶段构建

```bash
# 阶段1：编译扩展
FROM php:8.2-alpine AS builder
RUN apk add --no-cache $PHPIZE_DEPS \\
    && docker-php-ext-install pdo_mysql opcache \\
    && pecl install redis \\
    && docker-php-ext-enable redis

# 阶段2：运行时
FROM php:8.2-fpm-alpine
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d
```

### 收益

1. 运行时镜像只含必要扩展
2. 构建依赖不进入最终镜像
3. 层数少，拉取快、攻击面小

### 配合 .dockerignore

把 `vendor`、`node_modules`、`.git` 排除，避免构建上下文过大。
MD,
    ],
    [
        'alias' => 'mysql-slow-log-analyze', 'title' => 'MySQL 慢查询日志分析入门',
        'cid' => 'database', 'author' => 'dabing', 'days' => 22, 'tags' => ['mysql', '性能'],
        'excerpt' => '开启慢查询日志、用 pt-query-digest 分析、定位高频慢 SQL。',
        'content' => <<<'MD'
## 开启慢查询

```sql
SET GLOBAL slow_query_log = ON;
SET GLOBAL long_query_time = 1;
SET GLOBAL slow_query_log_file = '/var/log/mysql/slow.log';
```

### 分析工具

`pt-query-digest` 是 Percona 家的利器，能按耗时/次数排序汇总。

```bash
pt-query-digest /var/log/mysql/slow.log | less
```

### 关注什么

1. **总耗时占比高的查询**——优化它们收益最大
2. **Rows examined 巨大**——没走索引的信号
3. **同指纹重复出现**——可能是热点查询

### 优化节奏

拿到慢 SQL → EXPLAIN → 确认走索引 → 改写或加索引 → 复测。一个循环一般能压掉 90% 的慢查询。
MD,
    ],
    [
        'alias' => 'phpunit-testing-guide', 'title' => '用 PHPUnit 写出可靠的测试',
        'cid' => 'php', 'author' => 'dabing', 'days' => 18, 'tags' => ['php', '测试'],
        'excerpt' => '单元测试、数据提供者、测试替身、覆盖率的正确姿势。',
        'content' => <<<'MD'
## 测试是代码的文档

一份好的测试，比任何注释都更能说明代码的意图。

### 数据提供者

```php
#[DataProvider('provideAdd')]
public function testAdd(int \$a, int \$b, int \$expected): void
{
    self::assertSame(\$expected, add(\$a, \$b));
}
```

### 测试替身

- **Stub**：返回固定值，隔离依赖
- **Mock**：验证交互（是否被调用、参数正确）
- **Fake**：真实实现的轻量替代

### 覆盖率是手段不是目的

优先覆盖**核心业务逻辑**和**易错分支**（边界、异常、空值），而不是追求 100% 的行覆盖率。

### 命名规范

`test_方法名_场景_预期结果`，让失败信息一目了然。
MD,
    ],
    [
        'alias' => 'philosophy-code-clean', 'title' => '读书笔记：《代码整洁之道》',
        'cid' => 'reading', 'author' => 'dabing', 'days' => 14, 'tags' => ['读书笔记', '工程化'],
        'excerpt' => '命名、函数、注释、错误处理——整洁代码不只是美观，更是降低维护成本。',
        'content' => <<<'MD'
## 命名是最大的工程

Robert Martin 说：**代码的阅读次数远多于书写次数**。

### 有意义的命名

- 用名词命名类，用动词短语命名函数
- 避免 `data`、`info`、`temp` 这种无信息量词汇
- 命名要表达意图，而不是实现细节

### 函数尽量小

一个函数只做一件事，参数尽量少。超过 20 行就要警惕。

### 注释的真相

**好的代码自解释**。注释应该解释"为什么"，而不是复述"做了什么"。

### 错误处理

- 用异常而不是返回码
- 特例对象（Special Case）优于空值判断
- 别吞异常，要么处理要么抛出去

整洁代码不是洁癖，是让系统能持续演进的前提。
MD,
    ],
    [
        'alias' => 'life-autumn-drive', 'title' => '秋天的第一次远行',
        'cid' => 'essay', 'author' => 'dabing', 'days' => 8, 'tags' => ['随笔', '生活'],
        'excerpt' => '沿着山谷公路一直开，秋天的颜色从绿渐变到金黄。',
        'content' => <<<'MD'
## 出发

周末临时起意，沿着山里的公路往北开。秋天的风穿过车窗，带着松树和泥土的味道。

### 路上的风景

公路两侧的树开始变色，先是零星几棵，然后是大片大片的金黄与橙红。阳光透过树梢洒下来，在柏油路上投下斑驳的影子。

### 山里的村落

中途在一个山村停下来，买了刚出锅的栗子。老板娘说今年雨水少，栗子反而甜。

### 返程

天色渐暗，回程的车上放着老歌。偶尔从日常里抽身，看看另一种节奏的生活，人会更清醒。
MD,
    ],
    [
        'alias' => 'php-attributes-intro', 'title' => 'PHP 8 属性（Attribute）是什么',
        'cid' => 'php', 'author' => 'admin', 'days' => 5, 'tags' => ['php'],
        'excerpt' => '从文档注释到一等公民——属性如何让框架代码更清晰。',
        'content' => <<<'MD'
## 从注解到属性

过去的 PHP 注解是一段注释字符串，靠解析反射。PHP 8 把注解升级为**属性**，成为语法的一部分。

### 定义与使用

```php
#[Route('/users', methods: ['GET'])]
class UserController
{
    #[Inject]
    public function __construct(private UserService \$service) {}
}
```

### 读取

```php
\$ref = new ReflectionClass(UserController::class);
\$attrs = \$ref->getAttributes(Route::class);
```

### 价值

1. 类型安全——属性是真实类，能携带任意类型
2. 静态分析友好——Psalm/IDE 能理解
3. 框架层解耦——路由、验证、依赖注入都靠它

属性把"约定"变成"类型"，是 PHP 走向现代化的重要一步。
MD,
    ],
    [
        'alias' => 'ops-log-analysis', 'title' => '线上日志分析实战：一次 500 排查记录',
        'cid' => 'ops', 'author' => 'dabing', 'days' => 2, 'tags' => ['运维', 'nginx'],
        'excerpt' => '从异常日志、access log、慢查询三个维度，定位一次偶发 500 的全过程。',
        'content' => <<<'MD'
## 现象

某接口偶发 500，频率不高但影响用户。

### 第一步：看错误日志

发现 `Lock wait timeout exceeded`，定位到是订单状态更新的事务。

### 第二步：看 access log

用 `awk` 按耗时排序，发现 500 请求集中在某个时间段，与报表任务撞车。

### 第三步：查慢查询

```sql
SHOW FULL PROCESSLIST;
```

发现报表任务的长事务持有行锁，阻塞了订单更新。

### 解决

1. 报表改为只读副本查询
2. 订单事务缩小范围，避免持有锁太久
3. 增加锁等待超时告警

一次偶发故障，背后往往是一套机制问题。
MD,
    ],
];

$inserted = 0;
$skipped = 0;
$updated = 0;
foreach ($posts as $p) {
    $exists = $db->createCommand('SELECT COUNT(*) FROM post WHERE alias = :alias')
        ->bindValue(':alias', $p['alias'])->queryScalar();
    $cid = $catId[$p['cid']] ?? 0;
    $authorId = $p['author'] === 'dabing' ? 1 : 2991;
    $postTime = $now - $p['days'] * $day;
    if ($exists > 0) {
        // 已存在：仅修复正文/摘要/标签/作者（保留原状态与时间），用于恢复误写乱码
        $cmd->setSql(
            'UPDATE `post` SET `author_name` = :authorName, `excerpt` = :excerpt, `content` = :content, `tags` = :tags WHERE `alias` = :alias',
        )->bindValues([
            ':authorName' => $p['author'],
            ':excerpt' => '<p>' . $p['excerpt'] . '</p>',
            ':content' => $p['content'],
            ':tags' => implode(',', $p['tags']),
            ':alias' => $p['alias'],
        ])->execute();
        $updated++;
        echo "修复: {$p['title']}\n";
        continue;
    }
    $cmd->setSql(
        'INSERT INTO `post`
         (`cid`, `author_id`, `author_name`, `type`, `title`, `alias`, `excerpt`, `content`, `format`,
          `status`, `create_time`, `post_time`, `update_time`, `tags`, `comment_count`, `view_count`, `is_top`)
         VALUES (:cid, :author, :authorName, :type, :title, :alias, :excerpt, :content, :format,
          :status, :ct, :pt, :ut, :tags, 0, :vc, :top)',
    )->bindValues([
        ':cid' => $cid,
        ':author' => $authorId,
        ':authorName' => $p['author'],
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
        ':vc' => random_int(80, 1500),
        ':top' => 0,
    ])->execute();
    $inserted++;
    echo "插入: {$p['title']}\n";
}

echo "\n完成！新增 {$inserted} 篇，修复 {$updated} 篇，跳过 {$skipped} 篇。当前文章总数：" .
    (int)$db->createCommand('SELECT COUNT(*) FROM post')->queryScalar() . PHP_EOL;
