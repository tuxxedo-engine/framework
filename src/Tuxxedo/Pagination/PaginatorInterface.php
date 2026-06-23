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
 * @extends \IteratorAggregate<int, TItem>
 */
interface PaginatorInterface extends \IteratorAggregate
{
    /**
     * @var PagedInterface<TItem>
     */
    public PagedInterface $paged {
        get;
    }

    /**
     * @var int<1, max>
     */
    public int $page {
        get;
    }

    /**
     * @var int<1, max>
     */
    public int $perPage {
        get;
    }

    /**
     * @var int<0, max>
     */
    public int $totalCount {
        get;
    }

    public int $totalPages {
        get;
    }

    public bool $hasNextPage {
        get;
    }

    public int $nextPage {
        get;
    }

    public bool $hasPreviousPage {
        get;
    }

    public int $previousPage {
        get;
    }

    /**
     * @return \Generator<int, TItem>
     */
    public function getIterator(): \Generator;

    /**
     * @return list<int>
     */
    public function pageRange(
        int $window = 5,
    ): array;
}
