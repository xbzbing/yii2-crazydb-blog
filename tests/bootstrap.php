<?php

declare(strict_types=1);

// 无条件钉死测试环境（putenv 即可：Environment 读 getenv；不写 $_ENV，
// 否则 src/bootstrap 的 empty($_ENV['APP_ENV']) 判断会跳过 .env 加载）
putenv('APP_ENV=test');
App\Environment::prepare();
