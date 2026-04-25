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

namespace Tuxxedo\Database\Driver;

/**
 * @extends \Iterator<array-key, ResultRowInterface>
 */
interface ResultSetInterface extends \Countable, \Iterator
{
    public int $affectedRows {
        get;
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|class-string<TClassName&HydratableInterface>|\Closure(mixed[] $properties): TClassName $class
     * @return \Generator<TClassName>
     */
    public function fetchAll(
        string|\Closure $class = ResultRowInterface::class,
    ): \Generator;

    public function fetch(): ResultRowInterface;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|class-string<TClassName&HydratableInterface>|\Closure(mixed[] $properties): TClassName $class
     * @return TClassName
     */
    public function fetchObject(
        string|\Closure $class = ResultRowInterface::class,
    ): object;

    /**
     * @return mixed[]
     */
    public function fetchArray(): array;

    /**
     * @return mixed[]
     */
    public function fetchAssoc(): array;

    /**
     * @return array<int, mixed>
     */
    public function fetchRow(): array;
}
