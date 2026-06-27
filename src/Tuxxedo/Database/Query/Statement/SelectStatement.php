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

use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Hydrator\HydratorInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Statement\Condition\BetweenCondition;
use Tuxxedo\Database\Query\Statement\Condition\BetweenConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\BetweenOperator;
use Tuxxedo\Database\Query\Statement\Condition\Condition;
use Tuxxedo\Database\Query\Statement\Condition\ConditionConjunction;
use Tuxxedo\Database\Query\Statement\Condition\ConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\Order\OrderDirection;

class SelectStatement extends AbstractWhereStatement implements SelectStatementInterface
{
    private bool $distinct = false;

    /**
     * @var string[]
     */
    private array $columns = [];

    /**
     * @var array<string, OrderDirection>
     */
    private array $orderBy = [];

    /**
     * @var string[]
     */
    private array $groupBy = [];

    /**
     * @var ConditionInterface[]|BetweenCondition[]
     */
    private array $havingConditions = [];

    private ?int $limit = null;
    private ?int $offset = null;

    protected function generateSql(
        DialectInterface $dialect,
    ): string {
        $renderedColumns = \array_map(
            static fn (string $column): string => $column === '*' || (\str_contains($column, '(') && \str_contains($column, ')'))
                ? $column
                : $dialect->qualifiedIdentifier($column),
            $this->columns,
        );

        $columns = \sizeof($renderedColumns) > 0
            ? \join(', ', $renderedColumns)
            : '*';

        $sql = \sprintf(
            'SELECT %s%s FROM %s%s',
            $this->distinct
                ? 'DISTINCT '
                : '',
            $columns,
            $dialect->identifier($this->table),
            $this->generateWhereSql($dialect),
        );

        if (\sizeof($this->groupBy) > 0) {
            $renderedGroupBy = \array_map(
                static fn (string $column): string => $dialect->identifier($column),
                $this->groupBy,
            );

            $sql .= \sprintf(
                ' GROUP BY %s',
                \join(', ', $renderedGroupBy),
            );
        }

        if (\sizeof($this->havingConditions) > 0) {
            foreach ($this->havingConditions as $index => $condition) {
                $keyword = $index === 0
                    ? 'HAVING' :
                    $condition->conjunction->name;

                if ($condition instanceof BetweenConditionInterface) {
                    $sql .= \sprintf(
                        ' %s %s %s %s AND %s',
                        $keyword,
                        $dialect->qualifiedIdentifier($condition->identifier),
                        $condition->operator->value,
                        $condition->from,
                        $condition->to,
                    );

                    continue;
                }

                if (
                    $condition->operator === ConditionOperator::IS_NULL ||
                    $condition->operator === ConditionOperator::IS_NOT_NULL
                ) {
                    $sql .= \sprintf(
                        ' %s %s %s',
                        $keyword,
                        $dialect->qualifiedIdentifier($condition->identifier),
                        $condition->operator->value,
                    );
                } elseif (
                    $condition->operator === ConditionOperator::IN ||
                    $condition->operator === ConditionOperator::NOT_IN
                ) {
                    $sql .= \sprintf(
                        ' %s %s %s (%s)',
                        $keyword,
                        $dialect->qualifiedIdentifier($condition->identifier),
                        $condition->operator->value,
                        $condition->parameter,
                    );
                } else {
                    $sql .= \sprintf(
                        ' %s %s %s %s',
                        $keyword,
                        $dialect->qualifiedIdentifier($condition->identifier),
                        $condition->operator->value,
                        $condition->parameter,
                    );
                }
            }
        }

        if (\sizeof($this->orderBy) > 0) {
            $clauses = \array_map(
                static fn (string $column, OrderDirection $direction): string => $dialect->identifier($column) . ' ' . $direction->name,
                \array_keys($this->orderBy),
                \array_values($this->orderBy),
            );

            $sql .= \sprintf(
                ' ORDER BY %s',
                \join(', ', $clauses),
            );
        }

        if ($this->limit !== null) {
            $sql .= \sprintf(
                ' LIMIT %d',
                $this->limit,
            );

            if ($this->offset !== null) {
                $sql .= \sprintf(
                    ' OFFSET %d',
                    $this->offset,
                );
            }
        }

        return $sql;
    }

