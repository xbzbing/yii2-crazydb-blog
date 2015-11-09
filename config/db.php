<?php

$db =  [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=#HOST#;dbname=#DBName#',
    'username' => '#user#',
    'password' => '#password#',
    'charset' => 'utf8',
    'tablePrefix'=>'#prefix#'
];

if(file_exists(__DIR__.'/db-local.php'))
    $db = require(__DIR__.'/db-local.php');

return $db;
