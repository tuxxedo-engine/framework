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

namespace Tuxxedo\Database\Query\Statement\Join;

use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;

interface JoinConditionInterface
{
    public function whereColumn(
        string $column,
        string $other,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    public function orWhereColumn(
        string $column,
        string $other,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    public function where(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    public function orWhere(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    public function whereNull(
        string $column,
    ): static;

    public function whereNotNull(
        string $column,
    ): static;

    /**
     * @param non-empty-array<string|int|float|bool|null> $values
     */
    public function whereIn(
        string $column,
        array $values,
    ): static;

    public function whereColumnStartsWith(
        string $column,
        string $prefixColumn,
    ): static;

    public function orWhereColumnStartsWith(
        string $column,
        string $prefixColumn,
    ): static;

    public function whereStartsWith(
        string $column,
        string $prefix,
    ): static;

    public function orWhereStartsWith(
        string $column,
        string $prefix,
    ): static;

    /**
     * @param \Closure(JoinConditionInterface): void $callback
     */
    public function whereGroup(
        \Closure $callback,
    ): static;

    /**
     * @param \Closure(JoinConditionInterface): void $callback
     */
    public function orWhereGroup(
        \Closure $callback,
    ): static;
}
