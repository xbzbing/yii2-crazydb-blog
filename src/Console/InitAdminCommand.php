<?php

declare(strict_types=1);

namespace App\Console;

use App\User\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Yiisoft\Yii\Console\ExitCode;

use function password_hash;
use function sprintf;

/**
 * 管理员用户初始化命令：用户表为空时创建管理员，否则提示已存在。
 *
 * 用法：
 *   ./yii init/admin
 */
#[AsCommand(
    name: 'init/admin',
    description: '初始化管理员用户（用户表为空时创建，否则提示已存在）',
)]
final class InitAdminCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');
        $output->writeln('<info>👤 管理员用户初始化</info>');
        $output->writeln('');

        // 1. 检查用户表是否已有数据
        $count = User::query()->select('COUNT(*)')->scalar();

        if ($count > 0) {
            $output->writeln(sprintf('<warning>⚠ 用户表已有 %d 条记录，跳过初始化</warning>', $count));
            $output->writeln('<warning>  如需创建新管理员，请直接在后台管理页面操作</warning>');
            $output->writeln('');
            return ExitCode::OK;
        }

        $output->writeln('<info>  用户表为空，开始创建管理员用户</info>');
        $output->writeln('');

        // 2. 交互式输入
        $questionHelper = new QuestionHelper();

        // 用户名
        $usernameQuestion = new Question('  请输入管理员用户名 (默认: admin): ', 'admin');
        $usernameQuestion->setValidator(static function (?string $value): string {
            if ($value === null || $value === '') {
                throw new \RuntimeException('用户名不能为空');
            }
            if (strlen($value) < 3) {
                throw new \RuntimeException('用户名长度不能少于 3 位');
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
                throw new \RuntimeException('用户名只能包含字母、数字和下划线');
            }
            return $value;
        });
        $usernameQuestion->setMaxAttempts(3);
        $username = $questionHelper->ask($input, $output, $usernameQuestion);

        // 昵称
        $nicknameQuestion = new Question('  请输入管理员昵称 (默认: 管理员): ', '管理员');
        $nicknameQuestion->setMaxAttempts(3);
        $nickname = $questionHelper->ask($input, $output, $nicknameQuestion);

        // 邮箱
        $emailQuestion = new Question('  请输入管理员邮箱 (默认: admin@example.com): ', 'admin@example.com');
        $emailQuestion->setValidator(static function (?string $value): string {
            if ($value === null || $value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('请输入有效的邮箱地址');
            }
            return $value;
        });
        $emailQuestion->setMaxAttempts(3);
        $email = $questionHelper->ask($input, $output, $emailQuestion);

        // 密码
        $passwordQuestion = new Question('  请输入管理员密码 (≥6位): ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setValidator(static function (?string $value): string {
            if ($value === null || $value === '') {
                throw new \RuntimeException('密码不能为空');
            }
            if (strlen($value) < 6) {
                throw new \RuntimeException('密码长度不能少于 6 位');
            }
            return $value;
        });
        $passwordQuestion->setMaxAttempts(3);
        $password = $questionHelper->ask($input, $output, $passwordQuestion);

        // 确认密码
        $confirmQuestion = new Question('  请再次输入密码: ');
        $confirmQuestion->setHidden(true);
        $confirmQuestion->setValidator(function (?string $value) use ($password): string {
            if ($value !== $password) {
                throw new \RuntimeException('两次输入的密码不一致');
            }
            return $value;
        });
        $confirmQuestion->setMaxAttempts(3);
        $questionHelper->ask($input, $output, $confirmQuestion);

        // 3. 创建用户
        $user = new User();
        $user->username = $username;
        $user->nickname = $nickname;
        $user->email = $email;
        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->role = User::ROLE_ADMIN;
        $user->status = User::STATUS_NORMAL;
        $user->register_ip = '127.0.0.1';
        $user->register_time = time();
        $user->update_time = time();
        $user->active_time = time();
        $user->auth_key = bin2hex(random_bytes(32));

        // Yii3 ActiveRecord::save() 为 void：失败时抛异常，而不是返回 false
        try {
            $user->save();
        } catch (\Throwable $e) {
            $output->writeln('<error>创建用户失败：' . $e->getMessage() . '</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $output->writeln('');
        $output->writeln('<info>✅ 管理员用户创建成功</info>');
        $output->writeln('');
        $output->writeln(sprintf('  用户名: %s', $username));
        $output->writeln(sprintf('  昵称:   %s', $nickname));
        $output->writeln(sprintf('  邮箱:   %s', $email));
        $output->writeln(sprintf('  角色:   管理员 (role=%d)', User::ROLE_ADMIN));
        $output->writeln(sprintf('  用户ID: %d', $user->id));
        $output->writeln('');
        $output->writeln('<info>  现在可以通过 /login 使用此账号登录前台，然后访问 /admin 进入后台管理</info>');
        $output->writeln('');

        return ExitCode::OK;
    }
}
