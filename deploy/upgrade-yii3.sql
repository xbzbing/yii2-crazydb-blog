-- ============================================================
-- Yii2 → Yii3 完整增量升级 SQL
-- 用法：mysql -h127.0.0.1 -uroot -p crazydb < deploy/upgrade-yii3.sql
-- 幂等可重复执行：补齐 NULL 数据、对齐 schema.sql 结构（类型/主键/引擎/
-- collation/索引），并补充新表与种子数据。完成后用 ./yii init/check 校验。
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- 0. 辅助存储过程（幂等 ALTER 封装）
-- ============================================================

-- 幂等 MODIFY 列：期望类型/可空/默认值不一致时才执行
DROP PROCEDURE IF EXISTS `upg_modify_column`;
DELIMITER $$
CREATE PROCEDURE `upg_modify_column`(
    IN tname VARCHAR(64), IN cname VARCHAR(64),
    IN cdef VARCHAR(512), IN exp_type VARCHAR(64),
    IN exp_null VARCHAR(3), IN exp_default VARCHAR(64)
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tname AND COLUMN_NAME = cname
          AND (COLUMN_TYPE <> exp_type OR IS_NULLABLE <> exp_null
               OR (COLUMN_DEFAULT <=> exp_default) = 0)
    ) THEN
        SET @s = CONCAT('ALTER TABLE `', tname, '` MODIFY COLUMN ', cdef);
        PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
    END IF;
END$$
DELIMITER ;

-- 幂等 DROP 列
DROP PROCEDURE IF EXISTS `upg_drop_column`;
DELIMITER $$
CREATE PROCEDURE `upg_drop_column`(IN tname VARCHAR(64), IN cname VARCHAR(64))
BEGIN
    IF EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tname AND COLUMN_NAME = cname
    ) THEN
        SET @s = CONCAT('ALTER TABLE `', tname, '` DROP COLUMN `', cname, '`');
        PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
    END IF;
END$$
DELIMITER ;

-- 幂等建索引（unique=1 建唯一索引）
DROP PROCEDURE IF EXISTS `upg_index`;
DELIMITER $$
CREATE PROCEDURE `upg_index`(IN tname VARCHAR(64), IN idx VARCHAR(64), IN cols VARCHAR(255), IN unique_ INT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tname AND INDEX_NAME = idx
    ) THEN
        SET @s = CONCAT(IF(unique_ = 1, 'CREATE UNIQUE INDEX `', 'CREATE INDEX `'),
                        idx, '` ON `', tname, '` (', cols, ')');
        PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
    END IF;
END$$
DELIMITER ;

-- 幂等换引擎
DROP PROCEDURE IF EXISTS `upg_engine`;
DELIMITER $$
CREATE PROCEDURE `upg_engine`(IN tname VARCHAR(64), IN eng VARCHAR(32))
BEGIN
    IF (SELECT ENGINE FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tname) <> eng THEN
        SET @s = CONCAT('ALTER TABLE `', tname, '` ENGINE = ', eng);
        PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
    END IF;
END$$
DELIMITER ;

-- 幂等整表 collation 转换
DROP PROCEDURE IF EXISTS `upg_collation`;
DELIMITER $$
CREATE PROCEDURE `upg_collation`(IN tname VARCHAR(64), IN coll VARCHAR(64))
BEGIN
    IF (SELECT TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tname) <> coll THEN
        SET @s = CONCAT('ALTER TABLE `', tname,
                        '` CONVERT TO CHARACTER SET utf8mb4 COLLATE ', coll);
        PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
    END IF;
END$$
DELIMITER ;

-- blog_option 主键对齐为 (type, name)
DROP PROCEDURE IF EXISTS `upg_option_pk`;
DELIMITER $$
CREATE PROCEDURE `upg_option_pk`()
BEGIN
    IF (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'blog_option'
          AND INDEX_NAME = 'PRIMARY') <> 2
       OR NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
                      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'blog_option'
                        AND INDEX_NAME = 'PRIMARY' AND COLUMN_NAME = 'type') THEN
        ALTER TABLE `blog_option` DROP PRIMARY KEY, ADD PRIMARY KEY (`type`, `name`);
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- 1. 数据修补：NULL → 类型默认值（对齐 Yii3 非空属性，防 TypeError）
--    （全部 UPDATE 天然幂等）
-- ============================================================

