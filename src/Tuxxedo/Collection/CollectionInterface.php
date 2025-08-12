<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Collection;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends \Iterator<TKey, TValue>
 * @extends \ArrayAccess<TKey, TValue>
 */
interface CollectionInterface extends \Countable, \Iterator, \ArrayAccess
{
    /**
     * @return TKey|null
     */
    public function firstKey(): mixed;

    /**
     * @return TKey|null
     */
    public function lastKey(): mixed;

    /**
     * @return TValue|null
     */
    public function first(): mixed;

    /**
     * @return TValue|null
     */
    public function last(): mixed;

    public function contains(
        mixed ...$values,
    ): bool;

    public function sort(): static;

    public function sortKeys(): static;

    public function reverse(): static;

    public function reverseKeys(): static;

    /**
     * @return TKey[]
     */
    public function keys(): array;

    /**
     * @return TValue[]
     */
    public function values(): array;

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array;
}
