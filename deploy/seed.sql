-- ============================================================
-- 最小种子数据（全新环境可复现测试/开发前置条件）
-- 用法：mysql -uroot -p crazydb < deploy/seed.sql
-- 注意：dabing 密码为 admin123（bcrypt），上线前必须修改
-- ============================================================

-- 管理员用户（对齐 tests 依赖的 user id=1）
INSERT INTO `user` (id, username, nickname, email, password, role, status, register_ip, register_time, update_time, active_time, auth_key)
VALUES (1, 'dabing', '管理员', 'dabing@example.com',
        '$2y$12$2Da5LDT5vkjnxSBJ9GbN5.IUkaqo6od00FyLiOEUD6hLX0coVUawO',
        16, 1, '127.0.0.1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'seed-auth-key')
ON DUPLICATE KEY UPDATE id = id;

-- 基础站点配置
INSERT INTO `option` (`type`, `name`, `value`, `update_time`) VALUES
('sys', 'site_name', 'Crazydb-Blog', UNIX_TIMESTAMP()),
('sys', 'admin_email', 'root@crazydb.com', UNIX_TIMESTAMP()),
('sys', 'allow_comment', 'open', UNIX_TIMESTAMP()),
('sys', 'allow_register', 'open', UNIX_TIMESTAMP()),
('sys', 'need_approve', 'close', UNIX_TIMESTAMP()),
('seo', 'seo_title', 'Crazydb-Blog', UNIX_TIMESTAMP()),
('seo', 'seo_keywords', 'blog,crazydb', UNIX_TIMESTAMP()),
('seo', 'seo_description', 'Crazydb-Blog，基于Yii2的博客系统', UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `update_time` = VALUES(`update_time`);