-- blog_post
UPDATE `blog_post` SET `update_time` = 0  WHERE `update_time` IS NULL;
UPDATE `blog_post` SET `author_name` = '' WHERE `author_name` IS NULL;
UPDATE `blog_post` SET `alias` = ''       WHERE `alias` IS NULL;
UPDATE `blog_post` SET `tags` = ''        WHERE `tags` IS NULL;
UPDATE `blog_post` SET `cover` = ''       WHERE `cover` IS NULL;

-- blog_category
UPDATE `blog_category` SET `alias` = ''       WHERE `alias` IS NULL;
UPDATE `blog_category` SET `keywords` = ''    WHERE `keywords` IS NULL;
UPDATE `blog_category` SET `update_time` = 0  WHERE `update_time` IS NULL;

-- blog_comment（uid/reply_to 旧值 0 保留即可：模型 ?int 接受 0，无需转 NULL）
UPDATE `blog_comment` SET `update_time` = 0  WHERE `update_time` IS NULL;
UPDATE `blog_comment` SET `user_agent` = ''  WHERE `user_agent` IS NULL;

-- blog_user
UPDATE `blog_user` SET `auth_key` = ''    WHERE `auth_key` IS NULL;
UPDATE `blog_user` SET `active_time` = 0  WHERE `active_time` IS NULL;
UPDATE `blog_user` SET `update_time` = 0  WHERE `update_time` IS NULL;

-- blog_nav
UPDATE `blog_nav` SET `create_time` = 0  WHERE `create_time` IS NULL;
UPDATE `blog_nav` SET `update_time` = 0  WHERE `update_time` IS NULL;
UPDATE `blog_nav` SET `sort_order` = 0   WHERE `sort_order` IS NULL;

-- blog_log
UPDATE `blog_log` SET `type` = 'default' WHERE `type` IS NULL;
UPDATE `blog_log` SET `action` = ''      WHERE `action` IS NULL;
UPDATE `blog_log` SET `result` = ''      WHERE `result` IS NULL;
UPDATE `blog_log` SET `key` = ''         WHERE `key` IS NULL;
UPDATE `blog_log` SET `detail` = ''      WHERE `detail` IS NULL;
UPDATE `blog_log` SET `user_agent` = ''  WHERE `user_agent` IS NULL;

-- blog_option
UPDATE `blog_option` SET `type` = 'sys'  WHERE `type` IS NULL OR `type` = '';

-- 站点状态（运行中/维护中）与维护文案默认值（幂等：已有则跳过）
INSERT IGNORE INTO `blog_option` (`type`, `name`, `value`, `update_time`) VALUES
('sys', 'site_status', 'running', UNIX_TIMESTAMP()),
('sys', 'maintenance_message', '系统升级中', UNIX_TIMESTAMP());

-- ============================================================
-- 2. 结构对齐：blog_post
-- ============================================================
CALL `upg_modify_column`('blog_post', 'id',           '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT', 'int unsigned', 'NO', NULL);
CALL `upg_modify_column`('blog_post', 'cid',          '`cid` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_post', 'author_id',    '`author_id` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_post', 'author_name',  '`author_name` VARCHAR(80) NOT NULL DEFAULT \'\'', 'varchar(80)', 'NO', '');
CALL `upg_modify_column`('blog_post', 'is_top',       '`is_top` TINYINT(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0');
CALL `upg_modify_column`('blog_post', 'alias',        '`alias` VARCHAR(255) NOT NULL DEFAULT \'\'', 'varchar(255)', 'NO', '');
CALL `upg_modify_column`('blog_post', 'cover',        '`cover` VARCHAR(255) DEFAULT NULL', 'varchar(255)', 'YES', NULL);
CALL `upg_modify_column`('blog_post', 'status',       '`status` VARCHAR(20) NOT NULL DEFAULT \'published\'', 'varchar(20)', 'NO', 'published');
CALL `upg_modify_column`('blog_post', 'create_time',  '`create_time` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_post', 'post_time',    '`post_time` INT UNSIGNED DEFAULT NULL', 'int unsigned', 'YES', NULL);
CALL `upg_modify_column`('blog_post', 'update_time',  '`update_time` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_post', 'tags',         '`tags` VARCHAR(255) NOT NULL DEFAULT \'\'', 'varchar(255)', 'NO', '');
CALL `upg_modify_column`('blog_post', 'comment_count','`comment_count` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_post', 'view_count',   '`view_count` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_post', 'format',       '`format` VARCHAR(10) NOT NULL DEFAULT \'html\'', 'varchar(10)', 'NO', 'html');
-- 旧库遗留、代码未使用的列，删除对齐 schema
CALL `upg_drop_column`('blog_post', 'ext_info');

