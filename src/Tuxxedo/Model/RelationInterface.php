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

namespace Tuxxedo\Model;

// @todo Add orderBy chain method so the criteria stack can express ordering; mirrors the matching TODO on QueryableInterface
/**
 * @template TModel of object
 *
 * @extends QueryableInterface<TModel>
 */
interface RelationInterface extends QueryableInterface
{
    /**
     * @var array<int, TModel>
     */
    public array $pendingAdds {
        get;
    }

    /**
     * @var array<int, TModel>
     */
    public array $pendingRemoves {
        get;
    }

    /**
     * @param TModel $item
     */
    public function add(
        object $item,
    ): void;

    /**
     * @param TModel $item
     */
    public function remove(
        object $item,
    ): void;

    public function clearPending(): void;
}
