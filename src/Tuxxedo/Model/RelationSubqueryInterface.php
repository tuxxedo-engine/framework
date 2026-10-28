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

use Tuxxedo\Database\Query\Statement\SelectStatementInterface;

interface RelationSubqueryInterface extends SelectStatementInterface
{
    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     *
     * @throws ModelException
     */
    public function whereHas(
        string $relationName,
        ?\Closure $callback = null,
        bool $includeDeleted = false,
    ): static;

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     *
     * @throws ModelException
     */
    public function whereDoesntHave(
        string $relationName,
        ?\Closure $callback = null,
        bool $includeDeleted = false,
    ): static;
}
