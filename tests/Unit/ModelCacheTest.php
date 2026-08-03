<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Category\Category;
use App\Nav\Nav;
use App\Tests\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;

final class ModelCacheTest extends TestCase
{
    public function testGetAllCategoriesCacheAndDependencyInvalidation(): void
    {
        $cache = new Cache(new ArrayCache());

        $cat = new Category();
        $cat->name = '__cache_cat__';
        $cat->alias = '__cache_' . bin2hex(random_bytes(4));
        $cat->save();
        try {
            $first = Category::getAllCategories($cache);
            self::assertSame('__cache_cat__', $first[$cat->id]);

            $second = Category::getAllCategories($cache);
            self::assertSame($first, $second, 'cached result should be reused');

            $cat->name = '__cache_cat_updated__';
            $cat->update_time = time();
            $cat->save();
            $third = Category::getAllCategories($cache);
            self::assertSame('__cache_cat_updated__', $third[$cat->id], 'dependency should invalidate on update_time change');
        } finally {
            $cat->delete();
        }
    }

    public function testGetAllCategoriesRefreshForcesRebuild(): void
    {
        $cache = new Cache(new ArrayCache());

        $cat = new Category();
        $cat->name = '__refresh_cat__';
        $cat->alias = '__refresh_' . bin2hex(random_bytes(4));
        $cat->save();
        try {
            $first = Category::getAllCategories($cache);
            $cat->name = '__refresh_cat_v2__';
            $cat->save(); // update_time 未变 → 依赖不变 → 不 refresh 时保持旧缓存
            self::assertSame('__refresh_cat__', $first[$cat->id]);

            $withRefresh = Category::getAllCategories($cache, true);
            self::assertSame('__refresh_cat_v2__', $withRefresh[$cat->id], 'refresh forces rebuild regardless of dependency');
        } finally {
            $cat->delete();
        }
    }

    public function testGetCategorySummaryIncludesPostCount(): void
    {
        $cache = new Cache(new ArrayCache());

        $cat = new Category();
        $cat->name = '__summary_cat__';
        $cat->alias = '__summary_' . bin2hex(random_bytes(4));
        $cat->save();
        try {
            $summary = Category::getCategorySummary($cache);
            self::assertArrayHasKey('name', $summary[$cat->id]);
            self::assertSame('__summary_cat__', $summary[$cat->id]['name']);
            self::assertArrayHasKey('postCount', $summary[$cat->id]);
            self::assertIsInt($summary[$cat->id]['postCount']);
        } finally {
            $cat->delete();
        }
    }

    public function testGetParentNav(): void
    {
        $cache = new Cache(new ArrayCache());

        $nav = new Nav();
        $nav->name = '__parent_nav_item__';
        $nav->url = '/';
        $nav->sort_order = 100;
        $nav->save();
        try {
            $items = Nav::getParentNav($cache);
            self::assertSame('__parent_nav_item__', $items[$nav->id]);
        } finally {
            $nav->delete();
        }
    }

    public function testGetNavTreeBuildsTreeWithChildren(): void
    {
        $cache = new Cache(new ArrayCache());

        $parent = new Nav();
        $parent->name = '__tree_parent__';
        $parent->url = '/parent';
        $parent->sort_order = 100;
        $parent->save();

        $child = new Nav();
        $child->pid = $parent->id;
        $child->name = '__tree_child__';
        $child->url = '/child';
        $child->sort_order = 100;
        $child->save();
        try {
            $tree = Nav::getNavTree($cache);
            self::assertSame('__tree_parent__', $tree[$parent->id]['label']);
            self::assertSame('/parent', $tree[$parent->id]['url']);
            self::assertSame('__tree_child__', $tree[$parent->id]['items'][0]['label']);
            self::assertSame('/child', $tree[$parent->id]['items'][0]['url'], 'child url should be its own (Yii2 bug fixed)');
        } finally {
            $child->delete();
            $parent->delete();
        }
    }
}
