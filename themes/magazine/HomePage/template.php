<?php

declare(strict_types=1);

use App\Common\XUtils;
use Yiisoft\Html\Html;

/**
 * 墨刊首页：Hero 封面区 + 栏目分区 + 文章网格。
 *
 * @var Yiisoft\View\WebView $this
 * @var App\Shared\ApplicationParams $applicationParams
 * @var list<App\Post\Post> $posts
 * @var ?App\Post\Post $latest
 * @var list<array{category: App\Category\Category, posts: list<App\Post\Post>}> $sections
 * @var App\Web\Pager $pager
 * @var App\Post\MarkdownRenderer $markdownRenderer
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array<string, string|null> $siteConfig
 * @var array<string, string|null> $seoConfig
 * @var array<string, mixed> $navTree
 * @var bool $showSidebar
 * @var array<int, array{name: string, desc: ?string, url: ?string, postCount: int}> $categorySummary
 * @var list<array{totalCount: int, name: string, create_time: int, url: string}> $sidebarTags
 * @var list<array{id: ?int, nickname: string, website: ?string, pid: ?int, post_url: ?string, content: ?string, create_time: ?int, email: string, avatar: string, title: string}> $sidebarComments
 */

$this->setParameter('categorySummary', $categorySummary);
$this->setParameter('sidebarTags', $sidebarTags);
$this->setParameter('sidebarComments', $sidebarComments);
$this->setParameter('showSidebar', $showSidebar);
$this->setParameter('siteConfig', $siteConfig);
$this->setParameter('navTree', $navTree);

$siteName = (string)($siteConfig['site_name'] ?? $applicationParams->name);
$this->setTitle((string)($seoConfig['seo_title'] ?? $siteName));
$this->setParameter('seo_keywords', (string)($seoConfig['seo_keywords'] ?? ''));
$this->setParameter('seo_description', (string)($seoConfig['seo_description'] ?? ''));
?>

<?php if ($latest !== null): ?>
    <section class="hero">
        <div>
            <h1 class="hero-title">
                <a href="<?= Html::encode((string)$latest->getUrl($urlGenerator)) ?>"><?= Html::encode($latest->title) ?></a>
            </h1>
            <p class="hero-deck"><?= Html::encode(mb_strimwidth(strip_tags((string)$latest->excerpt), 0, 120, '...', 'utf-8')) ?></p>
            <div class="hero-meta">
                <span><?= Html::encode($latest->author_name) ?></span>
                <span><?= XUtils::xDateFormatter((int)$latest->post_time) ?></span>
                <span><?= (int)$latest->comment_count ?> 评论</span>
            </div>
        </div>
        <div class="hero-cover">
            <?php if ($latest->cover !== null && $latest->cover !== ''): ?>
                <img src="<?= Html::encode($latest->cover) ?>" alt="">
            <?php else: ?>
                <span>「<?= Html::encode(mb_substr($latest->title, 0, 1, 'utf-8')) ?>」</span>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($sections !== []): ?>
    <div class="section-grid">
        <?php foreach ($sections as $section): ?>
            <section>
                <h2 class="section-header">
                    <a href="<?= Html::encode((string)$section['category']->getUrl($urlGenerator)) ?>"><?= Html::encode($section['category']->name) ?></a>
                </h2>
                <ul class="section-list">
                    <?php foreach ($section['posts'] as $sectionPost): ?>
                        <li>
                            <div class="section-item-title">
                                <a href="<?= Html::encode((string)$sectionPost->getUrl($urlGenerator)) ?>"><?= Html::encode($sectionPost->title) ?></a>
                            </div>
                            <div class="section-item-date"><?= date('Y-m-d', (int)$sectionPost->post_time) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a class="section-more" href="<?= Html::encode((string)$section['category']->getUrl($urlGenerator)) ?>">更多文章 »</a>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $this->render('PostList/postList.php', [
    'posts' => $posts,
    'pager' => $pager,
    'markdownRenderer' => $markdownRenderer,
    'urlGenerator' => $urlGenerator,
    'categorySummary' => $categorySummary,
    'emptyText' => '暂时没有公开的文章发布，请关注本站更新！',
    'route' => 'site/index',
    'pageRoute' => 'site/index-page',
    'routeArgs' => [],
]) ?>
