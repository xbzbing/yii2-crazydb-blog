<?php

declare(strict_types=1);

namespace App\Console;

use App\Common\AssetMinifyService;
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
    public function __construct(private readonly AssetMinifyService $assetMinify)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        // 命令名由 config/console/commands.php 的 'asset:minify' 键定义
        $this->setDescription('Minify theme CSS/JS assets (dry-run to preview)');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview only, do not write files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool) $input->getOption('dry-run');
        $output->writeln('');
        $output->writeln('<info>' . ($dryRun ? 'Preview mode (dry-run)' : 'Minifying assets') . '</info>');
        $output->writeln('');

        $totalSaved = 0;
        $totalFiles = 0;

        foreach (AssetMinifyService::THEMES as $theme) {
            // 根目录 CSS（如 assets/main/site.css）
            $rootCss = __DIR__ . '/../../assets/' . $theme . '/site.css';
            if (is_file($rootCss)) {
                $saved = $this->minifyCss($rootCss, $theme, $output, $dryRun);
                if ($saved !== null) { $totalSaved += $saved; $totalFiles++; }
            }
            // 根目录其他主题 CSS（如 assets/magazine/magazine.css）
            $themeDir = __DIR__ . '/../../assets/' . $theme;
            if (is_dir($themeDir)) {
                foreach (glob($themeDir . '/*.css') as $extraCss) {
                    if ($extraCss !== $rootCss) {
                        $saved = $this->minifyCss($extraCss, $theme, $output, $dryRun);
                        if ($saved !== null) { $totalSaved += $saved; $totalFiles++; }
                    }
                }
            }
            // css/ 子目录（如 assets/crazydb/css/）
            $cssDir = __DIR__ . '/../../assets/' . $theme . '/css';
            if (is_dir($cssDir)) {
                $cssFiles = glob($cssDir . '/*.css');
                if (is_array($cssFiles)) {
                    foreach ($cssFiles as $file) {
                        $saved = $this->minifyCss($file, $theme, $output, $dryRun);
                        if ($saved !== null) { $totalSaved += $saved; $totalFiles++; }
                    }
                }
            }
            // vendor JS
            $vendorDir = __DIR__ . '/../../assets/' . $theme . '/vendor';
            if (is_dir($vendorDir)) {
                foreach ($this->assetMinify->findJsFiles($vendorDir) as $file) {
                    $saved = $this->minifyJs($file, $theme, $output, $dryRun);
                    if ($saved !== null) { $totalSaved += $saved; $totalFiles++; }
                }
            }
        }

        $output->writeln('');
        $output->writeln(sprintf('<info>Done: %d files, saved %s</info>', $totalFiles, AssetMinifyService::fmtBytes($totalSaved)));
        return ExitCode::OK;
    }

    private function minifyCss(string $file, string $theme, OutputInterface $output, bool $dryRun): ?int
    {
        $orig = filesize($file);
        if ($orig === false) {
            return null;
        }
        $saved = $this->assetMinify->minifyCss($file, $dryRun);
        if ($saved === null) {
            return null;
        }
        $rel = $theme . '/' . basename(dirname($file)) . '/' . basename($file);
        $output->writeln(($dryRun ? '  [preview]' : '  [done]') . " {$rel}  {$this->fmt($orig)} -> {$this->fmt(max(0, $orig - $saved))}");
        return $saved;
    }

    private function minifyJs(string $file, string $theme, OutputInterface $output, bool $dryRun): ?int
    {
        $orig = filesize($file);
        if ($orig === false) {
            return null;
        }
        $saved = $this->assetMinify->minifyJs($file, $dryRun);
        if ($saved === null) {
            return null;
        }
        $rel = str_replace(__DIR__ . '/../../assets/' . $theme . '/', $theme . '/', $file);
        $output->writeln(($dryRun ? '  [preview]' : '  [done]') . " {$rel}  {$this->fmt($orig)} -> {$this->fmt(max(0, $orig - $saved))}");
        return $saved;
    }

    private function fmt(int $b): string
    {
        return AssetMinifyService::fmtBytes($b);
    }
}
