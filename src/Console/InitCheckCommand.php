<?php

declare(strict_types=1);

namespace App\Console;

use PDO;
use PDOException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

use function array_diff_key;
use function array_keys;
use function array_merge;
use function array_unique;
use function count;
use function explode;
use function implode;
use function in_array;
use function ksort;
use function preg_match;
use function sprintf;
use function strpos;
use function strtoupper;
use function trim;

/**
 * 数据库结构检查命令：检查表是否存在、结构是否符合预期、是否需要迁移。
 *
 * 用法：
 *   ./yii init/check
 */
#[AsCommand(
    name: 'init/check',
    description: '检查数据库表结构是否符合预期，是否需要迁移',
)]
final class InitCheckCommand extends Command
{
    /**
     * 预期的表结构定义（与 deploy/schema.sql 保持一致）
     *
     * @var array<string, array{columns: array<string, string>, indexes: array<string, string[]>}>
     */
    private const EXPECTED_SCHEMA = [
        'post' => [
            'columns' => [
                'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                'cid' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'author_id' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'author_name' => "VARCHAR(80) NOT NULL DEFAULT ''",
                'type' => "VARCHAR(20) NOT NULL DEFAULT 'post'",
                'title' => "VARCHAR(255) NOT NULL DEFAULT ''",
                'alias' => "VARCHAR(255) NOT NULL DEFAULT ''",
                'excerpt' => 'TEXT',
                'content' => 'MEDIUMTEXT',
                'format' => "VARCHAR(10) NOT NULL DEFAULT 'html'",
                'cover' => 'VARCHAR(255) DEFAULT NULL',
                'password' => 'VARCHAR(32) DEFAULT NULL',
                'status' => "VARCHAR(20) NOT NULL DEFAULT 'published'",
                'create_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'post_time' => 'INT UNSIGNED DEFAULT NULL',
                'update_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'tags' => "VARCHAR(255) NOT NULL DEFAULT ''",
                'comment_count' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'view_count' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'is_top' => 'TINYINT(1) NOT NULL DEFAULT 0',
            ],
            'indexes' => [
                'PRIMARY' => ['id'],
                'uk_alias' => ['alias'],
                'idx_cid' => ['cid'],
                'idx_status_post_time' => ['status', 'post_time'],
                'idx_author' => ['author_id'],
                'idx_update_time' => ['update_time'],
            ],
        ],
        'category' => [
            'columns' => [
                'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                'name' => "VARCHAR(255) NOT NULL DEFAULT ''",
                'alias' => "VARCHAR(100) NOT NULL DEFAULT ''",
                'desc' => 'TEXT',
                'pid' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'display' => "VARCHAR(20) NOT NULL DEFAULT 'list'",
                'sort_order' => 'INT NOT NULL DEFAULT 0',
                'keywords' => "VARCHAR(255) NOT NULL DEFAULT ''",
                'update_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
            ],
            'indexes' => [
                'PRIMARY' => ['id'],
                'uk_alias' => ['alias'],
                'idx_pid_sort' => ['pid', 'sort_order'],
                'idx_update_time' => ['update_time'],
            ],
        ],
        'comment' => [
            'columns' => [
                'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                'pid' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'uid' => 'INT UNSIGNED DEFAULT NULL',
                'nickname' => "VARCHAR(80) NOT NULL DEFAULT ''",
                'email' => "VARCHAR(100) NOT NULL DEFAULT ''",
                'reply_to' => 'INT UNSIGNED DEFAULT NULL',
                'url' => 'VARCHAR(255) DEFAULT NULL',
                'ip' => "VARCHAR(46) NOT NULL DEFAULT ''",
                'user_agent' => "VARCHAR(255) NOT NULL DEFAULT ''",
                'create_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'update_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'content' => 'TEXT',
                'status' => "VARCHAR(20) NOT NULL DEFAULT 'unapproved'",
            ],
            'indexes' => [
                'PRIMARY' => ['id'],
                'idx_pid' => ['pid'],
                'idx_status_create' => ['status', 'create_time'],
                'idx_update_time' => ['update_time'],
            ],
        ],
        'user' => [
            'columns' => [
                'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                'nickname' => "VARCHAR(80) NOT NULL DEFAULT ''",
                'username' => "VARCHAR(20) NOT NULL DEFAULT ''",
                'avatar' => 'VARCHAR(255) DEFAULT NULL',
                'password' => "VARCHAR(60) NOT NULL DEFAULT ''",
                'email' => "VARCHAR(100) NOT NULL DEFAULT ''",
                'website' => 'VARCHAR(100) DEFAULT NULL',
                'role' => 'TINYINT UNSIGNED NOT NULL DEFAULT 1',
                'register_ip' => "VARCHAR(46) NOT NULL DEFAULT ''",
                'register_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'update_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'active_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'auth_key' => "VARCHAR(64) NOT NULL DEFAULT ''",
                'status' => 'TINYINT UNSIGNED NOT NULL DEFAULT 1',
                'info' => 'TEXT',
                'ext' => 'TEXT',
            ],
            'indexes' => [
                'PRIMARY' => ['id'],
                'uk_username' => ['username'],
                'uk_email' => ['email'],
                'idx_nickname' => ['nickname'],
            ],
        ],
        'tag' => [
            'columns' => [
                'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                'name' => "VARCHAR(255) NOT NULL DEFAULT ''",
                'pid' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'cid' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'create_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
            ],
            'indexes' => [
                'PRIMARY' => ['id'],
                'idx_pid' => ['pid'],
                'idx_name' => ['name'],
            ],
        ],
        'nav' => [
            'columns' => [
                'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                'pid' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'name' => "VARCHAR(255) NOT NULL DEFAULT ''",
                'url' => "VARCHAR(255) NOT NULL DEFAULT ''",
                'route' => 'TINYINT(1) NOT NULL DEFAULT 0',
                'sort_order' => 'INT NOT NULL DEFAULT 0',
                'extra' => 'VARCHAR(255) DEFAULT NULL',
                'create_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'update_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
            ],
            'indexes' => [
                'PRIMARY' => ['id'],
                'idx_pid_sort' => ['pid', 'sort_order'],
                'idx_update_time' => ['update_time'],
            ],
        ],
        'option' => [
            'columns' => [
                'type' => "VARCHAR(20) NOT NULL",
                'name' => "VARCHAR(50) NOT NULL",
                'value' => 'TEXT',
                'description' => "VARCHAR(255) NOT NULL DEFAULT ''",
                'update_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
            ],
            'indexes' => [
                'PRIMARY' => ['type', 'name'],
                'idx_update_time' => ['update_time'],
            ],
        ],
        'log' => [
            'columns' => [
                'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                'uid' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'type' => "VARCHAR(20) NOT NULL DEFAULT 'default'",
                'action' => "VARCHAR(100) NOT NULL DEFAULT ''",
                'result' => "VARCHAR(100) NOT NULL DEFAULT ''",
                'key' => "VARCHAR(100) NOT NULL DEFAULT ''",
                'detail' => "VARCHAR(255) NOT NULL DEFAULT ''",
                'create_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'ip' => "VARCHAR(46) NOT NULL DEFAULT ''",
                'user_agent' => "VARCHAR(255) NOT NULL DEFAULT ''",
            ],
            'indexes' => [
                'PRIMARY' => ['id'],
                'idx_uid' => ['uid'],
                'idx_create_time' => ['create_time'],
            ],
        ],
        'visit_daily' => [
            'columns' => [
                'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                'date' => 'DATE NOT NULL',
                'pv' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'uv' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'create_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'update_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
            ],
            'indexes' => [
                'PRIMARY' => ['id'],
                'uk_date' => ['date'],
            ],
        ],
        'custom_config' => [
            'columns' => [
                'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                'category' => "VARCHAR(100) NOT NULL DEFAULT ''",
                'key' => "VARCHAR(100) NOT NULL DEFAULT ''",
                'name' => "VARCHAR(255) NOT NULL DEFAULT ''",
                'value' => 'TEXT',
                'data_type' => "VARCHAR(20) NOT NULL DEFAULT 'text'",
                'priority' => 'INT NOT NULL DEFAULT 0',
                'description' => "VARCHAR(255) NOT NULL DEFAULT ''",
                'create_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
                'update_time' => 'INT UNSIGNED NOT NULL DEFAULT 0',
            ],
            'indexes' => [
                'PRIMARY' => ['id'],
                'uk_category_key' => ['category', 'key'],
                'idx_priority' => ['category', 'priority'],
            ],
        ],
    ];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');
        $output->writeln('<info>🔍 数据库结构检查</info>');
        $output->writeln('');

