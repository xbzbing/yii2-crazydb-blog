<?php

declare(strict_types=1);

namespace App\Admin\Api;

use App\Post\Post;

/**
 * 后台 JSON API 数据序列化辅助：把 AR 对象映射为安全的 JSON 数组
 * （剔除敏感字段：post 的 content/excerpt/password/cover，user 的 password/auth_key/ext 等）。
 */
final class ApiSerializer
{
    /**
     * @param list<Post> $posts
     * @return list<array<string, mixed>>
     */
    public static function posts(array $posts): array
    {
        return array_map(static fn (Post $p): array => [
            'id' => (int)$p->id,
            'cid' => (int)$p->cid,
            'author_id' => (int)$p->author_id,
            'author_name' => $p->author_name,
            'type' => $p->type,
            'title' => $p->title,
            'alias' => $p->alias,
            'format' => $p->format,
            'status' => $p->status,
            'tags' => $p->tags,
            'comment_count' => (int)$p->comment_count,
            'view_count' => (int)$p->view_count,
            'is_top' => (int)$p->is_top,
            'post_time' => (int)$p->post_time,
            'create_time' => (int)$p->create_time,
            'update_time' => (int)$p->update_time,
        ], $posts);
    }

    /**
     * 单篇文章（表单编辑用，含 content/excerpt）。
     *
     * @return array<string, mixed>
     */
    public static function postDetail(Post $post): array
    {
        return [
            'id' => (int)$post->id,
            'cid' => (int)$post->cid,
            'author_id' => (int)$post->author_id,
            'author_name' => $post->author_name,
            'type' => $post->type,
            'title' => $post->title,
            'alias' => $post->alias,
            'excerpt' => (string)$post->excerpt,
            'content' => (string)$post->content,
            'format' => $post->format,
            'status' => $post->status,
            'tags' => $post->tags,
            'password' => (string)$post->password,
            'cover' => (string)$post->cover,
            'is_top' => (int)$post->is_top,
            'post_time' => (int)$post->post_time,
        ];
    }

    /**
     * @param list<\App\User\User> $users
     * @return list<array<string, mixed>>
     */
    public static function users(array $users): array
    {
        return array_map(static fn (\App\User\User $u): array => [
            'id' => (int)$u->id,
            'username' => $u->username,
            'nickname' => $u->nickname,
            'email' => $u->email,
            'website' => $u->website,
            'avatar' => $u->avatar,
            'role' => (int)$u->role,
            'status' => (int)$u->status,
            'register_time' => (int)$u->register_time,
            'update_time' => (int)$u->update_time,
            'active_time' => (int)$u->active_time,
            'info' => $u->info,
        ], $users);
    }

    /**
     * @param list<\App\Comment\Comment> $comments
     * @return list<array<string, mixed>>
     */
    public static function comments(array $comments): array
    {
        return array_map(static fn (\App\Comment\Comment $c): array => self::comment($c), $comments);
    }

    /**
     * 单条评论（详情/编辑用，含邮箱/网址/IP/UA）。
     *
     * @return array<string, mixed>
     */
    public static function comment(\App\Comment\Comment $c): array
    {
        return [
            'id' => (int)$c->id,
            'pid' => (int)$c->pid,
            'uid' => $c->uid,
            'nickname' => $c->nickname,
            'email' => $c->email,
            'url' => $c->url,
            'ip' => $c->ip,
            'user_agent' => $c->user_agent,
            'reply_to' => $c->reply_to,
            'content' => (string)$c->content,
            'status' => $c->status,
            'create_time' => (int)$c->create_time,
            'update_time' => (int)$c->update_time,
        ];
    }

    /**
     * @param list<\App\Category\Category> $categories
     * @return list<array<string, mixed>>
     */
    public static function categories(array $categories): array
    {
        return array_map(static fn (\App\Category\Category $c): array => [
            'id' => (int)$c->id,
            'pid' => (int)$c->pid,
            'name' => $c->name,
            'alias' => $c->alias,
            'desc' => $c->desc,
            'display' => $c->display,
            'sort_order' => (int)$c->sort_order,
            'keywords' => $c->keywords,
            'update_time' => (int)$c->update_time,
        ], $categories);
    }

    /**
     * @param list<\App\Nav\Nav> $navs
     * @return list<array<string, mixed>>
     */
    public static function navs(array $navs): array
    {
        return array_map(static fn (\App\Nav\Nav $n): array => [
            'id' => (int)$n->id,
            'pid' => (int)$n->pid,
            'name' => $n->name,
            'url' => $n->url,
            'route' => (int)$n->route,
            'sort_order' => (int)$n->sort_order,
            'create_time' => (int)$n->create_time,
            'update_time' => (int)$n->update_time,
        ], $navs);
    }

    /**
     * @param list<\App\Log\Log> $logs
     * @return list<array<string, mixed>>
     */
    public static function logs(array $logs): array
    {
        return array_map(static fn (\App\Log\Log $l): array => [
            'id' => (int)$l->id,
            'uid' => (int)$l->uid,
            'type' => $l->type,
            'action' => $l->action,
            'result' => $l->result,
            'detail' => $l->detail,
            'create_time' => (int)$l->create_time,
        ], $logs);
    }
}