-- ============================================================
-- 3. 结构对齐：blog_category
-- ============================================================
CALL `upg_modify_column`('blog_category', 'alias',       '`alias` VARCHAR(100) NOT NULL DEFAULT \'\'', 'varchar(100)', 'NO', '');
CALL `upg_modify_column`('blog_category', 'keywords',    '`keywords` VARCHAR(255) NOT NULL DEFAULT \'\'', 'varchar(255)', 'NO', '');
CALL `upg_modify_column`('blog_category', 'update_time', '`update_time` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_category', 'display',     '`display` VARCHAR(20) NOT NULL DEFAULT \'list\'', 'varchar(20)', 'NO', 'list');
CALL `upg_modify_column`('blog_category', 'sort_order',  '`sort_order` INT NOT NULL DEFAULT 0', 'int', 'NO', '0');
CALL `upg_modify_column`('blog_category', 'desc',        '`desc` TEXT DEFAULT NULL', 'text', 'YES', NULL);

-- ============================================================
-- 4. 结构对齐：blog_comment
-- ============================================================
CALL `upg_modify_column`('blog_comment', 'id',          '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT', 'int unsigned', 'NO', NULL);
CALL `upg_modify_column`('blog_comment', 'pid',         '`pid` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_comment', 'uid',         '`uid` INT UNSIGNED DEFAULT NULL', 'int unsigned', 'YES', NULL);
CALL `upg_modify_column`('blog_comment', 'reply_to',    '`reply_to` INT UNSIGNED DEFAULT NULL', 'int unsigned', 'YES', NULL);
CALL `upg_modify_column`('blog_comment', 'url',         '`url` VARCHAR(255) DEFAULT NULL', 'varchar(255)', 'YES', NULL);
CALL `upg_modify_column`('blog_comment', 'user_agent',  '`user_agent` VARCHAR(255) NOT NULL DEFAULT \'\'', 'varchar(255)', 'NO', '');
CALL `upg_modify_column`('blog_comment', 'create_time', '`create_time` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_comment', 'update_time', '`update_time` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_comment', 'content',     '`content` TEXT DEFAULT NULL', 'text', 'YES', NULL);
CALL `upg_modify_column`('blog_comment', 'status',      '`status` VARCHAR(20) NOT NULL DEFAULT \'unapproved\'', 'varchar(20)', 'NO', 'unapproved');
CALL `upg_modify_column`('blog_comment', 'ip',          '`ip` VARCHAR(46) NOT NULL DEFAULT \'\'', 'varchar(46)', 'NO', '');

-- ============================================================
-- 5. 结构对齐：blog_user
-- ============================================================
CALL `upg_modify_column`('blog_user', 'id',           '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT', 'int unsigned', 'NO', NULL);
CALL `upg_modify_column`('blog_user', 'username',     '`username` VARCHAR(20) NOT NULL DEFAULT \'\'', 'varchar(20)', 'NO', '');
CALL `upg_modify_column`('blog_user', 'password',     '`password` VARCHAR(60) NOT NULL DEFAULT \'\'', 'varchar(60)', 'NO', '');
CALL `upg_modify_column`('blog_user', 'avatar',       '`avatar` VARCHAR(255) DEFAULT NULL', 'varchar(255)', 'YES', NULL);
CALL `upg_modify_column`('blog_user', 'website',      '`website` VARCHAR(100) DEFAULT NULL', 'varchar(100)', 'YES', NULL);
CALL `upg_modify_column`('blog_user', 'role',         '`role` TINYINT UNSIGNED NOT NULL DEFAULT 1', 'tinyint unsigned', 'NO', '1');
CALL `upg_modify_column`('blog_user', 'auth_key',     '`auth_key` VARCHAR(64) NOT NULL DEFAULT \'\'', 'varchar(64)', 'NO', '');
CALL `upg_modify_column`('blog_user', 'status',       '`status` TINYINT UNSIGNED NOT NULL DEFAULT 1', 'tinyint unsigned', 'NO', '1');
CALL `upg_modify_column`('blog_user', 'info',         '`info` TEXT DEFAULT NULL', 'text', 'YES', NULL);
CALL `upg_modify_column`('blog_user', 'ext',          '`ext` TEXT DEFAULT NULL', 'text', 'YES', NULL);
CALL `upg_modify_column`('blog_user', 'register_ip',  '`register_ip` VARCHAR(46) NOT NULL DEFAULT \'\'', 'varchar(46)', 'NO', '');
CALL `upg_modify_column`('blog_user', 'register_time','`register_time` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_user', 'active_time',  '`active_time` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_user', 'update_time',  '`update_time` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');