        // 1. 连接数据库
        $pdo = $this->connect($output);
        if ($pdo === null) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // 2. 获取实际表结构
        $actualTables = $this->getActualTables($pdo, $output);
        if ($actualTables === null) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // 3. 比较
        $expectedTables = array_keys(self::EXPECTED_SCHEMA);
        $actualTableNames = array_keys($actualTables);
        $missingTables = array_diff($expectedTables, $actualTableNames);
        $extraTables = array_diff($actualTableNames, $expectedTables);

        $issues = [];
        $warnings = [];

        // 缺失的表
        foreach ($missingTables as $table) {
            $issues[] = sprintf('表 <error>%s</error> 不存在', $table);
        }

        // 多余的表（不在预期中）
        foreach ($extraTables as $table) {
            $warnings[] = sprintf('表 <comment>%s</comment> 不在预期 schema 中（可能是自定义表）', $table);
        }

        // 检查每个预期表的结构
        foreach (self::EXPECTED_SCHEMA as $tableName => $expected) {
            if (!isset($actualTables[$tableName])) {
                continue; // 已在 missingTables 中报告
            }

            $actual = $actualTables[$tableName];

            // 检查列
            $expectedColumns = array_keys($expected['columns']);
            $actualColumns = array_keys($actual['columns']);
            $missingColumns = array_diff($expectedColumns, $actualColumns);
            $extraColumns = array_diff($actualColumns, $expectedColumns);

            foreach ($missingColumns as $col) {
                $issues[] = sprintf('表 <error>%s</error> 缺少列 <error>%s</error>', $tableName, $col);
            }

            foreach ($extraColumns as $col) {
                $warnings[] = sprintf('表 <comment>%s</comment> 有额外列 <comment>%s</comment>', $tableName, $col);
            }

            // 检查索引
            $expectedIndexes = $expected['indexes'];
            $actualIndexes = $actual['indexes'];
            $missingIndexes = array_diff_key($expectedIndexes, $actualIndexes);
            $extraIndexes = array_diff_key($actualIndexes, $expectedIndexes);

            foreach ($missingIndexes as $idxName => $idxCols) {
                $issues[] = sprintf('表 <error>%s</error> 缺少索引 <error>%s</error> (%s)', $tableName, $idxName, implode(', ', $idxCols));
            }

            foreach ($extraIndexes as $idxName => $idxCols) {
                $warnings[] = sprintf('表 <comment>%s</comment> 有额外索引 <comment>%s</comment> (%s)', $tableName, $idxName, implode(', ', $idxCols));
            }
        }

