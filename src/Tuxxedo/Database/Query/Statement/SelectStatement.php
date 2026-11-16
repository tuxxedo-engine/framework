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
use Tuxxedo\Database\Query\Statement\Aggregate\AggregateFunction;
use Tuxxedo\Database\Query\Statement\Condition\AggregateCondition;
use Tuxxedo\Database\Query\Statement\Condition\AggregateConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\BetweenCondition;
use Tuxxedo\Database\Query\Statement\Condition\BetweenConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\BetweenOperator;
use Tuxxedo\Database\Query\Statement\Condition\Condition;
use Tuxxedo\Database\Query\Statement\Condition\ConditionConjunction;
use Tuxxedo\Database\Query\Statement\Condition\ConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\Order\OrderDirection;
use Tuxxedo\Database\Query\Statement\Window\FrameBoundInterface;
use Tuxxedo\Database\Query\Statement\Window\FrameBoundKind;
use Tuxxedo\Database\Query\Statement\Window\FrameUnit;
use Tuxxedo\Database\Query\Statement\Window\Window;
use Tuxxedo\Database\Query\Statement\Window\WindowFunction;
use Tuxxedo\Database\Query\Statement\Window\WindowInterface;
use Tuxxedo\Database\SqlException;

class SelectStatement extends AbstractWhereStatement implements SelectStatementInterface
{
    private bool $distinct = false;
    private ?string $alias = null;
    private ?SubqueryRenderableInterface $fromSubquery = null;

    /**
     * @var list<array{kind: 'column'|'aggregate'|'raw'|'window', expression: string|null, function: AggregateFunction|null, distinct: bool, alias: string|null, window: WindowInterface|null, windowFunction: WindowFunction|null, argument: int|null, default: string|null}>
     */
    private array $columns = [];

    /**
     * @var list<array{subquery: SubqueryRenderableInterface, alias: string}>
     */
    private array $selectSubqueries = [];

    /**
     * @var list<array{expression: string|SubqueryRenderableInterface, direction: OrderDirection}>
     */
    private array $orderBy = [];

    /**
     * @var string[]
     */
    private array $groupBy = [];

    /**
     * @var ConditionInterface[]|BetweenCondition[]|AggregateCondition[]
     */
    private array $havingConditions = [];

    private ?int $limit = null;
    private ?int $offset = null;

    public static function createFromSubquery(
        SubqueryRenderableInterface $subquery,
        string $alias,
        ?ConnectionInterface $connection = null,
    ): static {
        $statement = new static(
            table: $alias,
            connection: $connection,
        );

        $statement->fromSubquery = $subquery;

        return $statement;
    }