-- ============================================================
-- 6. 结构对齐：blog_tag
-- ============================================================
CALL `upg_modify_column`('blog_tag', 'id',          '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT', 'int unsigned', 'NO', NULL);
CALL `upg_modify_column`('blog_tag', 'pid',         '`pid` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_tag', 'cid',         '`cid` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_tag', 'create_time', '`create_time` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');

-- ============================================================
-- 7. 结构对齐：blog_nav
-- ============================================================
CALL `upg_modify_column`('blog_nav', 'pid',         '`pid` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_nav', 'url',         '`url` VARCHAR(255) NOT NULL DEFAULT \'\'', 'varchar(255)', 'NO', '');
CALL `upg_modify_column`('blog_nav', 'route',       '`route` TINYINT(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0');
CALL `upg_modify_column`('blog_nav', 'sort_order',  '`sort_order` INT NOT NULL DEFAULT 0', 'int', 'NO', '0');
CALL `upg_modify_column`('blog_nav', 'extra',       '`extra` VARCHAR(255) DEFAULT NULL', 'varchar(255)', 'YES', NULL);
CALL `upg_modify_column`('blog_nav', 'create_time', '`create_time` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_nav', 'update_time', '`update_time` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');

-- ============================================================
-- 8. 结构对齐：blog_option
-- ============================================================
CALL `upg_modify_column`('blog_option', 'type',        '`type` VARCHAR(20) NOT NULL', 'varchar(20)', 'NO', NULL);
CALL `upg_modify_column`('blog_option', 'description', '`description` VARCHAR(255) NOT NULL DEFAULT \'\'', 'varchar(255)', 'NO', '');
CALL `upg_modify_column`('blog_option', 'value',       '`value` TEXT DEFAULT NULL', 'text', 'YES', NULL);
CALL `upg_modify_column`('blog_option', 'update_time', '`update_time` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_option_pk`();
CALL `upg_engine`('blog_option', 'InnoDB');

-- ============================================================
-- 9. 结构对齐：blog_log
-- ============================================================
CALL `upg_modify_column`('blog_log', 'id',          '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT', 'int unsigned', 'NO', NULL);
CALL `upg_modify_column`('blog_log', 'uid',         '`uid` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_log', 'type',        '`type` VARCHAR(20) NOT NULL DEFAULT \'default\'', 'varchar(20)', 'NO', 'default');
CALL `upg_modify_column`('blog_log', 'action',      '`action` VARCHAR(100) NOT NULL DEFAULT \'\'', 'varchar(100)', 'NO', '');
CALL `upg_modify_column`('blog_log', 'result',      '`result` VARCHAR(100) NOT NULL DEFAULT \'\'', 'varchar(100)', 'NO', '');
CALL `upg_modify_column`('blog_log', 'key',         '`key` VARCHAR(100) NOT NULL DEFAULT \'\'', 'varchar(100)', 'NO', '');
CALL `upg_modify_column`('blog_log', 'detail',      '`detail` VARCHAR(255) NOT NULL DEFAULT \'\'', 'varchar(255)', 'NO', '');
CALL `upg_modify_column`('blog_log', 'create_time', '`create_time` INT UNSIGNED NOT NULL DEFAULT 0', 'int unsigned', 'NO', '0');
CALL `upg_modify_column`('blog_log', 'ip',          '`ip` VARCHAR(46) NOT NULL DEFAULT \'\'', 'varchar(46)', 'NO', '');
CALL `upg_modify_column`('blog_log', 'user_agent',  '`user_agent` VARCHAR(255) NOT NULL DEFAULT \'\'', 'varchar(255)', 'NO', '');

-- ============================================================
-- 10. 索引补齐（幂等）
-- ============================================================
CALL `upg_index`('blog_post', 'uk_alias', '`alias`', 1);
CALL `upg_index`('blog_post', 'idx_cid', '`cid`', 0);
CALL `upg_index`('blog_post', 'idx_status_post_time', '`status`, `post_time`', 0);
CALL `upg_index`('blog_post', 'idx_author', '`author_id`', 0);
CALL `upg_index`('blog_post', 'idx_update_time', '`update_time`', 0);

CALL `upg_index`('blog_category', 'uk_alias', '`alias`', 1);
CALL `upg_index`('blog_category', 'idx_pid_sort', '`pid`, `sort_order`', 0);
CALL `upg_index`('blog_category', 'idx_update_time', '`update_time`', 0);

CALL `upg_index`('blog_comment', 'idx_pid', '`pid`', 0);
CALL `upg_index`('blog_comment', 'idx_status_create', '`status`, `create_time`', 0);
CALL `upg_index`('blog_comment', 'idx_update_time', '`update_time`', 0);

CALL `upg_index`('blog_option', 'idx_update_time', '`update_time`', 0);

CALL `upg_index`('blog_user', 'uk_username', '`username`', 1);
CALL `upg_index`('blog_user', 'uk_email', '`email`', 1);
CALL `upg_index`('blog_user', 'idx_nickname', '`nickname`', 0);

CALL `upg_index`('blog_tag', 'idx_pid', '`pid`', 0);
CALL `upg_index`('blog_tag', 'idx_name', '`name`', 0);

CALL `upg_index`('blog_nav', 'idx_pid_sort', '`pid`, `sort_order`', 0);
CALL `upg_index`('blog_nav', 'idx_update_time', '`update_time`', 0);

CALL `upg_index`('blog_log', 'idx_uid', '`uid`', 0);
CALL `upg_index`('blog_log', 'idx_create_time', '`create_time`', 0);

-- ============================================================
-- 11. collation 统一为 utf8mb4_unicode_ci（对齐 schema.sql）
--     （blog_custom_config / blog_visit_daily 已是，自动跳过）
-- ============================================================
CALL `upg_collation`('blog_post', 'utf8mb4_unicode_ci');
CALL `upg_collation`('blog_category', 'utf8mb4_unicode_ci');
CALL `upg_collation`('blog_comment', 'utf8mb4_unicode_ci');
CALL `upg_collation`('blog_user', 'utf8mb4_unicode_ci');
CALL `upg_collation`('blog_tag', 'utf8mb4_unicode_ci');
CALL `upg_collation`('blog_nav', 'utf8mb4_unicode_ci');
CALL `upg_collation`('blog_option', 'utf8mb4_unicode_ci');
CALL `upg_collation`('blog_log', 'utf8mb4_unicode_ci');

-- ============================================================
-- 12. 新增表（幂等，保留原 upgrade 内容）
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
-- 13. 种子数据（幂等）
-- ============================================================

-- custom_config：主题「关于我」侧栏内容
INSERT INTO `blog_custom_config` (`category`, `key`, `name`, `value`, `data_type`, `priority`, `description`, `create_time`, `update_time`)
SELECT 'ThemeDIY', 'aboutMe', '关于我',
       '曾经是爱好网络安全的程序猿\n\n后来是爱好编程的安全攻城狮\n\n现在是爱好安全的摸鱼工程师\n\n**联系方式**：xbzbing#gmail.com',
       'markdown', 100, '侧栏「关于我」内容（Markdown 渲染）', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `blog_custom_config` WHERE `category` = 'ThemeDIY' AND `key` = 'aboutMe');

-- option：基础站点配置（已有同名 name 则跳过）
INSERT IGNORE INTO `blog_option` (`type`, `name`, `value`, `update_time`) VALUES
('sys', 'site_name', 'Crazydb-Blog', UNIX_TIMESTAMP()),
('sys', 'admin_email', 'root@crazydb.com', UNIX_TIMESTAMP()),
('sys', 'allow_comment', 'open', UNIX_TIMESTAMP()),
('sys', 'allow_register', 'open', UNIX_TIMESTAMP()),
('sys', 'need_approve', 'close', UNIX_TIMESTAMP()),
('seo', 'seo_title', 'Crazydb-Blog', UNIX_TIMESTAMP()),
('seo', 'seo_keywords', 'blog,crazydb', UNIX_TIMESTAMP()),
('seo', 'seo_description', 'Crazydb-Blog，基于Yii2的博客系统', UNIX_TIMESTAMP());

-- ============================================================
-- 完成提示
-- ============================================================
SELECT 'upgrade-yii3.sql: 完成，库结构已对齐 Yii3 schema' AS result;