        // 4. 输出结果
        $output->writeln('<info>━━━ 检查结果 ━━━</info>');
        $output->writeln('');

        if (empty($issues) && empty($warnings)) {
            $output->writeln('<info>✅ 数据库结构完全符合预期</info>');
            $output->writeln('');
            return ExitCode::OK;
        }

        if (!empty($issues)) {
            $output->writeln(sprintf('<error>❌ 发现 %d 个问题需要处理：</error>', count($issues)));
            $output->writeln('');
            foreach ($issues as $issue) {
                $output->writeln('  • ' . $issue);
            }
            $output->writeln('');
        }

        if (!empty($warnings)) {
            $output->writeln(sprintf('<warning>⚠ 发现 %d 个警告（可忽略）：</warning>', count($warnings)));
            $output->writeln('');
            foreach ($warnings as $warning) {
                $output->writeln('  • ' . $warning);
            }
            $output->writeln('');
        }

        // 5. 建议
        if (!empty($missingTables) || $this->hasMissingColumns($issues)) {
            $output->writeln('<info>💡 建议操作：</info>');
            $output->writeln('  运行以下命令导入 schema.sql：');
            $output->writeln('');
            $output->writeln('    mysql -h' . $this->getHost() . ' -u' . $this->getUser() . ' -p ' . $this->getDatabase() . ' < deploy/schema.sql');
            $output->writeln('');
            $output->writeln('  注意：schema.sql 使用 CREATE TABLE IF NOT EXISTS，不会覆盖已有表');
            $output->writeln('  如需修改已有表结构，请使用 migrate 或手动 ALTER TABLE');
        }

