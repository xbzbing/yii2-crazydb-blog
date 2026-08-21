<?php

declare(strict_types=1);

namespace App\Console;

use App\Post\HtmlToMarkdownService;
use App\Post\Post;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

/**
 * 批量将历史 HTML 格式文章转换为 Markdown。
 *
 *   ./yii post/html-to-md            # 转换所有 format=html 的文章
 *   ./yii post/html-to-md --dry-run  # 仅预览，不写入数据库
 *   ./yii post/html-to-md --id=46    # 只转换指定 ID 的文章
 */
final class HtmlToMdCommand extends Command
{
    private const COMMAND_NAME = 'post/html-to-md';

    protected function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('批量转换历史 HTML 文章为 Markdown（正文+摘要）')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '仅预览转换结果，不写入数据库')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, '只处理指定 ID 的文章（单篇调试）');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = $input->getOption('dry-run');
        $targetId = $input->getOption('id') ? (int)$input->getOption('id') : null;

        $converter = new HtmlToMarkdownService();

        $query = Post::query()->where(['format' => Post::FORMAT_HTML]);
        if ($targetId !== null) {
            $query->andWhere(['id' => $targetId]);
        }
        /** @var list<Post> $posts */
        $posts = $query->all();

        if ($posts === []) {
            $output->writeln('<info>没有找到需要转换的 HTML 文章。</info>');
            return ExitCode::OK;
        }

        $output->writeln('');
        $output->writeln('<info>' . ($dryRun ? '预览模式' : '执行模式') . '：找到 ' . count($posts) . ' 篇 HTML 文章</info>');
        $output->writeln('');

        $converted = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($posts as $post) {
            $title = mb_substr($post->title, 0, 40, 'UTF-8');
            $contentLen = mb_strlen($post->content, 'UTF-8');
            $excerptLen = mb_strlen((string)($post->excerpt ?? ''), 'UTF-8');

            // 转换正文
            $newContent = $converter->convert((string)$post->content);
            $contentSaved = $contentLen - mb_strlen($newContent, 'UTF-8');
            $contentChanged = $newContent !== $post->content;

            // 转换摘要
            $newExcerpt = $excerptLen > 0 ? $converter->convert((string)$post->excerpt) : '';
            $excerptChanged = $newExcerpt !== $post->excerpt;

            if (!$contentChanged && !$excerptChanged) {
                $output->writeln("  <comment>⏭</comment> [{$post->id}] {$title}（已是 Markdown 或空内容）");
                $skipped++;
                continue;
            }

            $output->writeln("  <info>✓</info> [{$post->id}] {$title}");
            if ($contentChanged) {
                $output->writeln("       正文：{$contentLen} → " . mb_strlen($newContent, 'UTF-8') . " 字符");
            }
            if ($excerptChanged) {
                $output->writeln("       摘要：{$excerptLen} → " . mb_strlen($newExcerpt, 'UTF-8') . " 字符");
            }

            if (!$dryRun) {
                try {
                    $post->content = $newContent;
                    $post->excerpt = $newExcerpt !== '' ? $newExcerpt : null;
                    $post->format = Post::FORMAT_MARKDOWN;
                    $post->save();
                    $converted++;
                } catch (\Throwable $e) {
                    $output->writeln("       <error>保存失败：{$e->getMessage()}</error>");
                    $errors++;
                }
            } else {
                $converted++;
            }
        }

        $output->writeln('');
        $output->writeln(sprintf(
            '<info>完成：%d 篇转换，%d 篇跳过，%d 篇失败</info>',
            $converted,
            $skipped,
            $errors,
        ));

        if ($dryRun) {
            $output->writeln('<comment>（dry-run 模式，未写入数据库）</comment>');
        }

        return $errors > 0 ? ExitCode::IOERR : ExitCode::OK;
    }
}
