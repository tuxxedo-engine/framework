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

namespace Fixture\Pagination;

use Tuxxedo\Pagination\PagedInterface;

/**
 * @template TItem of object
 *
 * @implements PagedInterface<TItem>
 */
class ArrayPaged implements PagedInterface
{
    /**
     * @var list<TItem>
     */
    private array $items;

    /**
     * @var int<0, max>
     */
    public int $totalCount {
        get {
            return \sizeof($this->items);
        }
    }

    /**
     * @param list<TItem> $items
     */
    public function __construct(
        array $items,
    ) {
        $this->items = $items;
    }

    /**
     * @return list<TItem>
     */
    public function slice(
        int $limit,
        int $offset,
    ): array {
        return \array_slice($this->items, $offset, $limit);
    }
}
