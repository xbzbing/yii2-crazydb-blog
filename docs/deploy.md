# 开发环境部署与线上升级

## 环境配置（.env）

`.env` 从 `.env.example` 复制而来，**不入版本库**（.gitignore），存放数据库/Redis 密码等敏感配置。

```bash
cp .env.example .env
vim .env   # 按需修改
```

| 配置项 | 必填 | 说明 |
|---|---|---|
| `APP_ENV` / `APP_DEBUG` | 是 | 生产填 `prod` / `false` |
| `DB_HOST` / `DB_PORT` / `DB_NAME` | 是 | 数据库连接（生产为 mysql 容器名） |
| `DB_USER` / `DB_PASSWORD` | 是 | 数据库账号密码 |
| `DB_TABLE_PREFIX` | 否 | 表前缀，默认 `blog_`（Yii2 遗留命名）；置空则无前缀 |
| `REDIS_HOST` / `REDIS_PORT` | 是 | Redis 连接 |
| `REDIS_PASSWORD` | 是 | Redis 密码 |
| `NGINX_HTTP_PORT` / `NGINX_HTTPS_PORT` | 否 | 对外端口，默认 80/443 |
| `MAILER_DSN` | 否 | SMTP DSN（symfony/mailer 格式），默认 `null://` 不发送 |
| `COOKIE_SECURE` | 否 | HTTPS 部署设为 `true`（记住我 cookie Secure 标记） |
| `CAPTCHA_DEBUG` | 否 | 仅开发环境设 `true`（验证码直通） |

> 说明：生产部署由 `docker compose -f docker-compose-deploy.yml` 通过 `env_file: .env`
> 注入容器；开发环境容器内已内置默认 DB/Redis 环境变量（见 docker-compose-dev.yml），
> `.env` 仅对宿主机直接执行的命令（`./yii init/*`、`phpunit`）生效。
> `./yii init/env` 可交互式检查/填写 `.env`。

## 开发环境部署（dev）

### 前置条件

- Docker + Docker Compose
- Git

### 步骤

```bash
# 1. 克隆代码
git clone <repo-url> && cd yii2-crazydb-blog
git checkout yii3

# 2. 启动服务（mysql/redis/nginx/php）
docker compose -f docker-compose-dev.yml up -d

# 3. 安装依赖（容器内含 composer）
docker compose -f docker-compose-dev.yml exec php composer install

# 4. 初始化数据库
#    dev 容器已内置 DB_HOST=mysql、DB_PASSWORD=mysql.password 等环境变量，
#    表前缀默认 blog_（见 config/common/params.php，env DB_TABLE_PREFIX 可覆盖）
docker compose -f docker-compose-dev.yml exec php php yii init/env
docker compose -f docker-compose-dev.yml exec php php yii init/admin

#   全新数据库：先导入完整 schema + 种子
cat deploy/schema.sql | docker compose -f docker-compose-dev.yml exec -T mysql mysql -uroot -pmysql.password crazydb
cat deploy/seed.sql   | docker compose -f docker-compose-dev.yml exec -T mysql mysql -uroot -pmysql.password crazydb
#   已有数据库（Yii2 或旧 Yii3）：执行增量升级（幂等）
docker compose -f docker-compose-dev.yml exec php php yii init/migrate

# 5. 验证
docker compose -f docker-compose-dev.yml exec php ./vendor/bin/phpunit
```

### 服务访问

| 服务 | 地址 |
|---|---|
| 前台 | http://localhost |
| 后台 | http://localhost/admin |
| MySQL | 127.0.0.1:3306（root/mysql.password） |
| Redis | 127.0.0.1:6379 |

## 线上升级

### 前置条件

- 已部署旧版本（Yii2）运行中
- 备份数据库

### 步骤

```bash
# 1. 备份数据库（在 mysql 容器内执行，导出到宿主）
docker compose -f docker-compose-deploy.yml exec mysql sh -c \
  'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" crazydb' > backup_$(date +%Y%m%d).sql

# 2. 拉取新代码
git pull origin yii3

# 3. 启动新版本
cp .env.example .env
vim .env  # 填写数据库密码、DB_TABLE_PREFIX 等配置
docker compose -f docker-compose-deploy.yml up -d

# 4. 执行数据库升级（幂等，可重复执行）
docker compose -f docker-compose-deploy.yml exec php php yii init/migrate

# 5. 验证
curl -I http://localhost  # 应返回 200
```

### 回滚

```bash
# 切回旧代码分支（master 为 Yii2 版本）
git checkout master
docker compose -f docker-compose-deploy.yml up -d

# 恢复数据库（如需），同样在容器内执行
docker compose -f docker-compose-deploy.yml exec -T mysql sh -c \
  'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" crazydb' < backup_YYYYMMDD.sql
```

### 注意事项

- `init/migrate` 是**增量升级**：表已存在则跳过，可重复执行；**但不会创建基础表**，
  全新部署须先导入 `deploy/schema.sql` + `deploy/seed.sql`
- `init/check` 可检测库结构是否符合预期，升级前先运行确认
- 表前缀默认 `blog_`（Yii2 遗留命名），通过 env `DB_TABLE_PREFIX` 可覆盖；`DB_TABLE_PREFIX=` 置空则无前缀
- 老文章（HTML 格式）不受影响，无需转换；新文章支持 Markdown
- 静态资源（`web/upload/` 上传文件、`web/static/avatar/` 头像）为 gitignored 运行时数据，
  上线时需手动拷贝到新部署，保持路径不变