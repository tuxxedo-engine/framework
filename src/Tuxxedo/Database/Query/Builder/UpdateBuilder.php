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

class UpdateBuilder extends AbstractWhereBuilder implements UpdateBuilderInterface
{
    /**
     * @var array<string, string>
     */
    private array $columns = [];

    /**
     * @var array<string, string>
     */
    private array $expressions = [];

    protected function generateSql(): string
    {
        $clauses = [];

        foreach ($this->columns as $parameterKey => $identifier) {
            $clauses[] = \sprintf(
                '%s = :%s',
                $identifier,
                $parameterKey,
            );
        }

        foreach ($this->expressions as $identifier => $expression) {
            $clauses[] = \sprintf(
                '%s = %s',
                $identifier,
                $expression,
            );
        }

        return \sprintf(
            'UPDATE %s SET %s%s',
            $this->connection->dialect->identifier($this->table),
            \join(', ', $clauses),
            $this->generateWhereSql(),
        );
    }

    public function set(
        string $column,
        string|int|float|bool|null $value,
    ): static {
        $parameterKey = 'set_' . \sizeof($this->columns);

        $this->columns[$parameterKey] = $this->connection->dialect->identifier($column);
        $this->parameters[$parameterKey] = $value;

        return $this;
    }

    public function increment(
        string $column,
        int|float $amount = 1,
    ): static {
        $identifier = $this->connection->dialect->identifier($column);

        $this->expressions[$identifier] = \sprintf(
            '%s + %s',
            $identifier,
            \is_int($amount)
                ? (string) $amount
                : \rtrim(\sprintf('%.10F', $amount), '0'),
        );

        return $this;
    }

    public function decrement(
        string $column,
        int|float $amount = 1,
    ): static {
        $identifier = $this->connection->dialect->identifier($column);

        $this->expressions[$identifier] = \sprintf(
            '%s - %s',
            $identifier,
            \is_int($amount)
                ? (string) $amount
                : \rtrim(\sprintf('%.10F', $amount), '0'),
        );

        return $this;
    }
}
