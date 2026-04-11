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

abstract class AbstractWhereBuilder extends AbstractBuilder implements WhereBuilderInterface
{
    protected function generateWhereSql(): string
    {
        // @todo Implement

        return '';
    }

    public function hasConstraints(): bool
    {
        // @todo Implement

        return false;
    }

    /**
     * @param string|int|float|bool|null|array<string|int|float|bool|null> $value
     */
    public function where(
        string $column,
        string|int|float|bool|null|array $value,
        WhereOperator|string $operator = WhereOperator::EQUALS,
    ): static {
        // @todo Implement

        return $this;
    }

    /**
     * @param string|int|float|bool|null|array<string|int|float|bool|null> $value
     */
    public function orWhere(
        string $column,
        string|int|float|bool|null|array $value,
        WhereOperator|string $operator = WhereOperator::EQUALS,
    ): static {
        // @todo Implement

        return $this;
    }

    public function whereNull(
        string $column,
    ): static {
        // @todo Implement

        return $this;
    }

    public function whereNotNull(
        string $column,
    ): static {
        // @todo Implement

        return $this;
    }

    /**
     * @param array<string|int|float|bool|null> $values
     */
    public function whereIn(
        string $column,
        array $values,
    ): static {
        // @todo Implement

        return $this;
    }

    /**
     * @param array<string|int|float|bool|null> $values
     */
    public function whereNotIn(
        string $column,
        array $values,
    ): static {
        // @todo Implement

        return $this;
    }

    public function innerJoin(
        string $table,
        string $first,
        string $second,
        WhereOperator|string $operator = WhereOperator::EQUALS,
    ): static {
        // @todo Implement

        return $this;
    }

    public function leftJoin(
        string $table,
        string $first,
        string $second,
        WhereOperator|string $operator = WhereOperator::EQUALS,
    ): static {
        // @todo Implement

        return $this;
    }

    public function rightJoin(
        string $table,
        string $first,
        string $second,
        WhereOperator|string $operator = WhereOperator::EQUALS,
    ): static {
        // @todo Implement

        return $this;
    }

    public function crossJoin(
        string $table,
    ): static {
        // @todo Implement

        return $this;
    }
}
