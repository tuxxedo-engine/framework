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
// @todo This needs type normalization if non prepared statements is used, e.g. mysqli
interface ResultSetInterface extends \Countable, \Iterator
{
    public int|string $affectedRows {
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
