<?php

declare(strict_types=1);

use App\Console;

return [
    'hello' => Console\HelloCommand::class,
    'visit/sync' => Console\VisitSyncCommand::class,
];
