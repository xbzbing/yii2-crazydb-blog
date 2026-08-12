<?php

declare(strict_types=1);

use App\Web;
use Yiisoft\Http\Method;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

/**
 * URL compatibility with the legacy Yii2 site (see docs/url-compatibility.md).
 * Handlers are implemented in later phases (B/E); placeholders return 404.
 */
return [
    Group::create()
        ->routes(
            Route::get('/sitemap.xml')->action(Web\Sitemap\Action::class)->name('sitemap'),
            // Home
            Route::get('/')->action(Web\HomePage\Action::class)->name('site/index'),
            Route::get('/page/{page:\d+}')->action(Web\HomePage\Action::class)->name('site/index-page'),

            // Auth
            Route::methods([Method::GET, Method::POST], '/login')->action(Web\Login\Action::class)->name('site/login'),
            Route::post('/logout')->action(Web\Logout\Action::class)->name('site/logout'),
            Route::methods([Method::GET, Method::POST], '/register')->action(Web\Register\Action::class)->name('site/register'),
            Route::get('/site/captcha')->action(Web\Placeholder\NotFoundAction::class)->name('site/captcha'),

            // Categories
            Route::get('/catalog/{alias}')->action(Web\CategoryShow\Action::class)->name('category/show'),
            Route::get('/category/{id:\d+}')->action(Web\CategoryShow\Action::class)->name('category/view'),
            Route::get('/catalog/{alias}/page/{page:\d+}')->action(Web\CategoryShow\Action::class)->name('category/show-page'),

            // Posts
            Route::methods([Method::GET, Method::POST], '/archive/{alias}')->action(Web\PostShow\Action::class)->name('post/show'),
            Route::get('/posts')->action(Web\PostList\Action::class)->name('post/list'),
            Route::get('/posts/page/{page:\d+}')->action(Web\PostList\Action::class)->name('post/list-page'),
            Route::methods([Method::GET, Method::POST], '/post/{id:\d+}')->action(Web\PostShow\Action::class)->name('post/view'),
            Route::get('/archives')->action(Web\Archives\Action::class)->name('post/archives'),
            Route::get('/archives/{year:\d{4}}/{month:\d{1,2}}')->action(Web\ArchivesDate\Action::class)->name('post/archives-date'),

            // Tags
            Route::get('/tag/{name}')->action(Web\TagShow\Action::class)->name('tag/show'),
            Route::get('/tag/{name}/page/{page:\d+}')->action(Web\TagShow\Action::class)->name('tag/show-page'),
            Route::get('/tags')->action(Web\TagList\Action::class)->name('tag/list'),

            // Users (static paths before /user/{name} — match order is significant)
            Route::get('/user/profile/{name}')->action(Web\UserShow\Action::class)->name('user/profile'),
            Route::methods([Method::GET, Method::POST], '/user/profile')->action(Web\UserProfile\Action::class)->name('user/profile-edit'),
            Route::methods([Method::GET, Method::POST], '/user/modify-password')->action(Web\ModifyPassword\Action::class)->name('user/modify-password'),
            Route::get('/user/{name}')->action(Web\UserShow\Action::class)->name('user/show'),
            Route::get('/user/{name}/page/{page:\d+}')->action(Web\UserShow\Action::class)->name('user/show-page'),

            // Comments
            Route::post('/comment/add/{id:\d+}')->action(Web\CommentAdd\Action::class)->name('comment/add'),
            Route::get('/comment/{id:\d+}')->action(Web\Placeholder\NotFoundAction::class)->name('comment/view'),
            Route::get('/comments')->action(Web\Placeholder\NotFoundAction::class)->name('comment/list'),
            Route::get('/comments/page/{page:\d+}')->action(Web\Placeholder\NotFoundAction::class)->name('comment/list-page'),

            // Feeds
            Route::get('/feed/rss')->action(Web\FeedRss\Action::class)->name('feed/rss'),
            Route::get('/feed/atom')->action(Web\FeedAtom\Action::class)->name('feed/atom'),

            // Tools
            Route::get('/tool/image-upload')->action(Web\Placeholder\NotFoundAction::class)->name('tool/image-upload'),
            Route::get('/tool/captcha')->action(Web\ToolCaptcha\Action::class)->name('tool/captcha'),
        ),

    Group::create('/admin')
        ->middleware(\App\Admin\AdminGuardMiddleware::class)
        ->routes(
            Route::get('')->action(\App\Admin\Dashboard\Action::class)->name('admin/index'),
            Route::get('/posts')->action(\App\Admin\PostList\Action::class)->name('admin/post/list'),
            Route::get('/posts/page/{page:\d+}')->action(\App\Admin\PostList\Action::class)->name('admin/post/list-page'),
            Route::methods([Method::GET, Method::POST], '/post/create')->action(\App\Admin\PostForm\Action::class)->name('admin/post/create'),
            Route::methods([Method::GET, Method::POST], '/post/update/{id:\d+}')->action(\App\Admin\PostForm\Action::class)->name('admin/post/update'),
            Route::post('/post/delete/{id:\d+}')->action(\App\Admin\PostDelete\Action::class)->name('admin/post/delete'),
            Route::get('/comments')->action(\App\Admin\CommentList\Action::class)->name('admin/comment/list'),
            Route::get('/comments/page/{page:\d+}')->action(\App\Admin\CommentList\Action::class)->name('admin/comment/list-page'),
            Route::post('/comment/{action}/{id:\d+}')->action(\App\Admin\CommentAction\Action::class)->name('admin/comment/action'),
            Route::methods([Method::GET, Method::POST], '/config')->action(\App\Admin\Config\Action::class)->name('admin/config'),
            Route::get('/categories')->action(\App\Admin\CategoryList\Action::class)->name('admin/category/list'),
            Route::methods([Method::GET, Method::POST], '/category/create')->action(\App\Admin\CategoryForm\Action::class)->name('admin/category/create'),
            Route::methods([Method::GET, Method::POST], '/category/update/{id:\d+}')->action(\App\Admin\CategoryForm\Action::class)->name('admin/category/update'),
            Route::post('/category/delete/{id:\d+}')->action(\App\Admin\CategoryDelete\Action::class)->name('admin/category/delete'),
            Route::get('/navs')->action(\App\Admin\NavList\Action::class)->name('admin/nav/list'),
            Route::methods([Method::GET, Method::POST], '/nav/create')->action(\App\Admin\NavForm\Action::class)->name('admin/nav/create'),
            Route::methods([Method::GET, Method::POST], '/nav/update/{id:\d+}')->action(\App\Admin\NavForm\Action::class)->name('admin/nav/update'),
            Route::post('/nav/delete/{id:\d+}')->action(\App\Admin\NavDelete\Action::class)->name('admin/nav/delete'),
            Route::get('/tags')->action(\App\Admin\TagList\Action::class)->name('admin/tag/list'),
            Route::post('/tag/delete/{name}')->action(\App\Admin\TagList\Action::class)->name('admin/tag/delete'),
            Route::get('/logs')->action(\App\Admin\LogList\Action::class)->name('admin/log/list'),
            Route::get('/logs/page/{page:\d+}')->action(\App\Admin\LogList\Action::class)->name('admin/log/list-page'),
            Route::post('/logs/clear')->action(\App\Admin\LogList\Action::class)->name('admin/log/clear'),
            Route::get('/users')->action(\App\Admin\UserList\Action::class)->name('admin/user/list'),
            Route::get('/users/page/{page:\d+}')->action(\App\Admin\UserList\Action::class)->name('admin/user/list-page'),
            Route::post('/user/{action}/{id:\d+}')->action(\App\Admin\UserList\Action::class)->name('admin/user/action'),
            Route::post('/upload/image')->action(\App\Admin\Upload\Action::class)->name('admin/upload/image'),
        ),

    // 后台 JSON API（SPA 前端）：独立守卫（401/403 JSON）+ 共享全局 CSRF 校验
    Group::create('/admin/api')
        ->middleware(\App\Admin\Api\AdminApiGuardMiddleware::class, \App\Admin\Api\JsonBodyParserMiddleware::class)
        ->routes(
            Route::get('/me')->action(\App\Admin\Api\Me\Action::class)->name('admin/api/me'),
            Route::get('/dashboard')->action(\App\Admin\Api\Dashboard\Action::class)->name('admin/api/dashboard'),
            Route::get('/posts')->action(\App\Admin\Api\PostList\Action::class)->name('admin/api/post/list'),
            Route::get('/posts/page/{page:\d+}')->action(\App\Admin\Api\PostList\Action::class)->name('admin/api/post/list-page'),
            Route::get('/post/{id:\d+}')->action([\App\Admin\Api\PostForm\Action::class, 'detail'])->name('admin/api/post/detail'),
            Route::post('/post/save')->action([\App\Admin\Api\PostForm\Action::class, 'save'])->name('admin/api/post/save'),
            Route::post('/post/update/{id:\d+}')->action([\App\Admin\Api\PostForm\Action::class, 'update'])->name('admin/api/post/update'),
            Route::post('/post/delete/{id:\d+}')->action(\App\Admin\Api\PostDelete\Action::class)->name('admin/api/post/delete'),
            Route::get('/comments')->action(\App\Admin\Api\CommentList\Action::class)->name('admin/api/comment/list'),
            Route::get('/comments/page/{page:\d+}')->action(\App\Admin\Api\CommentList\Action::class)->name('admin/api/comment/list-page'),
            Route::get('/comment/{id:\d+}')->action([\App\Admin\Api\CommentForm\Action::class, 'detail'])->name('admin/api/comment/detail'),
            Route::post('/comment/update/{id:\d+}')->action([\App\Admin\Api\CommentForm\Action::class, 'update'])->name('admin/api/comment/update'),
            Route::post('/comment/{action}/{id:\d+}')->action(\App\Admin\Api\CommentAction\Action::class)->name('admin/api/comment/action'),
            Route::get('/categories')->action(\App\Admin\Api\CategoryList\Action::class)->name('admin/api/category/list'),
            Route::get('/category/{id:\d+}')->action([\App\Admin\Api\CategoryForm\Action::class, 'detail'])->name('admin/api/category/detail'),
            Route::post('/category/save')->action([\App\Admin\Api\CategoryForm\Action::class, 'save'])->name('admin/api/category/save'),
            Route::post('/category/update/{id:\d+}')->action([\App\Admin\Api\CategoryForm\Action::class, 'update'])->name('admin/api/category/update'),
            Route::post('/category/delete/{id:\d+}')->action(\App\Admin\Api\CategoryDelete\Action::class)->name('admin/api/category/delete'),
            Route::get('/custom-configs/categories')->action([\App\Admin\Api\CustomConfigList\Action::class, 'categories'])->name('admin/api/custom-config/categories'),
            Route::get('/custom-configs')->action([\App\Admin\Api\CustomConfigList\Action::class, 'list'])->name('admin/api/custom-config/list'),
            Route::get('/custom-config/{id:\d+}')->action([\App\Admin\Api\CustomConfigForm\Action::class, 'detail'])->name('admin/api/custom-config/detail'),
            Route::post('/custom-config/save')->action([\App\Admin\Api\CustomConfigForm\Action::class, 'save'])->name('admin/api/custom-config/save'),
            Route::post('/custom-config/update/{id:\d+}')->action([\App\Admin\Api\CustomConfigForm\Action::class, 'update'])->name('admin/api/custom-config/update'),
            Route::post('/custom-config/delete/{id:\d+}')->action([\App\Admin\Api\CustomConfigForm\Action::class, 'delete'])->name('admin/api/custom-config/delete'),
            Route::get('/navs')->action(\App\Admin\Api\NavList\Action::class)->name('admin/api/nav/list'),
            Route::get('/nav/{id:\d+}')->action([\App\Admin\Api\NavForm\Action::class, 'detail'])->name('admin/api/nav/detail'),
            Route::post('/nav/save')->action([\App\Admin\Api\NavForm\Action::class, 'save'])->name('admin/api/nav/save'),
            Route::post('/nav/update/{id:\d+}')->action([\App\Admin\Api\NavForm\Action::class, 'update'])->name('admin/api/nav/update'),
            Route::post('/nav/delete/{id:\d+}')->action(\App\Admin\Api\NavDelete\Action::class)->name('admin/api/nav/delete'),
            Route::get('/tags')->action([\App\Admin\Api\TagList\Action::class, 'list'])->name('admin/api/tag/list'),
            Route::post('/tag/delete/{name}')->action([\App\Admin\Api\TagList\Action::class, 'delete'])->name('admin/api/tag/delete'),
            Route::get('/users')->action([\App\Admin\Api\UserList\Action::class, 'list'])->name('admin/api/user/list'),
            Route::get('/users/page/{page:\d+}')->action([\App\Admin\Api\UserList\Action::class, 'list'])->name('admin/api/user/list-page'),
            Route::post('/user/update/{id:\d+}')->action([\App\Admin\Api\UserList\Action::class, 'update'])->name('admin/api/user/update'),
            Route::post('/user/{action}/{id:\d+}')->action([\App\Admin\Api\UserList\Action::class, 'toggle'])->name('admin/api/user/action'),
            Route::get('/logs')->action([\App\Admin\Api\LogList\Action::class, 'list'])->name('admin/api/log/list'),
            Route::get('/logs/page/{page:\d+}')->action([\App\Admin\Api\LogList\Action::class, 'list'])->name('admin/api/log/list-page'),
            Route::post('/logs/clear')->action([\App\Admin\Api\LogList\Action::class, 'clear'])->name('admin/api/log/clear'),
            Route::get('/config')->action([\App\Admin\Api\Config\Action::class, 'read'])->name('admin/api/config'),
            Route::post('/config/save')->action([\App\Admin\Api\Config\Action::class, 'save'])->name('admin/api/config/save'),
            Route::get('/cache')->action([\App\Admin\Api\Cache\Action::class, 'status'])->name('admin/api/cache'),
            Route::post('/cache/clear')->action([\App\Admin\Api\Cache\Action::class, 'clear'])->name('admin/api/cache/clear'),
            Route::get('/env')->action(\App\Admin\Api\Env\Action::class)->name('admin/api/env'),
        ),
];
