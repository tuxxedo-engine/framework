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

/**
 * @template TModel of object
 *
 * @extends \IteratorAggregate<int, TModel>
 * @extends \ArrayAccess<int, TModel>
 */
interface RelationInterface extends \IteratorAggregate, \Countable, \ArrayAccess
{
    public int $totalCount {
        get;
    }

    /**
     * @return \Generator<int, TModel>
     */
    public function getIterator(): \Generator;

    #[\NoDiscard]
    public function isMaterialized(): bool;
}
