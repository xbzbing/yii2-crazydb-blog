-- ============================================================
-- Yii2 → Yii3 增量升级 SQL
-- 用法：mysql -h127.0.0.1 -uroot -p crazydb < deploy/upgrade-yii3.sql
-- 说明：所有操作幂等（IF NOT EXISTS），可重复执行
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- 1. post 表：新增 format 列（幂等：先检查再添加）
-- ============================================================
SET @dbname = 'crazydb';
SET @tablename = 'blog_post';
SET @columnname = 'format';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname
     AND TABLE_NAME = @tablename
     AND COLUMN_NAME = @columnname
    ) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` VARCHAR(10) NOT NULL DEFAULT \'html\' COMMENT \'内容格式: html/markdown\' AFTER `content`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================================
-- 2. 新增索引（各表 update_time，幂等）
-- ============================================================
-- post
SET @dbname = 'crazydb';
SET @tablename = 'blog_post';
SET @indexname = 'idx_update_time';
SET @indexcols = 'update_time';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname
     AND TABLE_NAME = @tablename
     AND INDEX_NAME = @indexname
    ) > 0,
    'SELECT 1',
    CONCAT('CREATE INDEX `', @indexname, '` ON `', @tablename, '` (`', @indexcols, '`)')
));
PREPARE createIndexIfNotExists FROM @preparedStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- category
SET @tablename = 'blog_category';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname
     AND TABLE_NAME = @tablename
     AND INDEX_NAME = @indexname
    ) > 0,
    'SELECT 1',
    CONCAT('CREATE INDEX `', @indexname, '` ON `', @tablename, '` (`', @indexcols, '`)')
));
PREPARE createIndexIfNotExists FROM @preparedStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- comment
SET @tablename = 'blog_comment';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname
     AND TABLE_NAME = @tablename
     AND INDEX_NAME = @indexname
    ) > 0,
    'SELECT 1',
    CONCAT('CREATE INDEX `', @indexname, '` ON `', @tablename, '` (`', @indexcols, '`)')
));
PREPARE createIndexIfNotExists FROM @preparedStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- option
SET @tablename = 'blog_option';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname
     AND TABLE_NAME = @tablename
     AND INDEX_NAME = @indexname
    ) > 0,
    'SELECT 1',
    CONCAT('CREATE INDEX `', @indexname, '` ON `', @tablename, '` (`', @indexcols, '`)')
));
PREPARE createIndexIfNotExists FROM @preparedStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- ============================================================
-- 3. visit_daily 表（全新）
-- ============================================================
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

-- ============================================================
-- 4. custom_config 表（全新）
-- ============================================================
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

-- ============================================================
-- 5. 初始化 custom_config 种子数据（幂等）
-- ============================================================
INSERT INTO `blog_custom_config` (`category`, `key`, `name`, `value`, `data_type`, `priority`, `description`, `create_time`, `update_time`)
SELECT 'ThemeDIY', 'aboutMe', '关于我',
       '曾经是爱好网络安全的程序猿\n\n后来是爱好编程的安全攻城狮\n\n现在是爱好安全的摸鱼工程师\n\n**联系方式**：xbzbing#gmail.com',
       'markdown', 100, '侧栏「关于我」内容（Markdown 渲染）', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `blog_custom_config` WHERE `category` = 'ThemeDIY' AND `key` = 'aboutMe');

-- ============================================================
-- 6. 初始化 option 种子数据（幂等，已有则跳过）
-- ============================================================
INSERT IGNORE INTO `blog_option` (`type`, `name`, `value`, `update_time`) VALUES
('sys', 'site_name', 'Crazydb-Blog', UNIX_TIMESTAMP()),
('sys', 'admin_email', 'root@crazydb.com', UNIX_TIMESTAMP()),
('sys', 'allow_comment', 'open', UNIX_TIMESTAMP()),
('sys', 'allow_register', 'open', UNIX_TIMESTAMP()),
('sys', 'need_approve', 'close', UNIX_TIMESTAMP()),
('seo', 'seo_title', 'Crazydb-Blog', UNIX_TIMESTAMP()),
('seo', 'seo_keywords', 'blog,crazydb', UNIX_TIMESTAMP()),
('seo', 'seo_description', 'Crazydb-Blog，基于Yii2的博客系统', UNIX_TIMESTAMP());
