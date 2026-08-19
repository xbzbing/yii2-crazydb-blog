# 新版本新增功能

> 对比旧版本的增量功能，不含迁移自 Yii2 的既有能力。

## 内容与编辑器

- **Markdown 支持**：Vditor 编辑器（ir 模式）+ league/commonmark 渲染管线，新文章支持 Markdown 格式，老 HTML 文章保持兼容
- **TOC 自动生成**：Markdown 文章详情页自动生成折叠目录（h2/h3 锚点）
- **代码高亮**：fenced code block → SyntaxHighlighter 兼容输出，保留老文章高亮效果
- **文章加锁**：支持密码保护文章，访问时需输入密码验证

## 主题

- **Crazydb 主题**：忠实还原线上 crazydb.com，Bootstrap 5 适配 Bootstrap 3 视觉
- **墨刊主题**：杂志读物风格（hero 封面 + 栏目分区 + 网格卡片 + 窄栏阅读）
- **主题热切换**：后台配置下拉切换主题（option 表 theme 字段）

## 后台

- **前后端分离**：React + AntD Pro SPA（admin-web/），HashRouter，PHP 只提供 JSON API
- **仪表盘**：访问趋势折线图（@ant-design/plots）+ 更新提示
- **访问统计**：Redis HLL/INCR 实时统计 + 定时同步 MySQL visit_daily；访问按 UA 细分**爬虫 / 脚本 / 正常**三类（关键词默认值见下，可在后台「基本设置」配置）
- **爬虫/脚本访问关键词**：爬虫默认 `spider,bingbot,bot.html`；脚本默认 `python-,curl,wget,axios,java-http-client,java/,headless`（英文逗号分隔，存 option：`visit_bot_keywords` / `visit_script_keywords`）
- **个性化设置**：自定义配置管理（aboutMe/轮播图片等），主题可从库读取渲染
- **用户管理**：支持编辑昵称/角色（会员/编辑/管理员），站长账号（admin/root）受保护不可操作
- **注册黑名单**：用户名/昵称禁用 admin/root/管理员/站长等保留名，防止冒充

## 部署与运维

- **Docker 生产配置**：docker-compose-deploy.yml，所有配置走 .env
- **数据库迁移命令**：init/migrate（幂等结构升级）、init/env（环境配置）、init/admin（管理员初始化）、init/check（结构校验）
- **Redis 缓存隔离**：博客缓存按前缀删除，不再 flushdb 影响其他数据
