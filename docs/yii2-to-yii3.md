# Yii2 → Yii3 升级工作总结

> 起始日期：2026-08 | 分支：yii3 | 状态：开发完成，待上线

## 概述

基于 yiisoft/app 官方模板新建 Yii3 骨架，概念性重写博客系统。PHP 8.5 + Docker（nginx/php/mysql/redis）。

数据表沿用 Yii2 的 `blog_` 表前缀：Yii3 AR 通过 `tableName()` 返回 `{{%xxx}}` 占位符，
`Connection::setTablePrefix()` 配置前缀（`config/common/di/db-mysql.php`，env `DB_TABLE_PREFIX` 可覆盖，默认 `blog_`）。

## 技术选型

| 项 | 选型 |
|---|---|
| 框架 | Yii3 (yiisoft/app) |
| 数据库 | MySQL 8.4，AR typed 属性 |
| 表前缀 | Yii 范式 `{{%xxx}}` + setTablePrefix（默认 `blog_`） |
| 缓存 | Redis (yiisoft/cache-redis + predis) |
| 编辑器 | Vditor 3.10.5（ir 模式） |
| Markdown | league/commonmark 2.8 (GFM) |
| 代码高亮 | SyntaxHighlighter 兼容输出 |
| 邮件 | yiisoft/mailer + symfony/mailer |
| 验证码 | GD 自实现（yiisoft/captcha 不存在） |
| 测试 | phpunit 12 + 自写引导 |
| 静态分析 | psalm |

## 技术选型

| 项 | 选型 |
|---|---|
| 框架 | Yii3 (yiisoft/app) |
| 数据库 | MySQL 8.4，AR typed 属性 |
| 缓存 | Redis (yiisoft/cache-redis + predis) |
| 编辑器 | Vditor 3.10.5（ir 模式） |
| Markdown | league/commonmark 2.8 (GFM) |
| 代码高亮 | SyntaxHighlighter 兼容输出 |
| 邮件 | yiisoft/mailer + symfony/mailer |
| 验证码 | GD 自实现（yiisoft/captcha 不存在） |
| 测试 | phpunit 12 + 自写引导 |
| 静态分析 | psalm |

## 迁移阶段

### A：骨架与基础设施
- yiisoft/app 骨架 + composer 依赖
- 数据库连接（9 表 AR typed 属性）
- 路由迁移（29 条兼容路由，匹配测试锁定）

### B：业务逻辑
- 用户体系（User AR + Identity + 记住我 token）
- 邮件模块（symfony/mailer，MAILER_DSN 可配置）
- 缓存（PSR-16 + 版本化 key，替代 DbDependency）
- 评论（antiSpam + 审核流 + 邮件通知 + 防冒用）
- 注册（站点 scheme 校验 + info HTMLPurifier）

### C：Markdown 管线
- MarkdownRenderer（GFM + 缓存 + format 分派）
- 代码块 → SyntaxHighlighter 兼容输出
- HTMLPurifier 输出净化

### D：编辑器替换
- Vditor vendored（v3.10.5 完整 dist）
- 上传对接 admin/upload/image

### E：前台
- 首页/文章/分类/标签/归档/评论/RSS/Atom/sitemap
- Crazydb 主题（忠实还原线上 crazydb.com）
- 墨刊主题（杂志读物风格）

### F：后台
- 文章/分类/评论/用户/导航/标签/日志/配置
- 前后端分离（React+AntD Pro SPA）

### G：验证
- phpunit 全绿
- psalm exit 0
- 全链路冒烟

## 单元测试

Yii2 版本无单元测试。Yii3 版本新增 phpunit 12 测试套件（自写引导，不依赖 yiisoft/yii-testing），代码位于 `tests/Unit/`。

### 测试文件

