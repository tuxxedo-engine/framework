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
 * @extends \IteratorAggregate<array-key, TModel>
 */
interface RelationInterface extends \IteratorAggregate, \Countable
{
    /**
     * @return \Generator<array-key, TModel>
     */
    public function getIterator(): \Generator;
}
