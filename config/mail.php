<?php
$mail = [
    'class' => 'yii\swiftmailer\Mailer',
    'transport' => [
        'class' => 'Swift_SmtpTransport',
        'host' => '#host#',
        'username' => '#username#',
        'password' => '#password#',
        'port' => '587',
        'encryption' => 'tls',
    ],
];
if (YII_ENV_DEV && file_exists(__DIR__ . '/mail-local.php'))
    $mail = require(__DIR__ . '/mail-local.php');
return $mail;