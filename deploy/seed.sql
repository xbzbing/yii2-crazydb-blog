-- ============================================================
-- 最小种子数据（全新环境可复现测试/开发前置条件）
-- 用法：mysql -uroot -p crazydb < deploy/seed.sql
-- 注意：管理员用户请通过 ./yii init/admin 命令创建
-- ============================================================

-- 基础站点配置
INSERT INTO `blog_option` (`type`, `name`, `value`, `update_time`) VALUES
('sys', 'site_name', 'Crazydb-Blog', UNIX_TIMESTAMP()),
('sys', 'admin_email', 'root@crazydb.com', UNIX_TIMESTAMP()),
('sys', 'allow_comment', 'open', UNIX_TIMESTAMP()),
('sys', 'allow_register', 'open', UNIX_TIMESTAMP()),
('sys', 'need_approve', 'close', UNIX_TIMESTAMP()),
('sys', 'visit_bot_keywords', 'spider,bingbot,bot.html', UNIX_TIMESTAMP()),
('sys', 'visit_script_keywords', 'python-,curl,wget,axios,java-http-client,java/,headless', UNIX_TIMESTAMP()),
('seo', 'seo_title', 'Crazydb-Blog', UNIX_TIMESTAMP()),
('seo', 'seo_keywords', 'blog,crazydb', UNIX_TIMESTAMP()),
('seo', 'seo_description', 'Crazydb-Blog，基于Yii2的博客系统', UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `update_time` = VALUES(`update_time`);

-- CrazyDB 主题「关于我」侧栏内容（原写死在主题模板中，现提取到配置）
INSERT INTO `blog_custom_config` (`category`, `key`, `name`, `value`, `data_type`, `priority`, `description`, `create_time`, `update_time`)
SELECT 'ThemeDIY', 'aboutMe', '关于我',
       '曾经是爱好网络安全的程序猿\n\n后来是爱好编程的安全攻城狮\n\n现在是爱好安全的摸鱼工程师\n\n**联系方式**：xbzbing#gmail.com',
       'markdown', 100, '侧栏「关于我」内容（Markdown 渲染）', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `blog_custom_config` WHERE `category` = 'ThemeDIY' AND `key` = 'aboutMe');
