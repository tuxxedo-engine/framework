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

use Tuxxedo\Database\Query\WhereOperator;

interface WhereBuilderInterface extends BuilderInterface
{
    public function hasConstraints(): bool;

    /**
     * @param string|int|float|bool|null|array<string|int|float|bool|null> $value
     */
    public function where(
        string $column,
        string|int|float|bool|null|array $value,
        WhereOperator|string $operator = WhereOperator::EQUALS,
    ): static;

    /**
     * @param string|int|float|bool|null|array<string|int|float|bool|null> $value
     */
    public function orWhere(
        string $column,
        string|int|float|bool|null|array $value,
        WhereOperator|string $operator = WhereOperator::EQUALS,
    ): static;

    public function whereNull(
        string $column,
    ): static;

    public function whereNotNull(
        string $column,
    ): static;

    /**
     * @param array<string|int|float|bool|null> $values
     */
    public function whereIn(
        string $column,
        array $values,
    ): static;

    /**
     * @param array<string|int|float|bool|null> $values
     */
    public function whereNotIn(
        string $column,
        array $values,
    ): static;

    public function innerJoin(
        string $table,
        string $first,
        string $second,
        WhereOperator|string $operator = WhereOperator::EQUALS,
    ): static;

    public function leftJoin(
        string $table,
        string $first,
        string $second,
        WhereOperator|string $operator = WhereOperator::EQUALS,
    ): static;

    public function rightJoin(
        string $table,
        string $first,
        string $second,
        WhereOperator|string $operator = WhereOperator::EQUALS,
    ): static;

    public function crossJoin(
        string $table,
    ): static;
}
