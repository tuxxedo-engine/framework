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
     * @return array<ResultRowInterface>
     */
    public function fetchAllAsArray(): array;

    /**
     * @return \Generator<ResultRowInterface>
     */
    public function fetchAllAsGenerator(): \Generator;

    public function fetch(): ResultRowInterface;

    // @todo Consider a $class argument for DTO mapping (MapperInterface?)
    public function fetchObject(): ResultRowInterface;

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

    public function free(): void;
}
