# AGENTS.md

Yii2 博客重写为 Yii3 + React 后台 SPA（2026-08 完成迁移）。PHP 8.2-8.5，前台 PHP 服务端渲染（三套主题），后台 React+AntD SPA。运维手册见 [docs/local/OPS.md](docs/local/OPS.md)。

## Repository layout

```
src/            业务域分包：Admin/Web/Post/Comment/Visit/Log/User/Captcha/Category/Nav/...
  Admin/Api/    后台 JSON API：<名>/Action.php（PSR-15 invoke），包体 {ok, data, error, csrf}，fail() 默认 400
  Web/          前台页面：每个页面一个目录 Action.php + template.php（默认主题模板也在这里）
admin-web/      后台 SPA 源码（React+AntD+vite），build 产物输出 ../public/admin
themes/         crazydb、magazine 两套主题；默认主题即 src/Web/**/template.php
config/         Yii3 config-plugin 体系，入口 configuration.php；环境差异在 environments/{dev,test,prod}/
public/admin/   SPA build 产物（nginx 直接托管 /admin），emptyOutDir 会清空该目录
tests/Unit/     PHPUnit 单测；bootstrap.php 钉死 APP_ENV=test
deploy/         docker 镜像构建上下文（nginx-alpine/php8-alpine/mysql）
docs/local/     评审报告与运维手册（非公开文档）
graphify-out/   知识图谱工具产物，非源码
```

## Commands

```sh
make test                       # host 上跑 PHPUnit（自动注入 DB_HOST=127.0.0.1 DB_PORT=3306 REDIS_PASSWORD=redis.password）
./vendor/bin/phpunit --no-coverage tests/Unit/XxxTest.php    # 单文件；--filter "名称" 过滤单个用例
make psalm                      # Psalm（errorLevel=1，基线 psalm-baseline.xml）
composer phpstan                # 全量 PHPStan
cd admin-web && npm run build   # 必须在 admin-web 目录跑；产物进 ../public/admin
cd admin-web && npm run typecheck                            # tsc --noEmit
docker compose -f docker-compose-dev.yml up -d               # dev 栈：nginx:80 / php-fpm / mysql:3306 / redis:6379
./yii init/env && ./yii init/admin                            # 初始化 .env 与管理员
```

站点入口 http://localhost/ ，后台 `/admin`。dev 容器已开 `CAPTCHA_DEBUG=1`：验证码任意值直通（自动化登录靠这个）。

## Conventions

- **后台 SPA 是 HashRouter**：URL 形如 `/admin/#/posts/rank`；访问 `/admin/posts/rank` 会落到仪表盘。浏览器/playwright 验证后台功能务必带 `#`。
- **改前端后必须重新 build**：nginx 服务的是 `public/admin` 的产物，源码改动不会自动生效。
- **pre-commit hook 对 staged PHP 文件跑 PHPStan**，失败即阻止提交。hook 明确禁止：inline `@var` 覆盖推断、`@phpstan-ignore`、加 baseline 条目压制、为消错加 cast——用 instanceof 运行时收窄或修真实类型。
- **Yii3 AR 宽类型收窄惯例**：`findByPk()`/`query()->one()` 返回 `array|ActiveRecordInterface|null`，写成 `$x = ...; return $x instanceof self ? $x : null;`
- **`Post::updateAll()` 等静态调用**需行级 `@psalm-suppress InvalidStaticInvocation`（psalm 014）。
- **psalm 基线**只能由 `--set-baseline=psalm-baseline.xml` 重新生成，不要手改基线文件。
- **控制台命令注册在 `config/console/commands.php`**（`visit/sync`、`post-view/sync`、`init/migrate`、`post-view/cleanup-legacy` 等）。
- **主题切换**：ThemeFactory 按 option 表配置做 pathMap（`config/common/di/view.php`），不要在 params 里静态配 theme.pathMap（已弃用）。
- **缓存 key 统一前缀 `crazydbcache_`**（`CacheKeys::PREFIX`）；访问统计 V2 常量见 `src/Visit/VisitKeys.php`、`src/Post/PostViewKeys.php`。

## Testing

- **测试依赖本机 MySQL(3306) 和 Redis(6379) 在跑**（即 docker compose dev 栈）；连接来自 `.env` + make 注入的环境变量。
- **`APP_ENV=test` 必须用 `putenv` 钉死**（tests/bootstrap.php）：不能用 `$_ENV`，会破坏 src/bootstrap 的 `.env` 加载判断。
- **依赖表结构新增列的测试**，setUp 里幂等跑 `(new CommandTester(new InitMigrateCommand()))->execute([])`（配 `static $migrated` 标志）。缺列时 Yii AR 按 schema 静默丢弃字段、不报错——表现为断言值莫名不对。
- **测试库是共享的、有残留数据风险**：断言「无数据」前提的用例必须先清理相关行（参考 VisitServiceTest 的 deleteAll 隔离写法）。
- **Predis 测试桩 `tests/Unit/InMemoryRedisStub.php`**：真实 Predis `ClientInterface` 只有 8 个方法，其余命令走 `__call`；stub 的命令行为必须与 Predis 一致（如 `zrevrange withscores` 返回**关联数组** `{member: score}`）。

## Secrets / .env

`.env` 含本地 DB/Redis 密码，勿提交勿外泄。可信代理解析 XFF 依赖 `TRUSTED_PROXY_IPS`（`XUtils::getClientIP` 仅对可信代理解析转发头）。