        $output->writeln('');
        return empty($issues) ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }

    /**
     * 连接数据库
     */
    private function connect(OutputInterface $output): ?PDO
    {
        $host = $this->getHost();
        $port = $this->getPort();
        $database = $this->getDatabase();
        $user = $this->getUser();
        $password = $this->getPassword();

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
            $output->writeln('<warning>  请检查 .env 中的数据库配置：</warning>');
            $output->writeln('    DB_HOST=' . $host);
            $output->writeln('    DB_PORT=' . $port);
            $output->writeln('    DB_NAME=' . $database);
            $output->writeln('    DB_USER=' . $user);
            $output->writeln('    DB_PASSWORD=' . ($password !== '' ? '***' : '(空)'));
            $output->writeln('');
            return null;
        }
    }

    /**
     * 获取实际表结构
     *
     * @return array<string, array{columns: array<string, string>, indexes: array<string, string[]>}>|null
     */
    private function getActualTables(PDO $pdo, OutputInterface $output): ?array
    {
        $database = $this->getDatabase();

        // 获取所有表
        $stmt = $pdo->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME');
        $stmt->execute([$database]);
        $tables = $stmt->fetchAll();

        if (empty($tables)) {
            $output->writeln('<warning>⚠ 数据库中没有找到任何表</warning>');
            $output->writeln('');
            $output->writeln('<info>💡 请先导入 schema.sql：</info>');
            $output->writeln('  mysql -h' . $this->getHost() . ' -u' . $this->getUser() . ' -p ' . $database . ' < deploy/schema.sql');
            $output->writeln('');
            return [];
        }

        $output->writeln(sprintf('  <info>✓</info> 找到 %d 张表', count($tables)));
        $output->writeln('');

        $result = [];

        foreach ($tables as $table) {
            $rawTableName = $table['TABLE_NAME'];

            // 剥离表前缀（blog_xxx → xxx），与 EXPECTED_SCHEMA 无前缀名对齐
            $tableName = $rawTableName;
            $prefix = $this->getTablePrefix();
            if ($prefix !== '' && str_starts_with($rawTableName, $prefix)) {
                $tableName = substr($rawTableName, strlen($prefix));
            }

            // 获取列信息（用原始表名查询）
            $stmt = $pdo->prepare('
                SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION
            ');
            $stmt->execute([$database, $rawTableName]);
            $columns = $stmt->fetchAll();

            $columnMap = [];
            foreach ($columns as $col) {
                $columnMap[$col['COLUMN_NAME']] = $this->formatColumnType($col);
            }

            // 获取索引信息（用原始表名查询）
            $stmt = $pdo->prepare('
                SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as INDEX_COLUMNS
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                GROUP BY INDEX_NAME
                ORDER BY INDEX_NAME
            ');
            $stmt->execute([$database, $rawTableName]);
            $indexes = $stmt->fetchAll();

            $indexMap = [];
            foreach ($indexes as $idx) {
                $indexMap[$idx['INDEX_NAME']] = explode(',', $idx['INDEX_COLUMNS']);
            }

            $result[$tableName] = [
                'columns' => $columnMap,
                'indexes' => $indexMap,
            ];
        }

        return $result;
    }

    /**
     * 格式化列类型为与 EXPECTED_SCHEMA 可比较的格式
     */
    private function formatColumnType(array $col): string
    {
        $type = strtoupper($col['COLUMN_TYPE']);
        $nullable = $col['IS_NULLABLE'] === 'YES' ? ' NULL' : ' NOT NULL';
        $default = $col['COLUMN_DEFAULT'];
        $extra = $col['EXTRA'];

        $parts = [$type];

        if ($extra !== '' && strpos($extra, 'auto_increment') !== false) {
            $parts[] = 'AUTO_INCREMENT';
        }

        $parts[] = $nullable;

        if ($default !== null && $extra === '') {
            $parts[] = 'DEFAULT ' . $default;
        }

        return implode(' ', $parts);
    }

    private function getHost(): string
    {
        return (string) ($_ENV['DB_HOST'] ?? getenv('DB_HOST')) ?: '127.0.0.1';
    }

    private function getPort(): string
    {
        return (string) ($_ENV['DB_PORT'] ?? getenv('DB_PORT')) ?: '3306';
    }

    private function getDatabase(): string
    {
        return (string) ($_ENV['DB_NAME'] ?? getenv('DB_NAME')) ?: 'crazydb';
    }

    private function getUser(): string
    {
        return (string) ($_ENV['DB_USER'] ?? getenv('DB_USER')) ?: 'root';
    }

    private function getPassword(): string
    {
        return (string) ($_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD'));
    }

    /**
     * 表前缀（Yii2 遗留 blog_），默认从 env DB_TABLE_PREFIX 读取。
     */
    private function getTablePrefix(): string
    {
        $raw = (string) ($_ENV['DB_TABLE_PREFIX'] ?? getenv('DB_TABLE_PREFIX'));
        return $raw !== '' ? $raw : 'blog_';
    }

    private function hasMissingColumns(array $issues): bool
    {
        foreach ($issues as $issue) {
            if (strpos($issue, '缺少列') !== false || strpos($issue, '缺少索引') !== false) {
                return true;
            }
        }
        return false;
    }
}
