Yii2-crazydb-blog
=================

这个项目从Yii2还未正式发布就开始折腾，断断续续大概也有八个月了，最近感觉有时间了......闲不过三日....蛋疼

项目基于 Yii 2 Basic Application 模板。

参考地址：[https://github.com/yiisoft/yii2-app-basic](https://github.com/yiisoft/yii2-app-basic)

Yii 2 Basic Application Template
================================

Yii2基本应用模板包含了Yii2的基本特性，适用于创建较小的项目。

Yii1采用.htaccess文件来保护系统文件，而Yii2做的更为彻底，直接从路径上分离web目录和资源目录，架构上讲更科学点。

同时Yii2采用Composer作为包管理工具，使得第三方应用与组件依赖在「网络正常」的情况下更方便安装与更新。

Yii2的高级模板采用了前台和后台分离的方案，更适合大型应用。对于个人blog来说，基本应用模板已经够用。


目录结构
-------------------

      assets/             contains assets definition
      commands/           contains console commands (controllers)
      config/             contains application configurations
      controllers/        contains Web controller classes
      mail/               contains view files for e-mails
      models/             contains model classes
      runtime/            contains files generated during runtime
      tests/              contains various tests for the basic application
      vendor/             contains dependent 3rd-party packages
      views/              contains view files for the Web application(It may be removed on next update)
      themes/             contains view files for different themes
      web/                contains the entry script and Web resources


环境要求
------------

PHP最低版本要求为5.4.0。


安装
------------

只要注意一点，web/目录是网站根目录。

使用composer来安装第三方包。

配置
-------------

### 数据库

编辑 `config/db.php` ，使用当前真实运行环境配置，例如：

```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=yii2basic',
    'username' => 'root',
    'password' => '1234',
    'charset' => 'utf8',
];
```

**注意:** 数据表暂时没有上传，因为结构还不是特别稳定，可以根据model恢复数据库。

同时也可以修改`config/` 文件夹下的其他文件来自定义你的应用。