<?php

declare(strict_types=1);

// 钉死测试环境：避免误用开发/生产 .env 参数（测试必须可复现、不碰生产数据）
if (getenv('APP_ENV') === false && !isset($_ENV['APP_ENV'])) {
    putenv('APP_ENV=test');
}
App\Environment::prepare();
