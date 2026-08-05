<?php

declare(strict_types=1);

namespace App\Console;

use PDO;
use PDOException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

use function implode;
use function sprintf;
use function strpos;

/**
 * 数据库增量升级命令：Yii2 → Yii3 结构迁移（幂等）。
 *
 * 用法：
 *   ./yii init/migrate            # 执行升级
 *   ./yii init/migrate --dry-run  # 仅检查，不执行
 */
#[AsCommand(
    name: 'init/migrate',
    description: '数据库增量升级（Yii2→Yii3，幂等，可重复执行）',
)]
final class InitMigrateCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, '仅检查，不执行变更');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = $input->getOption('dry-run');

        $output->writeln('');
        $output->writeln('<info>🔄 数据库增量升级' . ($dryRun ? '（dry-run 模式）' : '') . '</info>');
        $output->writeln('');

        // 1. 连接数据库
        $pdo = $this->connect($output);
        if ($pdo === null) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // 2. 执行升级步骤
        $applied = 0;
        $skipped = 0;

        // Step 1: post 表新增 format 列
        $result = $this->addColumnIfNotExists($pdo, $output, $dryRun, 'post', 'format', "VARCHAR(10) NOT NULL DEFAULT 'html' COMMENT '内容格式: html/markdown'", 'content');
        $applied += $result['applied'];
        $skipped += $result['skipped'];

        // Step 2: 新增索引
        $indexResult = $this->addIndexIfNotExists($pdo, $output, $dryRun, 'post', 'idx_update_time', ['update_time']);
        $applied += $indexResult['applied'];
        $skipped += $indexResult['skipped'];

        $indexResult = $this->addIndexIfNotExists($pdo, $output, $dryRun, 'category', 'idx_update_time', ['update_time']);
        $applied += $indexResult['applied'];
        $skipped += $indexResult['skipped'];

        $indexResult = $this->addIndexIfNotExists($pdo, $output, $dryRun, 'comment', 'idx_update_time', ['update_time']);
        $applied += $indexResult['applied'];
        $skipped += $indexResult['skipped'];

        $indexResult = $this->addIndexIfNotExists($pdo, $output, $dryRun, 'option', 'idx_update_time', ['update_time']);
        $applied += $indexResult['applied'];
        $skipped += $indexResult['skipped'];

        // Step 3: 创建 visit_daily 表
        $tableResult = $this->createTableIfNotExists($pdo, $output, $dryRun, 'visit_daily', [
            '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT',
            '`date` DATE NOT NULL COMMENT \'日期\'',
            '`pv` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'访问次数(PV)\'',
            '`uv` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'独立IP数(UV)\'',
            '`create_time` INT UNSIGNED NOT NULL DEFAULT 0',
            '`update_time` INT UNSIGNED NOT NULL DEFAULT 0',
            'PRIMARY KEY (`id`)',
            'UNIQUE KEY `uk_date` (`date`)',
        ], '按日访问统计');
        $applied += $tableResult['applied'];
        $skipped += $tableResult['skipped'];

        // Step 4: 创建 custom_config 表
        $tableResult = $this->createTableIfNotExists($pdo, $output, $dryRun, 'custom_config', [
            '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT',
            '`category` VARCHAR(100) NOT NULL DEFAULT \'\' COMMENT \'分类\'',
            '`key` VARCHAR(100) NOT NULL DEFAULT \'\' COMMENT \'配置键（分类内唯一）\'',
            '`name` VARCHAR(255) NOT NULL DEFAULT \'\' COMMENT \'名称\'',
            '`value` TEXT NULL COMMENT \'配置值\'',
            '`data_type` VARCHAR(20) NOT NULL DEFAULT \'text\' COMMENT \'值类型: text/markdown/html/image/url/base64/hex\'',
            '`priority` INT NOT NULL DEFAULT 0 COMMENT \'优先级（越大越靠前）\'',
            '`description` VARCHAR(255) NOT NULL DEFAULT \'\' COMMENT \'描述\'',
            '`create_time` INT UNSIGNED NOT NULL DEFAULT 0',
            '`update_time` INT UNSIGNED NOT NULL DEFAULT 0',
            'PRIMARY KEY (`id`)',
            'UNIQUE KEY `uk_category_key` (`category`, `key`)',
            'KEY `idx_priority` (`category`, `priority`)',
        ], '自定义配置');
        $applied += $tableResult['applied'];
        $skipped += $tableResult['skipped'];

        // Step 5: 插入种子数据
        $seedResult = $this->insertSeedData($pdo, $output, $dryRun);
        $applied += $seedResult['applied'];
        $skipped += $seedResult['skipped'];

        // 3. 输出结果
        $output->writeln('');
        $output->writeln('<info>━━━ 升级完成 ━━━</info>');
        $output->writeln('');
        $output->writeln(sprintf('  已应用: %d 项', $applied));
        $output->writeln(sprintf('  已跳过: %d 项（已存在）', $skipped));
        $output->writeln('');

        if ($dryRun && $applied > 0) {
            $output->writeln('<comment>  dry-run 模式，未实际执行。去掉 --dry-run 参数执行变更。</comment>');
            $output->writeln('');
        }

        return ExitCode::OK;
    }

    private function connect(OutputInterface $output): ?PDO
    {
        $host = (string) ($_ENV['DB_HOST'] ?? getenv('DB_HOST')) ?: '127.0.0.1';
        $port = (string) ($_ENV['DB_PORT'] ?? getenv('DB_PORT')) ?: '3306';
        $database = (string) ($_ENV['DB_NAME'] ?? getenv('DB_NAME')) ?: 'crazydb';
        $user = (string) ($_ENV['DB_USER'] ?? getenv('DB_USER')) ?: 'root';
        $password = (string) ($_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD'));

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

        try {
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $output->writeln(sprintf('  <info>✓</info> 已连接 MySQL %s@%s:%s/%s', $user, $host, $port, $database));
            $output->writeln('');
            return $pdo;
        } catch (PDOException $e) {
            $output->writeln(sprintf('<error>❌ 无法连接 MySQL: %s</error>', $e->getMessage()));
            $output->writeln('');
            return null;
        }
    }

    private function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function indexExists(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $stmt->execute([$table, $index]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $stmt->execute([$table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function addColumnIfNotExists(PDO $pdo, OutputInterface $output, bool $dryRun, string $table, string $column, string $definition, string $after): array
    {
        if ($this->columnExists($pdo, $table, $column)) {
            $output->writeln(sprintf('  <comment>✓</comment> %s.%s 列已存在，跳过', $table, $column));
            return ['applied' => 0, 'skipped' => 1];
        }

        $sql = sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s AFTER `%s`', $table, $column, $definition, $after);

        if ($dryRun) {
            $output->writeln(sprintf('  <info>○</info> %s.%s 列不存在，将执行:', $table, $column));
            $output->writeln(sprintf('    <comment>%s</comment>', $sql));
            return ['applied' => 1, 'skipped' => 0];
        }

        try {
            $pdo->exec($sql);
            $output->writeln(sprintf('  <info>✓</info> %s.%s 列已添加', $table, $column));
            return ['applied' => 1, 'skipped' => 0];
        } catch (PDOException $e) {
            $output->writeln(sprintf('  <error>✗</error> %s.%s 列添加失败: %s', $table, $column, $e->getMessage()));
            return ['applied' => 0, 'skipped' => 0];
        }
    }

    private function addIndexIfNotExists(PDO $pdo, OutputInterface $output, bool $dryRun, string $table, string $indexName, array $columns): array
    {
        if ($this->indexExists($pdo, $table, $indexName)) {
            $output->writeln(sprintf('  <comment>✓</comment> %s.%s 索引已存在，跳过', $table, $indexName));
            return ['applied' => 0, 'skipped' => 1];
        }

        $cols = implode('`, `', $columns);
        $sql = sprintf('CREATE INDEX `%s` ON `%s` (`%s`)', $indexName, $table, $cols);

        if ($dryRun) {
            $output->writeln(sprintf('  <info>○</info> %s.%s 索引不存在，将执行:', $table, $indexName));
            $output->writeln(sprintf('    <comment>%s</comment>', $sql));
            return ['applied' => 1, 'skipped' => 0];
        }

        try {
            $pdo->exec($sql);
            $output->writeln(sprintf('  <info>✓</info> %s.%s 索引已创建', $table, $indexName));
            return ['applied' => 1, 'skipped' => 0];
        } catch (PDOException $e) {
            $output->writeln(sprintf('  <error>✗</error> %s.%s 索引创建失败: %s', $table, $indexName, $e->getMessage()));
            return ['applied' => 0, 'skipped' => 0];
        }
    }

    private function createTableIfNotExists(PDO $pdo, OutputInterface $output, bool $dryRun, string $table, array $columns, string $comment): array
    {
        if ($this->tableExists($pdo, $table)) {
            $output->writeln(sprintf('  <comment>✓</comment> %s 表已存在，跳过', $table));
            return ['applied' => 0, 'skipped' => 1];
        }

        $cols = implode(",\n    ", $columns);
        $sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (\n    %s\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='%s'", $table, $cols, $comment);

        if ($dryRun) {
            $output->writeln(sprintf('  <info>○</info> %s 表不存在，将创建:', $table));
            $output->writeln(sprintf('    <comment>%s</comment>', $sql));
            return ['applied' => 1, 'skipped' => 0];
        }

        try {
            $pdo->exec($sql);
            $output->writeln(sprintf('  <info>✓</info> %s 表已创建', $table));
            return ['applied' => 1, 'skipped' => 0];
        } catch (PDOException $e) {
            $output->writeln(sprintf('  <error>✗</error> %s 表创建失败: %s', $table, $e->getMessage()));
            return ['applied' => 0, 'skipped' => 0];
        }
    }

    private function insertSeedData(PDO $pdo, OutputInterface $output, bool $dryRun): array
    {
        $applied = 0;
        $skipped = 0;

        // custom_config: ThemeDIY aboutMe
        if ($this->recordExists($pdo, 'custom_config', 'category', 'ThemeDIY')) {
            $output->writeln('  <comment>✓</comment> custom_config.ThemeDIY 数据已存在，跳过');
            $skipped++;
        } else {
            $sql = "INSERT INTO `custom_config` (`category`, `key`, `name`, `value`, `data_type`, `priority`, `description`, `create_time`, `update_time`)
                    VALUES ('ThemeDIY', 'aboutMe', '关于我', '曾经是爱好网络安全的程序猿\n\n后来是爱好编程的安全攻城狮\n\n现在是爱好安全的摸鱼工程师\n\n**联系方式**：xbzbing#gmail.com', 'markdown', 100, '侧栏「关于我」内容（Markdown 渲染）', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";

            if ($dryRun) {
                $output->writeln('  <info>○</info> custom_config.ThemeDIY 数据不存在，将插入');
                $applied++;
            } else {
                try {
                    $pdo->exec($sql);
                    $output->writeln('  <info>✓</info> custom_config.ThemeDIY 数据已插入');
                    $applied++;
                } catch (PDOException $e) {
                    $output->writeln(sprintf('  <error>✗</error> custom_config 插入失败: %s', $e->getMessage()));
                }
            }
        }

        // option 种子数据
        $optionCount = $this->countRecords($pdo, 'option');
        if ($optionCount >= 8) {
            $output->writeln(sprintf('  <comment>✓</comment> option 表已有 %d 条数据，跳过', $optionCount));
            $skipped++;
        } else {
            $sql = "INSERT IGNORE INTO `option` (`type`, `name`, `value`, `update_time`) VALUES
                    ('sys', 'site_name', 'Crazydb-Blog', UNIX_TIMESTAMP()),
                    ('sys', 'admin_email', 'root@crazydb.com', UNIX_TIMESTAMP()),
                    ('sys', 'allow_comment', 'open', UNIX_TIMESTAMP()),
                    ('sys', 'allow_register', 'open', UNIX_TIMESTAMP()),
                    ('sys', 'need_approve', 'close', UNIX_TIMESTAMP()),
                    ('seo', 'seo_title', 'Crazydb-Blog', UNIX_TIMESTAMP()),
                    ('seo', 'seo_keywords', 'blog,crazydb', UNIX_TIMESTAMP()),
                    ('seo', 'seo_description', 'Crazydb-Blog，基于Yii2的博客系统', UNIX_TIMESTAMP())";

            if ($dryRun) {
                $output->writeln(sprintf('  <info>○</info> option 表数据不足（%d/8），将插入', $optionCount));
                $applied++;
            } else {
                try {
                    $pdo->exec($sql);
                    $output->writeln('  <info>✓</info> option 种子数据已插入');
                    $applied++;
                } catch (PDOException $e) {
                    $output->writeln(sprintf('  <error>✗</error> option 插入失败: %s', $e->getMessage()));
                }
            }
        }

        return ['applied' => $applied, 'skipped' => $skipped];
    }

    private function recordExists(PDO $pdo, string $table, string $column, string $value): bool
    {
        $stmt = $pdo->prepare(sprintf('SELECT COUNT(*) FROM `%s` WHERE `%s` = ?', $table, $column));
        $stmt->execute([$value]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function countRecords(PDO $pdo, string $table): int
    {
        $stmt = $pdo->query(sprintf('SELECT COUNT(*) FROM `%s`', $table));
        return (int) $stmt->fetchColumn();
    }
}
