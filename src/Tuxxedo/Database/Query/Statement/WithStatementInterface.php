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

namespace Tuxxedo\Database\Query\Statement;

use Tuxxedo\Database\SqlException;

interface WithStatementInterface
{
    /**
     * @param list<string> $columns
     *
     * @throws SqlException
     */
    public function with(
        string $name,
        SubqueryRenderableInterface $query,
        array $columns = [],
    ): static;

    /**
     * @param list<string> $columns
     *
     * @throws SqlException
     */
    public function withRecursive(
        string $name,
        SubqueryRenderableInterface $anchor,
        SubqueryRenderableInterface $recursive,
        array $columns = [],
        bool $distinct = false,
    ): static;
}