| 文件 | 覆盖内容 |
|---|---|
| UserActiveRecordTest | 用户 AR 读写、状态常量、密码验证 |
| UserAuthTest | 登录/注册/登出/记住我/黑名单/用户编辑 |
| CommentServiceTest | 评论发布全流程（审核/防冒用/验证码/通知） |
| MarkdownRendererTest | Markdown 渲染/缓存失效/format 分派 |
| RoutesCompatibilityTest | 29 条 URL 兼容性基线 |
| FeedStructureTest | RSS/Atom XML 结构与内容 |
| AdminGuardTest | 后台权限守卫鉴权矩阵 |
| ThemeFactoryTest | 主题切换/白名单校验 |
| CaptchaServiceTest | 验证码生成/校验/一次性消费 |
| NoticeServiceTest | 邮件通知发送/静默跳过 |
| ContainerSmokeTest | DI 容器真实解析关键服务 |
| FrontendQueriesTest | 首页/详情页查询/分页/侧边栏 |
| StaticAssetsTest | 静态资源文件存在性 |
| AllModelsActiveRecordTest | 全模型 AR 基础读写 |
| ModelCacheTest | 缓存版本化 key/失效 |
| CommonComponentsTest | XUtils/CMSUtils 公共方法 |
| EnvironmentTest | 环境变量加载 |

### 运行

```bash
DB_HOST=127.0.0.1 DB_PORT=3306 DB_PASSWORD=mysql.password REDIS_PASSWORD=redis.password ./vendor/bin/phpunit
```

## 关键踩坑

| 项 | 事实 |
|---|---|
| save() | 返回 void（非 bool），失败抛异常 |
| save() 更新 | 仅 dirty 列，update_time 须显式 touch |
| 缓存依赖 | DbDependency → getOrSet(key, fn, ttl, CallbackDependency) |
| 身份 getId | 返回 ?string（非 int） |
| Flash | 独立类，set() 写入 / get() 标记删除 / 下请求移除 |
| 验证码包 | yiisoft/captcha 不存在，GD 自实现 |
| 邮件断言 | __toString() 为空，用 StubMailer::getMessages() |
| commonmark 节点 | 2.x 在 Extension\CommonMark\Node\Block\ 命名空间 |
| HTMLPurifier | 默认 Forms=false 剥 checkbox，需显式开启 |
| PSR-16 key | 禁止 `:` 等字符 |
| 表前缀 | tableName() 返回 `{{%xxx}}`，Connection 必须 setTablePrefix，否则查询无前缀表名 |

## 文件结构

```
src/
├── Console/          # 控制台命令（init/migrate, init/env, init/admin, init/check）
├── Common/           # 公共组件（XUtils, CMSUtils）
├── User/             # 用户体系（AR, Repository, AuthService, RegisterService）
├── Post/             # 文章 + MarkdownRenderer
├── Category/         # 分类
├── Comment/          # 评论
├── Nav/              # 导航
├── Tag/              # 标签
├── Log/              # 日志
├── Mail/             # 邮件通知
├── Captcha/          # 验证码
└── Web/              # 前台页面
    ├── HomePage/
    ├── PostShow/
    ├── CategoryShow/
    ├── TagShow/
    └── Shared/

admin-web/            # 后台前端（React + AntD Pro）
public/admin/         # 构建产物（nginx 托管）
themes/
├── crazydb/          # Crazydb 主题
└── magazine/         # 墨刊主题

config/
├── common/           # DI / 参数 / 路由
├── console/          # 控制台配置
└── web/              # Web 配置

deploy/
├── schema.sql        # 完整建表（10 张表，带 blog_ 前缀）
├── seed.sql          # 种子数据
└── upgrade-yii3.sql  # Yii2→3 增量 SQL（幂等，带 blog_ 前缀）

docker-compose-deploy.yml   # 生产 Docker
docker-compose-dev.yml      # 开发 Docker
```

## 运行命令

完整部署步骤见 [deploy.md](./deploy.md)（含 `.env` 配置说明）。常用命令（宿主机需 PHP 环境，或在容器内执行）：

```bash
# 测试
DB_HOST=127.0.0.1 DB_PORT=3306 DB_PASSWORD=mysql.password REDIS_PASSWORD=redis.password ./vendor/bin/phpunit

# 数据库
./yii init/env          # 交互式检查/填写 .env
./yii init/admin        # 管理员初始化（用户表为空时）
./yii init/check        # 校验库结构是否符合预期
./yii init/migrate      # 增量升级（幂等，不建基础表）
./yii init/migrate --dry-run  # 只检查不执行
# 全新安装：mysql ... < deploy/schema.sql && mysql ... < deploy/seed.sql
```
