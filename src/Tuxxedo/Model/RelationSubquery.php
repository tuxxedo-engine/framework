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

use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Query\Statement\SelectStatement;
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;

class RelationSubquery extends SelectStatement implements RelationSubqueryInterface
{
    /**
     * @var (\Closure(string, (\Closure(RelationSubqueryInterface): void)|null, bool): SelectStatementInterface)|null
     */
    private ?\Closure $existsFactory = null;

    /**
     * @param \Closure(string, (\Closure(RelationSubqueryInterface): void)|null, bool): SelectStatementInterface $existsFactory
     */
    public static function createForRelation(
        string $table,
        ConnectionInterface $connection,
        \Closure $existsFactory,
    ): static {
        $subquery = new static(
            table: $table,
            connection: $connection,
        );

        $subquery->existsFactory = $existsFactory;

        return $subquery;
    }

    public function whereHas(
        string $relationName,
        ?\Closure $callback = null,
        bool $includeDeleted = false,
    ): static {
        return $this->whereExists(
            $this->buildExistsSubquery(
                relationName: $relationName,
                callback: $callback,
                includeDeleted: $includeDeleted,
            ),
        );
    }

    public function whereDoesntHave(
        string $relationName,
        ?\Closure $callback = null,
        bool $includeDeleted = false,
    ): static {
        return $this->whereNotExists(
            $this->buildExistsSubquery(
                relationName: $relationName,
                callback: $callback,
                includeDeleted: $includeDeleted,
            ),
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     *
     * @throws ModelException
     */
    private function buildExistsSubquery(
        string $relationName,
        ?\Closure $callback,
        bool $includeDeleted,
    ): SelectStatementInterface {
        if ($this->existsFactory === null) {
            throw ModelException::fromChainMethodRequiresModelContext(
                method: 'whereHas',
            );
        }

        return ($this->existsFactory)($relationName, $callback, $includeDeleted);
    }
}
