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

namespace Tuxxedo\Database\Builder;

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\SqlException;

// @todo Implement
interface CountBuilderInterface extends WhereBuilderInterface
{
    public function column(
        string $column = '*',
    ): static;

    /**
     * @throws DatabaseException
     * @throws SqlException
     */
    public function count(): int;
}
