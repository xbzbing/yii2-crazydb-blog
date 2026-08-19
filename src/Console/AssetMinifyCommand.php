<?php

declare(strict_types=1);

namespace App\Console;

use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Asset minify for prod deployment.
 *
 * Usage:
 *   ./yii asset/minify            # minify all theme CSS/JS
 *   ./yii asset/minify --dry-run  # preview only, no writes
 */
final class AssetMinifyCommand extends Command
{
    private const THEMES = ['crazydb', 'main', 'magazine'];

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview only, do not write files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = $input->getOption('dry-run');
        $output->writeln('');
        $output->writeln('<info>' . ($dryRun ? 'Preview mode (dry-run)' : 'Minifying assets') . '</info>');
        $output->writeln('');

        $totalSaved = 0;
        $totalFiles = 0;

        foreach (self::THEMES as $theme) {
            // 根目录 CSS（如 assets/main/site.css）
            $rootCss = __DIR__ . '/../../assets/' . $theme . '/site.css';
            if (is_file($rootCss)) {
                $saved = $this->minifyCss($rootCss, $theme, $output, $dryRun);
                if ($saved !== null) { $totalSaved += $saved; $totalFiles++; }
            }
            // css/ 子目录（如 assets/crazydb/css/）
            $cssDir = __DIR__ . '/../../assets/' . $theme . '/css';
            if (is_dir($cssDir)) {
                foreach (glob($cssDir . '/*.css') as $file) {
                    $saved = $this->minifyCss($file, $theme, $output, $dryRun);
                    if ($saved !== null) { $totalSaved += $saved; $totalFiles++; }
                }
            }
            // vendor JS
            $vendorDir = __DIR__ . '/../../assets/' . $theme . '/vendor';
            if (is_dir($vendorDir)) {
                foreach ($this->findJsFiles($vendorDir) as $file) {
                    $saved = $this->minifyJs($file, $theme, $output, $dryRun);
                    if ($saved !== null) { $totalSaved += $saved; $totalFiles++; }
                }
            }
        }

        $output->writeln('');
        $output->writeln(sprintf('<info>Done: %d files, saved %s</info>', $totalFiles, $this->fmt($totalSaved)));
        return ExitCode::OK;
    }

    private function minifyCss(string $file, string $theme, OutputInterface $output, bool $dryRun): ?int
    {
        $orig = filesize($file);
        $min = (new CSS($file))->minify();
        $saved = $orig - strlen($min);
        if ($saved <= 0) return null;
        $rel = $theme . '/' . basename(dirname($file)) . '/' . basename($file);
        $output->writeln(($dryRun ? '  [preview]' : '  [done]') . " {$rel}  {$this->fmt($orig)} -> {$this->fmt(strlen($min))}");
        if (!$dryRun) file_put_contents($file, $min);
        return $saved;
    }

    private function minifyJs(string $file, string $theme, OutputInterface $output, bool $dryRun): ?int
    {
        $orig = filesize($file);
        $min = (new JS($file))->minify();
        $saved = $orig - strlen($min);
        if ($saved <= 0) return null;
        $rel = str_replace(__DIR__ . '/../../assets/' . $theme . '/', $theme . '/', $file);
        $output->writeln(($dryRun ? '  [preview]' : '  [done]') . " {$rel}  {$this->fmt($orig)} -> {$this->fmt(strlen($min))}");
        if (!$dryRun) file_put_contents($file, $min);
        return $saved;
    }

    private function findJsFiles(string $dir): array
    {
        $files = [];
        $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iter as $f) {
            if ($f->isFile() && $f->getExtension() === 'js') $files[] = $f->getPathname();
        }
        return $files;
    }

    private function fmt(int $b): string
    {
        return $b >= 1024 ? sprintf('%.1f KB', $b / 1024) : $b . ' B';
    }
}
