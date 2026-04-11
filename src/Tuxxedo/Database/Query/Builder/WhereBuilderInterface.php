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

interface WhereBuilderInterface extends BuilderInterface
{
    public function hasConstraints(): bool;
    public function hasConditionConstraints(): bool;
    public function hasJoinConstraints(): bool;

    /**
     * @param string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    public function where(
        string $column,
        string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    /**
     * @param string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    public function orWhere(
        string $column,
        string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    public function orWhereNull(
        string $column,
    ): static;

    public function orWhereNotNull(
        string $column,
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

    /**
     * @param non-empty-array<string|int|float|bool|null> $values
     */
    public function whereNotIn(
        string $column,
        array $values,
    ): static;

    public function innerJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static;

    public function leftJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static;

    public function rightJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static;

    public function crossJoin(
        string $table,
    ): static;

    public function whereBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static;

    public function whereNotBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static;
}
