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
            // Home
            Route::get('/')->action(Web\HomePage\Action::class)->name('site/index'),
            Route::get('/page/{page:\d+}')->action(Web\HomePage\Action::class)->name('site/index-page'),

            // Auth
            Route::methods([Method::GET, Method::POST], '/login')->action(Web\Login\Action::class)->name('site/login'),
            Route::get('/logout')->action(Web\Logout\Action::class)->name('site/logout'),
            Route::methods([Method::GET, Method::POST], '/register')->action(Web\Register\Action::class)->name('site/register'),
            Route::get('/site/captcha')->action(Web\Placeholder\NotFoundAction::class)->name('site/captcha'),

            // Categories
            Route::get('/catalog/{alias}')->action(Web\CategoryShow\Action::class)->name('category/show'),
            Route::get('/catalog/{alias}/page/{page:\d+}')->action(Web\CategoryShow\Action::class)->name('category/show-page'),

            // Posts
            Route::get('/archive/{alias}')->action(Web\PostShow\Action::class)->name('post/show'),
            Route::get('/posts')->action(Web\PostList\Action::class)->name('post/list'),
            Route::get('/posts/page/{page:\d+}')->action(Web\PostList\Action::class)->name('post/list-page'),
            Route::get('/post/{id:\d+}')->action(Web\Placeholder\NotFoundAction::class)->name('post/view'),
            Route::get('/archives')->action(Web\Archives\Action::class)->name('post/archives'),
            Route::get('/archives/{year:\d{4}}/{month:\d{1,2}}')->action(Web\ArchivesDate\Action::class)->name('post/archives-date'),

            // Tags
            Route::get('/tag/{name}')->action(Web\TagShow\Action::class)->name('tag/show'),
            Route::get('/tag/{name}/page/{page:\d+}')->action(Web\TagShow\Action::class)->name('tag/show-page'),
            Route::get('/tags')->action(Web\TagList\Action::class)->name('tag/list'),

            // Users (static paths before /user/{name} — match order is significant)
            Route::get('/user/profile/{name}')->action(Web\Placeholder\NotFoundAction::class)->name('user/profile'),
            Route::get('/user/profile')->action(Web\Placeholder\NotFoundAction::class)->name('user/profile-me'),
            Route::get('/user/modify-password')->action(Web\Placeholder\NotFoundAction::class)->name('user/modify-password'),
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
];
