<?php

declare(strict_types=1);

/**
 * 后台仪表盘。
 *
 * @var Yiisoft\View\WebView $this
 * @var int $postTotal
 * @var int $commentTotal
 * @var int $pendingComments
 * @var int $userTotal
 * @var int $optionTotal
 */

$this->setTitle('仪表盘 - 后台管理');
?>

<h1>仪表盘</h1>

<div class="admin-stats">
    <div class="admin-stat"><span class="stat-num"><?= $postTotal ?></span><span class="stat-label">文章</span></div>
    <div class="admin-stat"><span class="stat-num"><?= $commentTotal ?></span><span class="stat-label">评论</span></div>
    <div class="admin-stat admin-stat-warn"><span class="stat-num"><?= $pendingComments ?></span><span class="stat-label">待审核评论</span></div>
    <div class="admin-stat"><span class="stat-num"><?= $userTotal ?></span><span class="stat-label">用户</span></div>
</div>
