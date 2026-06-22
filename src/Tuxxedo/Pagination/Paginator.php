<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Pagination;

/**
 * @template TItem of object
 *
 * @implements \IteratorAggregate<int, TItem>
 */
// @todo pageRange(int $window = 5): list<int> - sliding-window helper returning page numbers around the current page, edge-shifted to keep the window full near boundaries, empty list when totalPages is 0. Math primitive only; no ellipsis, URL or theme rendering (those are UI concerns that belong outside the Paginator)
class Paginator implements \IteratorAggregate
{
    /**
     * @var int<1, max>
     */
    public int $page;

    /**
     * @var int<1, max>
     */
    public int $perPage;

    /**
     * @var int<0, max>
     */
    public int $totalCount {
        get {
            return $this->paged->totalCount;
        }
    }

    public int $totalPages {
        get {
            return (int) \ceil($this->paged->totalCount / $this->perPage);
        }
    }

    public bool $hasNextPage {
        get {
            return $this->page < $this->totalPages;
        }
    }

    public int $nextPage {
        get {
            return \min($this->page + 1, $this->totalPages);
        }
    }

    public bool $hasPreviousPage {
        get {
            return $this->page > 1;
        }
    }

    public int $previousPage {
        get {
            return \max($this->page - 1, 1);
        }
    }

    /**
     * @param PagedInterface<TItem> $paged
     */
    public function __construct(
        public readonly PagedInterface $paged,
        int $page,
        int $perPage = 20,
    ) {
        $this->page = \max(1, $page);
        $this->perPage = \max(1, $perPage);
    }

    /**
     * @return \Generator<int, TItem>
     */
    public function getIterator(): \Generator
    {
        yield from $this->paged->slice(
            limit: $this->perPage,
            offset: ($this->page - 1) * $this->perPage,
        );
    }
}
