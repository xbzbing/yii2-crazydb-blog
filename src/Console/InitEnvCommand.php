<?php

declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Yiisoft\Yii\Console\ExitCode;

use function basename;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_readable;
use function preg_match;
use function sprintf;
use function str_starts_with;
use function trim;

/**
 * 环境配置初始化命令：创建/检查 .env 文件，交互式填写缺失项。
 *
 * 用法：
 *   ./yii init/env           # 检查并补全缺失配置
 *   ./yii init/env --force   # 强制重新检查所有配置项
 */
#[AsCommand(
    name: 'init/env',
    description: '创建/检查 .env 环境配置文件，交互式填写缺失项',
)]
final class InitEnvCommand extends Command
{
    /** 必填配置项（未设置则强制要求输入） */
    private const REQUIRED_KEYS = [
        'DB_PASSWORD',
        'REDIS_PASSWORD',
    ];

    /** 默认值（.env.example 中未定义但应自动填入的值） */
    private const DEFAULTS = [
        'APP_ENV' => 'prod',
        'APP_DEBUG' => 'false',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_NAME' => 'crazydb',
        'DB_USER' => 'root',
        'REDIS_HOST' => '127.0.0.1',
        'REDIS_PORT' => '6379',
        'NGINX_HTTP_PORT' => '80',
        'NGINX_HTTPS_PORT' => '443',
    ];

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, '强制重新检查所有配置项（包括已设置的）');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rootPath = dirname(__DIR__, 2);
        $envPath = $rootPath . '/.env';
        $examplePath = $rootPath . '/.env.example';
        $force = $input->getOption('force');

        $output->writeln('');
        $output->writeln('<info>🔧 环境配置检查</info>');
        $output->writeln('');

        // 1. 检查 .env.example 是否存在
        if (!is_readable($examplePath)) {
            $output->writeln('<error>错误：.env.example 文件不存在或不可读</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // 2. 如果 .env 不存在，从 .env.example 复制
        if (!file_exists($envPath)) {
            copy($examplePath, $envPath);
            $output->writeln('<comment>  已创建 .env（从 .env.example 复制）</comment>');
            $output->writeln('');
        }

        // 3. 解析 .env.example 获取变量定义
        $exampleVars = $this->parseEnvFile($examplePath);

        // 4. 读取当前 .env 值
        $currentEnv = $this->parseEnvFile($envPath);

        // 5. 逐项检查
        $questionHelper = new QuestionHelper();
        $updated = false;
        $lines = $this->readEnvLines($envPath);

        foreach ($exampleVars as $key => $exampleValue) {
            $currentValue = $currentEnv[$key] ?? '';
            $isRequired = in_array($key, self::REQUIRED_KEYS, true);
            $hasDefault = $exampleValue !== '' || isset(self::DEFAULTS[$key]);

            // 判断是否需要用户输入
            $needsInput = false;
            if ($force) {
                $needsInput = true;
            } elseif ($isRequired && $currentValue === '') {
                $needsInput = true;
            }

            if ($needsInput) {
                // 显示当前状态
                $displayCurrent = $currentValue !== '' ? $currentValue : '(未设置)';
                $output->writeln(sprintf('  <comment>%-20s</comment> %-20s', $key, $displayCurrent));

                if ($isRequired && $currentValue === '') {
                    $output->writeln('  ← <info>必填</info>');
                }

                // 交互式输入
                $newValue = $this->promptValue($output, $questionHelper, $input, $key, $isRequired, $exampleValue);

                if ($newValue !== null && $newValue !== $currentValue) {
                    $lines = $this->updateEnvLine($lines, $key, $newValue);
                    $updated = true;
                    $output->writeln('    <info>✓ 已设置</info>');
                } else {
                    $output->writeln('    <comment>  保持原值</comment>');
                }
            } else {
                // 显示已配置状态
                $displayValue = $currentValue !== '' ? $currentValue : ($exampleValue ?: '(空)');
                $status = $currentValue !== '' ? '<info>✓</info>' : '<comment>✓</comment>';
                $output->writeln(sprintf('  %-20s %-20s %s', $key, $displayValue, $status));
            }
        }

        // 6. 写入更新后的 .env
        if ($updated) {
            file_put_contents($envPath, implode('', $lines));
            $output->writeln('');
            $output->writeln('<info>✅ .env 配置已更新</info>');
        } else {
            $output->writeln('');
            $output->writeln('<info>✅ .env 配置检查完成</info>');
        }

        $output->writeln('');

        // 7. 检查必填项
        $finalEnv = $this->parseEnvFile($envPath);
        $missing = [];
        foreach (self::REQUIRED_KEYS as $key) {
            if (($finalEnv[$key] ?? '') === '') {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            $output->writeln('<warning>⚠ 以下必填项仍未配置：' . implode(', ', $missing) . '</warning>');
            $output->writeln('<warning>  请运行 ./yii init/env 或手动编辑 .env</warning>');
            $output->writeln('');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $output->writeln('<info>🎉 所有配置项已就绪！</info>');
        $output->writeln('');
        $output->writeln('<info>下一步：</info>');
        $output->writeln('<info>  docker compose up -d     # 启动服务</info>');
        $output->writeln('<info>  docker compose exec php php yii init/migrate  # 初始化数据库（首次）</info>');
        $output->writeln('');

        return ExitCode::OK;
    }

    /**
     * 解析 .env 文件，返回 [KEY => value] 数组。
     *
     * @return array<string, string>
     */
    private function parseEnvFile(string $path): array
    {
        $result = [];
        if (!is_readable($path)) {
            return $result;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return $result;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (preg_match('/^([A-Z_][A-Z0-9_]*)=(.*)$/', $line, $m)) {
                $key = $m[1];
                $value = trim($m[2], " \t\"'");
                // 去除行内注释（# 后面的内容）
                $commentPos = strpos($value, '#');
                if ($commentPos !== false) {
                    $value = trim(substr($value, 0, $commentPos));
                }
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 读取 .env 文件的原始行（保留注释和格式）。
     *
     * @return list<string>
     */
    private function readEnvLines(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }
        return file($path, FILE_IGNORE_NEW_LINES) ?: [];
    }

    /**
     * 更新 .env 文件中指定 KEY 的值。
     *
     * @param list<string> $lines
     * @return list<string>
     */
    private function updateEnvLine(array $lines, string $key, string $value): array
    {
        $found = false;
        foreach ($lines as $i => $line) {
            if (preg_match('/^' . preg_quote($key, '/') . '=/', $line)) {
                $lines[$i] = $key . '=' . $value;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $lines[] = $key . '=' . $value;
        }

        return $lines;
    }

    /**
     * 交互式输入配置值。
     */
    private function promptValue(
        OutputInterface $output,
        QuestionHelper $questionHelper,
        InputInterface $input,
        string $key,
        bool $isRequired,
        string $exampleValue,
    ): ?string {
        $hint = $isRequired ? '（必填）' : '（回车跳过）';
        $default = $isRequired ? null : ($exampleValue ?: null);

        $question = new Question(sprintf("    请输入 %s 的值 %s: ", $key, $hint), $default);
        $question->setHidden(true);

        if ($isRequired) {
            $question->setValidator(static function (?string $value) use ($key): string {
                if ($value === null || $value === '') {
                    throw new \RuntimeException(sprintf('%s 为必填项，不能为空', $key));
                }
                if (strlen($value) < 6) {
                    throw new \RuntimeException(sprintf('%s 长度不能少于 6 位', $key));
                }
                return $value;
            });
            $question->setMaxAttempts(3);
        }

        try {
            return $questionHelper->ask($input, $output, $question);
        } catch (\RuntimeException) {
            return null;
        }
    }
}
