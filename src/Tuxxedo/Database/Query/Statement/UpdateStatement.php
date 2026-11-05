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

use Tuxxedo\Database\Query\Dialect\DialectInterface;

class UpdateStatement extends AbstractWhereStatement implements UpdateStatementInterface
{
    /**
     * @var array<string, string>
     */
    private array $columns = [];

    /**
     * @var array<string, string>
     */
    private array $expressions = [];

    protected function generateSql(
        DialectInterface $dialect,
    ): string {
        $clauses = [];

        foreach ($this->columns as $parameterKey => $column) {
            $clauses[] = \sprintf(
                '%s = :%s',
                $dialect->identifier($column),
                $parameterKey,
            );
        }

        foreach ($this->expressions as $column => $fragment) {
            $identifier = $dialect->identifier($column);

            $clauses[] = \sprintf(
                '%s = %s %s',
                $identifier,
                $identifier,
                $fragment,
            );
        }

        return \sprintf(
            '%sUPDATE %s SET %s%s',
            $this->generateWithSql($dialect),
            $dialect->identifier($this->table),
            \join(', ', $clauses),
            $this->generateWhereSql($dialect),
        );
    }

    public function with(
        string $name,
        SubqueryRenderableInterface $query,
        array $columns = [],
    ): static {
        $this->addCommonTableExpression(
            name: $name,
            query: $query,
            columns: $columns,
        );

        return $this;
    }

    public function withRecursive(
        string $name,
        SubqueryRenderableInterface $anchor,
        SubqueryRenderableInterface $recursive,
        array $columns = [],
        bool $distinct = false,
    ): static {
        $this->addCommonTableExpression(
            name: $name,
            query: $anchor,
            recursive: $recursive,
            columns: $columns,
            distinct: $distinct,
        );

        return $this;
    }

    public function set(
        string $column,
        string|int|float|bool|null $value,
    ): static {
        $parameterKey = 'set_' . \sizeof($this->columns);

        $this->columns[$parameterKey] = $column;
        $this->parameters[$parameterKey] = $value;

        return $this;
    }

    public function increment(
        string $column,
        int|float $amount = 1,
    ): static {
        $this->expressions[$column] = \sprintf(
            '+ %s',
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
        $this->expressions[$column] = \sprintf(
            '- %s',
            \is_int($amount)
                ? (string) $amount
                : \rtrim(\sprintf('%.10F', $amount), '0'),
        );

        return $this;
    }
}
