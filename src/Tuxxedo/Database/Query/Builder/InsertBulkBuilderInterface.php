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

namespace Tuxxedo\Database\Query\Builder;

use Tuxxedo\Database\SqlException;

interface InsertBulkBuilderInterface extends BuilderInterface
{
    /**
     * @param non-empty-array<string, string|int|float|bool|null> ...$rows
     *
     * @throws SqlException
     */
    public function values(
        array ...$rows,
    ): static;
}
