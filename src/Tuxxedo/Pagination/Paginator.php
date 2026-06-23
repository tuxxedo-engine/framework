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
 * @implements PaginatorInterface<TItem>
 */
class Paginator implements PaginatorInterface
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

    /**
     * @return list<int>
     */
    public function pageRange(
        int $window = 5,
    ): array {
        $totalPages = $this->totalPages;

        if ($totalPages === 0) {
            return [];
        }

        $window = \max(1, $window);

        if ($window >= $totalPages) {
            return \range(1, $totalPages);
        }

        $halfWindow = \intdiv($window, 2);
        $start = $this->page - $halfWindow;
        $end = $start + $window - 1;

        if ($start < 1) {
            $end += 1 - $start;
            $start = 1;
        }

        if ($end > $totalPages) {
            $start -= $end - $totalPages;
            $end = $totalPages;
        }

        return \range($start, $end);
    }
}
