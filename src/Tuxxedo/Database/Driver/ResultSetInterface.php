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
// @todo May need more methods for various fetching mechanisms
interface ResultSetInterface extends \Countable, \Iterator
{
    public int $affectedRows {
        get;
    }

    /**
     * @return array<ResultRowInterface>
     */
    public function fetchAll(): array;

    /**
     * @return \Generator<ResultRowInterface>
     */
    public function fetch(): \Generator;
}
