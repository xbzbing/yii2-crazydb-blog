<?php

declare(strict_types=1);

namespace App\Web;

/**
 * 轻量分页计算（1-based），供前台列表页生成翻页链接。
 */
final readonly class Pager
{
    public int $pageCount;
    public int $pageSize;
    public int $offset;
    public int $currentPage;

    public function __construct(
        public int $totalCount,
        int $pageSize = 10,
        int $currentPage = 1,
    ) {
        $this->pageSize = max(1, $pageSize);
        $this->pageCount = max(1, (int)ceil($totalCount / $this->pageSize));
        $this->currentPage = min(max(1, $currentPage), $this->pageCount);
        $this->offset = ($this->currentPage - 1) * $pageSize;
    }

    public function hasPrev(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->pageCount;
    }

    /**
     * 翻页数字范围（首页/上一页/页码/下一页/末页的完整集合，供模板直出）。
     *
     * @return list<int>
     */
    public function pages(int $around = 2): array
    {
        $start = max(1, $this->currentPage - $around);
        $end = min($this->pageCount, $this->currentPage + $around);
        $pages = [];
        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }
        return $pages;
    }
}
