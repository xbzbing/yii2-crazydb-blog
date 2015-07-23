<?php
/**
 * Created by PhpStorm.
 * User: xbzbing
 * Date: 14-7-23
 * Time: 下午5:07
 */
namespace app\controllers;

use Yii;
use app\components\BaseController;

class AclController extends BaseController
{
    public function actionInit()
    {
        $auth = Yii::$app->authManager;

        // add "createPost" permission
        $createPost = $auth->createPermission('createPost');
        $createPost->description = '新建文章';
        $auth->add($createPost);

        // add "readPost" permission
        $readPost = $auth->createPermission('readPost');
        $readPost->description = 'read a post';
        $auth->add($readPost);

        // add "updatePost" permission
        $updatePost = $auth->createPermission('updatePost');
        $updatePost->description = 'update post';
        $auth->add($updatePost);

        // add "reader" role and give this role the "readPost" permission
        $reader = $auth->createRole('reader');
        $auth->add($reader);
        $auth->addChild($reader, $readPost);

        // add "author" role and give this role the "createPost" permission
        // as well as the permissions of the "reader" role
        $author = $auth->createRole('author');
        $auth->add($author);
        $auth->addChild($author, $createPost);
        $auth->addChild($author, $reader);

        // add "admin" role and give this role the "updatePost" permission
        // as well as the permissions of the "author" role
        $admin = $auth->createRole('admin');
        $auth->add($admin);
        $auth->addChild($admin, $updatePost);
        $auth->addChild($admin, $author);

        // Assign roles to users. 10, 14 and 26 are IDs returned by IdentityInterface::getId()
        // usually implemented in your User model.
        $auth->assign($reader, 1);
    }
}