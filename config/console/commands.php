<?php

declare(strict_types=1);

use App\Console;

return [
    'hello' => Console\HelloCommand::class,
    'visit/sync' => Console\VisitSyncCommand::class,
    'post-view/sync' => Console\PostViewSyncCommand::class,
    'init/env' => Console\InitEnvCommand::class,
    'init/admin' => Console\InitAdminCommand::class,
    'init/check' => Console\InitCheckCommand::class,
    'init/migrate' => Console\InitMigrateCommand::class,
    'asset:minify' => Console\AssetMinifyCommand::class,
    'post/html-to-md' => Console\HtmlToMdCommand::class,
];