    public function select(
        string ...$columns,
    ): static {
        foreach ($columns as $column) {
            $this->columns[] = $column;
        }

        return $this;
    }

    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }

    public function orderBy(
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
    ): static {
        if (\is_string($direction)) {
            $direction = OrderDirection::from($direction);
        }

        $this->orderBy[$column] = $direction;

        return $this;
    }

    public function groupBy(
        string ...$columns,
    ): static {
        foreach ($columns as $column) {
            $this->groupBy[] = $column;
        }

        return $this;
    }

    public function having(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        if (\is_string($operator)) {
            $operator = ConditionOperator::fromInput($operator);
        }

        $parameterKey = 'having_' . \sizeof($this->havingConditions);

        $this->parameters[$parameterKey] = $value;
        $this->havingConditions[] = new Condition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
            operator: $operator,
            parameter: ':' . $parameterKey,
        );

        return $this;
    }

    public function orHaving(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        if (\is_string($operator)) {
            $operator = ConditionOperator::fromInput($operator);
        }

        $parameterKey = 'having_' . \sizeof($this->havingConditions);

        $this->parameters[$parameterKey] = $value;
        $this->havingConditions[] = new Condition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: $operator,
            parameter: ':' . $parameterKey,
        );

        return $this;
    }

    public function havingIn(
        string $column,
        array $values,
    ): static {
        $parameterKey = 'having_' . \sizeof($this->havingConditions);

        $this->parameters[$parameterKey] = $values;
        $this->havingConditions[] = new Condition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
            operator: ConditionOperator::IN,
            parameter: ':' . $parameterKey . '[]',
        );

        return $this;
    }

    public function havingNotIn(
        string $column,
        array $values,
    ): static {
        $parameterKey = 'having_' . \sizeof($this->havingConditions);

        $this->parameters[$parameterKey] = $values;
        $this->havingConditions[] = new Condition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
            operator: ConditionOperator::NOT_IN,
            parameter: ':' . $parameterKey . '[]',
        );

        return $this;
    }

    public function havingNull(
        string $column,
    ): static {
        $this->havingConditions[] = new Condition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
            operator: ConditionOperator::IS_NULL,
        );

        return $this;
    }

    public function havingNotNull(
        string $column,
    ): static {
        $this->havingConditions[] = new Condition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
            operator: ConditionOperator::IS_NOT_NULL,
        );

        return $this;
    }

    public function orHavingIn(
        string $column,
        array $values,
    ): static {
        $parameterKey = 'having_' . \sizeof($this->havingConditions);

        $this->parameters[$parameterKey] = $values;
        $this->havingConditions[] = new Condition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: ConditionOperator::IN,
            parameter: ':' . $parameterKey . '[]',
        );

        return $this;
    }

    public function orHavingNotIn(
        string $column,
        array $values,
    ): static {
        $parameterKey = 'having_' . \sizeof($this->havingConditions);

        $this->parameters[$parameterKey] = $values;
        $this->havingConditions[] = new Condition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: ConditionOperator::NOT_IN,
            parameter: ':' . $parameterKey . '[]',
        );

        return $this;
    }

    public function orHavingNull(
        string $column,
    ): static {
        $this->havingConditions[] = new Condition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: ConditionOperator::IS_NULL,
        );

        return $this;
    }

    public function orHavingNotNull(
        string $column,
    ): static {
        $this->havingConditions[] = new Condition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: ConditionOperator::IS_NOT_NULL,
        );

        return $this;
    }

    public function havingBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        $parameterKey = 'having_between_' . \sizeof($this->havingConditions);

        $this->parameters[$parameterKey . '_from'] = $from;
        $this->parameters[$parameterKey . '_to'] = $to;
        $this->havingConditions[] = new BetweenCondition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
            operator: BetweenOperator::BETWEEN,
            from: ':' . $parameterKey . '_from',
            to: ':' . $parameterKey . '_to',
        );

        return $this;
    }

    public function havingNotBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        $parameterKey = 'having_between_' . \sizeof($this->havingConditions);

        $this->parameters[$parameterKey . '_from'] = $from;
        $this->parameters[$parameterKey . '_to'] = $to;
        $this->havingConditions[] = new BetweenCondition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
            operator: BetweenOperator::NOT_BETWEEN,
            from: ':' . $parameterKey . '_from',
            to: ':' . $parameterKey . '_to',
        );

        return $this;
    }

    public function orHavingBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        $parameterKey = 'having_between_' . \sizeof($this->havingConditions);

        $this->parameters[$parameterKey . '_from'] = $from;
        $this->parameters[$parameterKey . '_to'] = $to;
        $this->havingConditions[] = new BetweenCondition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: BetweenOperator::BETWEEN,
            from: ':' . $parameterKey . '_from',
            to: ':' . $parameterKey . '_to',
        );

        return $this;
    }

    public function orHavingNotBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        $parameterKey = 'having_between_' . \sizeof($this->havingConditions);

        $this->parameters[$parameterKey . '_from'] = $from;
        $this->parameters[$parameterKey . '_to'] = $to;
        $this->havingConditions[] = new BetweenCondition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: BetweenOperator::NOT_BETWEEN,
            from: ':' . $parameterKey . '_from',
            to: ':' . $parameterKey . '_to',
        );

        return $this;
    }

    public function limit(
        int $limit,
        ?int $offset = null,
    ): static {
        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    public function fetch(
        string|\Closure $class,
        ?HydratorInterface $hydrator = null,
        ?ConnectionInterface $connection = null,
    ): ?object {
        $result = $this->execute($connection);

        if ($result->count() > 0) {
            return $result->fetchObject($class, $hydrator);
        }

        return null;
    }

    public function fetchAll(
        string|\Closure $class,
        ?HydratorInterface $hydrator = null,
        ?ConnectionInterface $connection = null,
    ): \Generator {
        yield from $this->execute($connection)->fetchAll($class, $hydrator);
    }
}
