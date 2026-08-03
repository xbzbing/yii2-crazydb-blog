<?php

$cache =  ['class' => 'yii\caching\FileCache'];

if(file_exists(__DIR__.'/cache-local.php'))
    $cache = require(__DIR__.'/cache-local.php');

return $cache;