    protected function generateSql(
        DialectInterface $dialect,
    ): string {
        $renderedColumns = [];

        foreach ($this->columns as $column) {
            $renderedColumns[] = $this->renderColumn($dialect, $column);
        }

        $allColumns = \sizeof($this->joins) > 0
            ? $dialect->identifier($this->alias ?? $this->table) . '.*'
            : '*';

        if (\sizeof($this->selectSubqueries) > 0 && \sizeof($renderedColumns) === 0) {
            $renderedColumns[] = $allColumns;
        }

        foreach ($this->selectSubqueries as $index => $entry) {
            $renderedColumns[] = '(' . $this->mergeSubquery(
                subquery: $entry['subquery'],
                prefix: 'ssq_' . $index . '_',
                dialect: $dialect,
            ) . ') AS ' . $dialect->identifier($entry['alias']);
        }

        $columns = \sizeof($renderedColumns) > 0
            ? \join(', ', $renderedColumns)
            : $allColumns;

        $sql = \sprintf(
            '%sSELECT %s%s FROM %s%s',
            $this->generateWithSql($dialect),
            $this->distinct
                ? 'DISTINCT '
                : '',
            $columns,
            $this->renderFrom($dialect),
            $this->generateWhereSql($dialect),
        );

        if (\sizeof($this->groupBy) > 0) {
            $renderedGroupBy = \array_map(
                static fn (string $column): string => $dialect->qualifiedIdentifier($column),
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

                if ($condition instanceof AggregateConditionInterface) {
                    $sql .= \sprintf(
                        ' %s %s %s %s',
                        $keyword,
                        $this->renderAggregate($dialect, $condition->function, $condition->column, $condition->distinct),
                        $dialect->conditionOperator($condition->operator),
                        $condition->parameter,
                    );

                    continue;
                }

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
                        $dialect->conditionOperator($condition->operator),
                    );
                } elseif (
                    $condition->operator === ConditionOperator::IN ||
                    $condition->operator === ConditionOperator::NOT_IN
                ) {
                    $sql .= \sprintf(
                        ' %s %s %s (%s)',
                        $keyword,
                        $dialect->qualifiedIdentifier($condition->identifier),
                        $dialect->conditionOperator($condition->operator),
                        $condition->parameter,
                    );
                } else {
                    $sql .= \sprintf(
                        ' %s %s %s %s',
                        $keyword,
                        $dialect->qualifiedIdentifier($condition->identifier),
                        $dialect->conditionOperator($condition->operator),
                        $condition->parameter,
                    );
                }
            }
        }

        if (\sizeof($this->orderBy) > 0) {
            $clauses = [];

            foreach ($this->orderBy as $index => $entry) {
                $expression = $entry['expression'];

                if ($expression instanceof SubqueryRenderableInterface) {
                    $expression = '(' . $this->mergeSubquery(
                        subquery: $expression,
                        prefix: 'osq_' . $index . '_',
                        dialect: $dialect,
                    ) . ')';
                } else {
                    $expression = $dialect->qualifiedIdentifier($expression);
                }

                $clauses[] = $dialect->orderTerm($expression, $entry['direction']);
            }

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

    public function alias(
        string $alias,
    ): static {
        $this->alias = $alias;

        return $this;
    }

    public function select(
        string ...$columns,
    ): static {
        foreach ($columns as $column) {
            $this->columns[] = [
                'kind' => 'column',
                'expression' => $column,
                'function' => null,
                'distinct' => false,
                'alias' => null,
                'window' => null,
                'windowFunction' => null,
                'argument' => null,
                'default' => null,
            ];
        }

        return $this;
    }

    public function selectAs(
        string $column,
        string $alias,
    ): static {
        $this->columns[] = [
            'kind' => 'column',
            'expression' => $column,
            'function' => null,
            'distinct' => false,
            'alias' => $alias,
            'window' => null,
            'windowFunction' => null,
            'argument' => null,
            'default' => null,
        ];

        return $this;
    }

    public function selectAggregate(
        AggregateFunction $function,
        ?string $column = null,
        ?string $alias = null,
        bool $distinct = false,
        ?\Closure $over = null,
    ): static {
        self::guardAggregateColumn($function, $column, $distinct);

        if ($distinct && $over !== null) {
            throw SqlException::fromDistinctWindowAggregate(
                function: $function,
            );
        }

        $this->columns[] = [
            'kind' => 'aggregate',
            'expression' => $column,
            'function' => $function,
            'distinct' => $distinct,
            'alias' => $alias,
            'window' => $over !== null
                ? self::window($over)
                : null,
            'windowFunction' => null,
            'argument' => null,
            'default' => null,
        ];

        return $this;
    }

    public function selectCount(
        ?string $column = null,
        ?string $alias = null,
        bool $distinct = false,
        ?\Closure $over = null,
    ): static {
        return $this->selectAggregate(
            function: AggregateFunction::COUNT,
            column: $column,
            alias: $alias,
            distinct: $distinct,
            over: $over,
        );
    }

    public function selectSum(
        string $column,
        ?string $alias = null,
        bool $distinct = false,
        ?\Closure $over = null,
    ): static {
        return $this->selectAggregate(
            function: AggregateFunction::SUM,
            column: $column,
            alias: $alias,
            distinct: $distinct,
            over: $over,
        );
    }

    public function selectAvg(
        string $column,
        ?string $alias = null,
        bool $distinct = false,
        ?\Closure $over = null,
    ): static {
        return $this->selectAggregate(
            function: AggregateFunction::AVG,
            column: $column,
            alias: $alias,
            distinct: $distinct,
            over: $over,
        );
    }

    public function selectMin(
        string $column,
        ?string $alias = null,
        ?\Closure $over = null,
    ): static {
        return $this->selectAggregate(
            function: AggregateFunction::MIN,
            column: $column,
            alias: $alias,
            over: $over,
        );
    }

    public function selectMax(
        string $column,
        ?string $alias = null,
        ?\Closure $over = null,
    ): static {
        return $this->selectAggregate(
            function: AggregateFunction::MAX,
            column: $column,
            alias: $alias,
            over: $over,
        );
    }

    public function selectWindow(
        WindowFunction $function,
        ?string $alias = null,
        ?\Closure $over = null,
        ?string $column = null,
        ?int $argument = null,
        string|int|float|bool|null $default = null,
    ): static {
        self::guardWindowFunction($function, $column, $argument, $default);

        $placeholder = null;

        if ($default !== null) {
            $parameterKey = 'window_' . \sizeof($this->columns);
            $placeholder = ':' . $parameterKey;

            $this->parameters[$parameterKey] = $default;
        }

        $this->columns[] = [
            'kind' => 'window',
            'expression' => $column,
            'function' => null,
            'distinct' => false,
            'alias' => $alias,
            'window' => self::window($over),
            'windowFunction' => $function,
            'argument' => $argument,
            'default' => $placeholder,
        ];

        return $this;
    }

    public function selectRowNumber(
        ?string $alias = null,
        ?\Closure $over = null,
    ): static {
        return $this->selectWindow(
            function: WindowFunction::ROW_NUMBER,
            alias: $alias,
            over: $over,
        );
    }

    public function selectRank(
        ?string $alias = null,
        ?\Closure $over = null,
    ): static {
        return $this->selectWindow(
            function: WindowFunction::RANK,
            alias: $alias,
            over: $over,
        );
    }

    public function selectDenseRank(
        ?string $alias = null,
        ?\Closure $over = null,
    ): static {
        return $this->selectWindow(
            function: WindowFunction::DENSE_RANK,
            alias: $alias,
            over: $over,
        );
    }

    public function selectPercentRank(
        ?string $alias = null,
        ?\Closure $over = null,
    ): static {
        return $this->selectWindow(
            function: WindowFunction::PERCENT_RANK,
            alias: $alias,
            over: $over,
        );
    }

    public function selectCumeDist(
        ?string $alias = null,
        ?\Closure $over = null,
    ): static {
        return $this->selectWindow(
            function: WindowFunction::CUME_DIST,
            alias: $alias,
            over: $over,
        );
    }

    public function selectNtile(
        int $buckets,
        ?string $alias = null,
        ?\Closure $over = null,
    ): static {
        return $this->selectWindow(
            function: WindowFunction::NTILE,
            alias: $alias,
            over: $over,
            argument: $buckets,
        );
    }

    public function selectLag(
        string $column,
        ?string $alias = null,
        ?\Closure $over = null,
        int $offset = 1,
        string|int|float|bool|null $default = null,
    ): static {
        return $this->selectWindow(
            function: WindowFunction::LAG,
            alias: $alias,
            over: $over,
            column: $column,
            argument: $offset,
            default: $default,
        );
    }

    public function selectLead(
        string $column,
        ?string $alias = null,
        ?\Closure $over = null,
        int $offset = 1,
        string|int|float|bool|null $default = null,
    ): static {
        return $this->selectWindow(
            function: WindowFunction::LEAD,
            alias: $alias,
            over: $over,
            column: $column,
            argument: $offset,
            default: $default,
        );
    }

    public function selectFirstValue(
        string $column,
        ?string $alias = null,
        ?\Closure $over = null,
    ): static {
        return $this->selectWindow(
            function: WindowFunction::FIRST_VALUE,
            alias: $alias,
            over: $over,
            column: $column,
        );
    }

    public function selectLastValue(
        string $column,
        ?string $alias = null,
        ?\Closure $over = null,
    ): static {
        return $this->selectWindow(
            function: WindowFunction::LAST_VALUE,
            alias: $alias,
            over: $over,
            column: $column,
        );
    }

    public function selectNthValue(
        string $column,
        int $n,
        ?string $alias = null,
        ?\Closure $over = null,
    ): static {
        return $this->selectWindow(
            function: WindowFunction::NTH_VALUE,
            alias: $alias,
            over: $over,
            column: $column,
            argument: $n,
        );
    }

    public function havingCount(
        int $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
        ?string $column = null,
        bool $distinct = false,
    ): static {
        return $this->havingAggregate(
            function: AggregateFunction::COUNT,
            column: $column,
            value: $value,
            operator: $operator,
            distinct: $distinct,
        );
    }

    public function havingSum(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
        bool $distinct = false,
    ): static {
        return $this->havingAggregate(
            function: AggregateFunction::SUM,
            column: $column,
            value: $value,
            operator: $operator,
            distinct: $distinct,
        );
    }

    public function havingAvg(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
        bool $distinct = false,
    ): static {
        return $this->havingAggregate(
            function: AggregateFunction::AVG,
            column: $column,
            value: $value,
            operator: $operator,
            distinct: $distinct,
        );
    }

    public function havingMin(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        return $this->havingAggregate(
            function: AggregateFunction::MIN,
            column: $column,
            value: $value,
            operator: $operator,
        );
    }

    public function havingMax(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        return $this->havingAggregate(
            function: AggregateFunction::MAX,
            column: $column,
            value: $value,
            operator: $operator,
        );
    }

    public function orHavingCount(
        int $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
        ?string $column = null,
        bool $distinct = false,
    ): static {
        return $this->orHavingAggregate(
            function: AggregateFunction::COUNT,
            column: $column,
            value: $value,
            operator: $operator,
            distinct: $distinct,
        );
    }

    public function orHavingSum(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
        bool $distinct = false,
    ): static {
        return $this->orHavingAggregate(
            function: AggregateFunction::SUM,
            column: $column,
            value: $value,
            operator: $operator,
            distinct: $distinct,
        );
    }

    public function orHavingAvg(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
        bool $distinct = false,
    ): static {
        return $this->orHavingAggregate(
            function: AggregateFunction::AVG,
            column: $column,
            value: $value,
            operator: $operator,
            distinct: $distinct,
        );
    }

    public function orHavingMin(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        return $this->orHavingAggregate(
            function: AggregateFunction::MIN,
            column: $column,
            value: $value,
            operator: $operator,
        );
    }

    public function orHavingMax(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        return $this->orHavingAggregate(
            function: AggregateFunction::MAX,
            column: $column,
            value: $value,
            operator: $operator,
        );
    }

    public function selectRaw(
        string $sql,
        ?string $alias = null,
    ): static {
        $this->columns[] = [
            'kind' => 'raw',
            'expression' => $sql,
            'function' => null,
            'distinct' => false,
            'alias' => $alias,
            'window' => null,
            'windowFunction' => null,
            'argument' => null,
            'default' => null,
        ];

        return $this;
    }

    public function selectSubquery(
        SubqueryRenderableInterface $subquery,
        string $alias,
    ): static {
        $this->selectSubqueries[] = [
            'subquery' => $subquery,
            'alias' => $alias,
        ];

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
            $direction = OrderDirection::fromInput($direction);
        }

        foreach ($this->orderBy as $index => $entry) {
            if ($entry['expression'] === $column) {
                $this->orderBy[$index]['direction'] = $direction;

                return $this;
            }
        }

        $this->orderBy[] = [
            'expression' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    public function orderBySubquery(
        SubqueryRenderableInterface $subquery,
        OrderDirection|string $direction = OrderDirection::ASC,
    ): static {
        if (\is_string($direction)) {
            $direction = OrderDirection::fromInput($direction);
        }

        $this->orderBy[] = [
            'expression' => $subquery,
            'direction' => $direction,
        ];

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

    public function havingAggregate(
        AggregateFunction $function,
        ?string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
        bool $distinct = false,
    ): static {
        return $this->addAggregateHaving(
            function: $function,
            column: $column,
            value: $value,
            operator: $operator,
            distinct: $distinct,
            conjunction: ConditionConjunction::AND,
        );
    }

    public function orHavingAggregate(
        AggregateFunction $function,
        ?string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
        bool $distinct = false,
    ): static {
        return $this->addAggregateHaving(
            function: $function,
            column: $column,
            value: $value,
            operator: $operator,
            distinct: $distinct,
            conjunction: ConditionConjunction::OR,
        );
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
        BetweenOperator|string $operator = BetweenOperator::BETWEEN,
    ): static {
        if (\is_string($operator)) {
            $operator = BetweenOperator::fromInput($operator);
        }

        $parameterKey = 'having_between_' . \sizeof($this->havingConditions);

        $this->parameters[$parameterKey . '_from'] = $from;
        $this->parameters[$parameterKey . '_to'] = $to;
        $this->havingConditions[] = new BetweenCondition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
            operator: $operator,
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
        return $this->havingBetween(
            column: $column,
            from: $from,
            to: $to,
            operator: BetweenOperator::NOT_BETWEEN,
        );
    }

    public function orHavingBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
        BetweenOperator|string $operator = BetweenOperator::BETWEEN,
    ): static {
        if (\is_string($operator)) {
            $operator = BetweenOperator::fromInput($operator);
        }

        $parameterKey = 'having_between_' . \sizeof($this->havingConditions);

        $this->parameters[$parameterKey . '_from'] = $from;
        $this->parameters[$parameterKey . '_to'] = $to;
        $this->havingConditions[] = new BetweenCondition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: $operator,
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
        return $this->orHavingBetween(
            column: $column,
            from: $from,
            to: $to,
            operator: BetweenOperator::NOT_BETWEEN,
        );
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

    public function renderSubquery(
        DialectInterface $dialect,
        string $parameterPrefix,
    ): array {
        $sql = $this->generateSql($dialect);
        $keys = [];
        $parameters = [];

        foreach ($this->parameters as $key => $value) {
            $stringKey = (string) $key;
            $keys[$stringKey] = true;
            $parameters[$parameterPrefix . $stringKey] = $value;
        }

        $rewritten = \preg_replace_callback(
            pattern: '/:([A-Za-z_][A-Za-z0-9_]*)/',
            callback: static fn (array $match): string => isset($keys[$match[1]])
                ? ':' . $parameterPrefix . $match[1]
                : $match[0],
            subject: $sql,
        );

        return [
            'sql' => $rewritten ?? $sql,
            'parameters' => $parameters,
        ];
    }

    private function renderFrom(
        DialectInterface $dialect,
    ): string {
        if ($this->fromSubquery === null) {
            return $this->tableReference($dialect, $this->table, $this->alias);
        }

        return '(' . $this->mergeSubquery(
            subquery: $this->fromSubquery,
            prefix: 'fsq_',
            dialect: $dialect,
        ) . ') AS ' . $dialect->identifier($this->alias ?? $this->table);
    }

    /**
     * @param array{kind: 'column'|'aggregate'|'raw'|'window', expression: string|null, function: AggregateFunction|null, distinct: bool, alias: string|null, window: WindowInterface|null, windowFunction: WindowFunction|null, argument: int|null, default: string|null} $column
     */
    private function renderColumn(
        DialectInterface $dialect,
        array $column,
    ): string {
        $expression = $column['expression'] ?? '';

        $sql = match ($column['kind']) {
            'raw' => $expression,
            'aggregate' => $this->renderAggregate($dialect, $column['function'] ?? AggregateFunction::COUNT, $column['expression'], $column['distinct']) . $this->renderOver($dialect, $column['window']),
            'window' => $this->renderWindowFunction($dialect, $column['windowFunction'] ?? WindowFunction::ROW_NUMBER, $column['expression'], $column['argument'], $column['default']) . $this->renderOver($dialect, $column['window']),
            'column' => $expression === '*'
                ? '*'
                : $dialect->qualifiedIdentifier($expression),
        };

        return $column['alias'] === null
            ? $sql
            : $sql . ' AS ' . $dialect->identifier($column['alias']);
    }

    private function renderAggregate(
        DialectInterface $dialect,
        AggregateFunction $function,
        ?string $column,
        bool $distinct,
    ): string {
        if ($column === null) {
            return $function->value . '(*)';
        }

        return $function->value . '(' . ($distinct ? 'DISTINCT ' : '') . $dialect->qualifiedIdentifier($column) . ')';
    }

    private function renderWindowFunction(
        DialectInterface $dialect,
        WindowFunction $function,
        ?string $column,
        ?int $argument,
        ?string $default,
    ): string {
        $arguments = [];

        if ($column !== null) {
            $arguments[] = $dialect->qualifiedIdentifier($column);
        }

        if ($argument !== null || $default !== null) {
            $arguments[] = (string) ($argument ?? 1);
        }

        if ($default !== null) {
            $arguments[] = $default;
        }

        return $dialect->windowFunction($function, $arguments);
    }

    private function renderOver(
        DialectInterface $dialect,
        ?WindowInterface $window,
    ): string {
        if ($window === null) {
            return '';
        }

        $clauses = [];

        if (\sizeof($window->partitionColumns) > 0) {
            $clauses[] = 'PARTITION BY ' . \join(
                ', ',
                \array_map(
                    static fn (string $column): string => $dialect->qualifiedIdentifier($column),
                    $window->partitionColumns,
                ),
            );
        }

        if (\sizeof($window->orderColumns) > 0) {
            $singleKey = $window->frameUnit === FrameUnit::RANGE && (
                $window->frameStart?->offset !== null ||
                $window->frameEnd?->offset !== null
            );

            $terms = [];

            foreach ($window->orderColumns as $order) {
                $expression = $dialect->qualifiedIdentifier($order['column']);

                $terms[] = $singleKey
                    ? $expression . ' ' . $order['direction']->name
                    : $dialect->orderTerm($expression, $order['direction']);
            }

            $clauses[] = 'ORDER BY ' . \join(', ', $terms);
        }

        if ($window->frameUnit !== null && $window->frameStart !== null && $window->frameEnd !== null) {
            $clauses[] = \sprintf(
                '%s BETWEEN %s AND %s',
                $window->frameUnit->name,
                self::renderFrameBound($window->frameStart),
                self::renderFrameBound($window->frameEnd),
            );
        }

        return ' OVER (' . \join(' ', $clauses) . ')';
    }

    private static function renderFrameBound(
        FrameBoundInterface $bound,
    ): string {
        return match ($bound->kind) {
            FrameBoundKind::UNBOUNDED_PRECEDING => 'UNBOUNDED PRECEDING',
            FrameBoundKind::PRECEDING => ($bound->offset ?? 0) . ' PRECEDING',
            FrameBoundKind::CURRENT_ROW => 'CURRENT ROW',
            FrameBoundKind::FOLLOWING => ($bound->offset ?? 0) . ' FOLLOWING',
            FrameBoundKind::UNBOUNDED_FOLLOWING => 'UNBOUNDED FOLLOWING',
        };
    }

    /**
     * @param (\Closure(WindowInterface): void)|null $over
     */
    private static function window(
        ?\Closure $over,
    ): WindowInterface {
        $window = new Window();

        if ($over !== null) {
            $over($window);
        }

        return $window;
    }

    /**
     * @throws SqlException
     */
    private static function guardWindowFunction(
        WindowFunction $function,
        ?string $column,
        ?int $argument,
        string|int|float|bool|null $default,
    ): void {
        if ($function->takesColumn() && $column === null) {
            throw SqlException::fromWindowFunctionRequiresColumn(
                function: $function,
            );
        }

        if (!$function->takesColumn() && $column !== null) {
            throw SqlException::fromWindowFunctionArgumentNotAccepted(
                function: $function,
                argument: 'a column',
            );
        }

        if ($function->requiresArgument() && $argument === null) {
            throw SqlException::fromWindowFunctionRequiresArgument(
                function: $function,
            );
        }

        if (!$function->takesArgument() && $argument !== null) {
            throw SqlException::fromWindowFunctionArgumentNotAccepted(
                function: $function,
                argument: 'an argument',
            );
        }

        if ($argument !== null && $argument < $function->minimumArgument()) {
            throw SqlException::fromInvalidWindowFunctionArgument(
                function: $function,
                value: $argument,
            );
        }

        if (!$function->takesDefault() && $default !== null) {
            throw SqlException::fromWindowFunctionArgumentNotAccepted(
                function: $function,
                argument: 'a default',
            );
        }
    }

    /**
     * @throws SqlException
     */
    private static function guardAggregateColumn(
        AggregateFunction $function,
        ?string $column,
        bool $distinct,
    ): void {
        if ($column === null && ($function->requiresColumn() || $distinct)) {
            throw SqlException::fromAggregateRequiresColumn(
                function: $function,
            );
        }
    }

    /**
     * @throws SqlException
     */
    private function addAggregateHaving(
        AggregateFunction $function,
        ?string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator,
        bool $distinct,
        ConditionConjunction $conjunction,
    ): static {
        self::guardAggregateColumn($function, $column, $distinct);

        if (\is_string($operator)) {
            $operator = ConditionOperator::fromInput($operator);
        }

        $parameterKey = 'having_' . \sizeof($this->havingConditions);

        $this->parameters[$parameterKey] = $value;
        $this->havingConditions[] = new AggregateCondition(
            conjunction: $conjunction,
            function: $function,
            column: $column,
            distinct: $distinct,
            operator: $operator,
            parameter: ':' . $parameterKey,
        );

        return $this;
    }
}
