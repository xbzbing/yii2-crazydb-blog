-- ============================================================
-- yii2-crazydb-blog schema (reverse-engineered from models)
-- Generated: 2026-08-03
-- NOTE: migrations/ was never committed; structure inferred from
-- ActiveRecord models. Field types are best-effort estimates.
-- ============================================================

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- 文章表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_post` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分类ID',
  `author_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '作者ID',
  `author_name` VARCHAR(80) NOT NULL DEFAULT '' COMMENT '作者昵称',
  `type` VARCHAR(20) NOT NULL DEFAULT 'post' COMMENT '文章类型: post/album/product',
  `title` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '标题',
  `alias` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '访问别名',
  `excerpt` TEXT COMMENT '简介(HTML)',
  `content` MEDIUMTEXT COMMENT '内容(markdown 原文或 HTML)',
  `format` VARCHAR(10) NOT NULL DEFAULT 'html' COMMENT '内容格式: html/markdown',
  `cover` VARCHAR(255) DEFAULT NULL COMMENT '封面图片地址',
  `password` VARCHAR(32) DEFAULT NULL COMMENT '访问密码',
  `status` VARCHAR(20) NOT NULL DEFAULT 'published' COMMENT '状态: published/draft/deleted/hidden',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `post_time` INT UNSIGNED DEFAULT NULL COMMENT '发布时间(草稿为NULL)',
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `tags` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '标签(逗号分隔)',
  `comment_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_top` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否置顶',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_alias` (`alias`),
  KEY `idx_cid` (`cid`),
  KEY `idx_status_post_time` (`status`, `post_time`),
  KEY `idx_author` (`author_id`),
  KEY `idx_update_time` (`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章';

-- ------------------------------------------------------------
-- 分类表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_category` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '分类名称',
  `alias` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'URL别名',
  `desc` TEXT COMMENT '分类介绍',
  `pid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父分类',
  `display` VARCHAR(20) NOT NULL DEFAULT 'list' COMMENT '显示模式: list/page',
  `sort_order` INT NOT NULL DEFAULT 0 COMMENT '显示顺序权重',
  `keywords` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'SEO关键字',
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_alias` (`alias`),
  KEY `idx_pid_sort` (`pid`, `sort_order`),
  KEY `idx_update_time` (`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分类';

-- ------------------------------------------------------------
-- 评论表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_comment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '文章ID',
  `uid` INT UNSIGNED DEFAULT NULL COMMENT '用户ID',
  `nickname` VARCHAR(80) NOT NULL DEFAULT '' COMMENT '评论用户名称',
  `email` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '电子邮箱',
  `reply_to` INT UNSIGNED DEFAULT NULL COMMENT '回复目标评论ID',
  `url` VARCHAR(255) DEFAULT NULL COMMENT '评论者URL',
  `ip` VARCHAR(46) NOT NULL DEFAULT '' COMMENT '用户IP',
  `user_agent` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'UA',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `content` TEXT COMMENT '评论内容',
  `status` VARCHAR(20) NOT NULL DEFAULT 'unapproved' COMMENT '状态: unapproved/approved/spam',
  PRIMARY KEY (`id`),
  KEY `idx_pid` (`pid`),
  KEY `idx_status_create` (`status`, `create_time`),
  KEY `idx_update_time` (`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论';

-- ------------------------------------------------------------
-- 用户表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nickname` VARCHAR(80) NOT NULL DEFAULT '' COMMENT '昵称',
  `username` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '用户名',
  `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像地址',
  `password` VARCHAR(60) NOT NULL DEFAULT '' COMMENT '密码hash',
  `email` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `website` VARCHAR(100) DEFAULT NULL COMMENT '个人网站',
  `role` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '角色: 1会员/8编辑/16管理员',
  `register_ip` VARCHAR(46) NOT NULL DEFAULT '',
  `register_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `active_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后活跃时间',
  `auth_key` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '记住我cookie key',
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态位: 1正常/2未激活/4封禁/8删除',
  `otp_secret` VARCHAR(255) DEFAULT NULL COMMENT 'TOTP 密钥（Base32，NULL=未启用）',
  `otp_enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'OTP 二次验证（0/1）',
  `info` TEXT COMMENT '个人简介',
  `ext` TEXT COMMENT '扩展信息',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_nickname` (`nickname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户';

-- ------------------------------------------------------------
-- 标签表 (文章-标签关系)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_tag` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '标签名',
  `pid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '文章ID',
  `cid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分类ID',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pid` (`pid`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='标签';

-- ------------------------------------------------------------
-- 导航表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_nav` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父菜单',
  `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '名字',
  `url` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '网址',
  `route` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否为系统路由',
  `sort_order` INT NOT NULL DEFAULT 0 COMMENT '显示顺序权重',
  `extra` VARCHAR(255) DEFAULT NULL COMMENT '附加属性',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pid_sort` (`pid`, `sort_order`),
  KEY `idx_update_time` (`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='导航';

-- ------------------------------------------------------------
-- 站点配置表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_option` (
  `type` VARCHAR(20) NOT NULL COMMENT '配置类型: sys/seo',
  `name` VARCHAR(50) NOT NULL COMMENT '配置名称',
  `value` TEXT COMMENT '值',
  `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '描述',
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`type`, `name`),
  KEY `idx_update_time` (`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='站点配置';

-- ------------------------------------------------------------
-- 操作日志表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `type` VARCHAR(20) NOT NULL DEFAULT 'default' COMMENT '操作类型',
  `action` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '执行操作',
  `result` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '操作结果: success/failed',
  `key` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Key',
  `detail` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '详细信息',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `ip` VARCHAR(46) NOT NULL DEFAULT '',
  `user_agent` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志';

-- ------------------------------------------------------------
-- 按日访问统计表（前台 PV/UV 聚合，由 visit/sync 定时任务落库）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_visit_daily` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATE NOT NULL COMMENT '日期',
  `pv` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '访问次数(PV)',
  `uv` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '独立IP数(UV)',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='按日访问统计';

-- ------------------------------------------------------------
-- 自定义配置表（个性化设置：ThemeDIY/IndexCarousel 等分类）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_custom_config` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '分类',
  `key` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '配置键（分类内唯一）',
  `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '名称',
  `value` TEXT NULL COMMENT '配置值',
  `data_type` VARCHAR(20) NOT NULL DEFAULT 'text' COMMENT '值类型: text/markdown/html/image/url/base64/hex',
  `priority` INT NOT NULL DEFAULT 0 COMMENT '优先级（越大越靠前）',
  `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '描述',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_category_key` (`category`, `key`),
  KEY `idx_priority` (`category`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='自定义配置';
