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
 */
interface PagedInterface
{
    /**
     * @var int<0, max>
     */
    public int $totalCount {
        get;
    }

    /**
     * @return iterable<int, TItem>
     */
    public function slice(
        int $limit,
        int $offset,
    ): iterable;
}
