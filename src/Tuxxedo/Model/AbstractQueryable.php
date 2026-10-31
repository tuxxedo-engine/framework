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

use Tuxxedo\Database\Query\Statement\Aggregate\AggregateFunction;
use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\Join\JoinConditionInterface;
use Tuxxedo\Database\Query\Statement\Order\OrderDirection;
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasManyThrough;
use Tuxxedo\Model\Attribute\Relation\HasOneThrough;
use Tuxxedo\Model\Attribute\Relation\MorphMany;
use Tuxxedo\Model\Attribute\Relation\MorphOne;
use Tuxxedo\Model\Attribute\Relation\MorphToMany;
use Tuxxedo\Model\MetaData\ModelCompositeKeyInterface;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;
use Tuxxedo\Model\MetaData\ModelPrimaryKeyInterface;
use Tuxxedo\Model\MetaData\ModelRelationInterface;
use Tuxxedo\Model\MetaData\ModelRelationLink;
use Tuxxedo\Model\MetaData\ModelRelationLinkInterface;
use Tuxxedo\Validator\Attribute\SkipCascade;

/**
 * @template TModel of object
 *
 * @implements QueryableInterface<TModel>
 */
abstract class AbstractQueryable implements QueryableInterface
{
    /**
     * @var array<int, TModel>|null
     */
    private ?array $cache = null;

    private ?int $cachedTotalCount = null;

    #[SkipCascade]
    public int $totalCount {
        get {
            return $this->cachedTotalCount ??= $this->computeTotalCount();
        }
    }

    /**
     * @var int<0, max>
     */
    #[SkipCascade]
    public int $count {
        get {
            return \sizeof($this->materialize());
        }
    }

    /**
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>, list<array{column: string|SelectStatementInterface, direction: OrderDirection}>, ?int, ?int): iterable<int, TModel>)|null $loaderBuilder
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>): int)|null $countBuilder
     * @param list<\Closure(WhereStatementInterface): void> $criteriaStack
     * @param list<array{column: string|SelectStatementInterface, direction: OrderDirection}> $orderBy
     * @param ?class-string<TModel> $modelClass
     */
    protected function __construct(
        protected readonly ?\Closure $loaderBuilder = null,
        protected readonly ?\Closure $countBuilder = null,
        public readonly array $criteriaStack = [],
        public readonly array $orderBy = [],
        public readonly ?int $limit = null,
        public readonly ?int $offset = null,
        protected readonly ?ModelsManagerInterface $manager = null,
        protected readonly ?string $modelClass = null,
    ) {
    }

    /**
     * @param list<\Closure(WhereStatementInterface): void> $criteriaStack
     * @param list<array{column: string|SelectStatementInterface, direction: OrderDirection}> $orderBy
     */
    abstract protected function cloneWith(
        array $criteriaStack,
        array $orderBy,
        ?int $limit,
        ?int $offset,
    ): static;

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->materialize()[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->materialize()[$offset];
    }

    /**
     * @return never
     *
     * @throws ModelException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw ModelException::fromImmutableRelation();
    }

    /**
     * @return never
     *
     * @throws ModelException
     */
    public function offsetUnset(mixed $offset): void
    {
        throw ModelException::fromImmutableRelation();
    }

    /**
     * @param SelectStatementInterface|string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    #[\NoDiscard]
    public function where(
        string $column,
        SelectStatementInterface|string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $value, $operator): void {
                $statement->where($column, $value, $operator);
            },
        );
    }

    /**
     * @param SelectStatementInterface|string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    #[\NoDiscard]
    public function orWhere(
        string $column,
        SelectStatementInterface|string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $value, $operator): void {
                $statement->orWhere($column, $value, $operator);
            },
        );
    }

    #[\NoDiscard]
    public function whereNull(
        string $column,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column): void {
                $statement->whereNull($column);
            },
        );
    }

    #[\NoDiscard]
    public function whereNotNull(
        string $column,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column): void {
                $statement->whereNotNull($column);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereNull(
        string $column,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column): void {
                $statement->orWhereNull($column);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereNotNull(
        string $column,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column): void {
                $statement->orWhereNotNull($column);
            },
        );
    }

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    #[\NoDiscard]
    public function whereIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $values): void {
                $statement->whereIn($column, $values);
            },
        );
    }

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    #[\NoDiscard]
    public function whereNotIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $values): void {
                $statement->whereNotIn($column, $values);
            },
        );
    }

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    #[\NoDiscard]
    public function orWhereIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $values): void {
                $statement->orWhereIn($column, $values);
            },
        );
    }

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    #[\NoDiscard]
    public function orWhereNotIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $values): void {
                $statement->orWhereNotIn($column, $values);
            },
        );
    }

    #[\NoDiscard]
    public function whereBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $from, $to): void {
                $statement->whereBetween($column, $from, $to);
            },
        );
    }

    #[\NoDiscard]
    public function whereNotBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $from, $to): void {
                $statement->whereNotBetween($column, $from, $to);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $from, $to): void {
                $statement->orWhereBetween($column, $from, $to);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereNotBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $from, $to): void {
                $statement->orWhereNotBetween($column, $from, $to);
            },
        );
    }

    #[\NoDiscard]
    public function whereColumn(
        string $column,
        string $other,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $other, $operator): void {
                $statement->whereColumn($column, $other, $operator);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereColumn(
        string $column,
        string $other,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $other, $operator): void {
                $statement->orWhereColumn($column, $other, $operator);
            },
        );
    }

    /**
     * @param array<string, string|int|float|bool|null> $bindings
     */
    #[\NoDiscard]
    public function whereRaw(
        string $sql,
        array $bindings = [],
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($sql, $bindings): void {
                $statement->whereRaw($sql, $bindings);
            },
        );
    }

    #[\NoDiscard]
    public function whereLike(
        string $column,
        string $pattern,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $pattern): void {
                $statement->whereLike($column, $pattern);
            },
        );
    }

    #[\NoDiscard]
    public function whereNotLike(
        string $column,
        string $pattern,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $pattern): void {
                $statement->whereNotLike($column, $pattern);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereLike(
        string $column,
        string $pattern,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $pattern): void {
                $statement->orWhereLike($column, $pattern);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereNotLike(
        string $column,
        string $pattern,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $pattern): void {
                $statement->orWhereNotLike($column, $pattern);
            },
        );
    }

    #[\NoDiscard]
    public function whereFullText(
        array $columns,
        string $search,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($columns, $search): void {
                $statement->whereFullText($columns, $search);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereFullText(
        array $columns,
        string $search,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($columns, $search): void {
                $statement->orWhereFullText($columns, $search);
            },
        );
    }

    /**
     * @param (\Closure(JoinConditionInterface): void)|null $on
     */
    #[\NoDiscard]
    public function innerJoin(
        string $table,
        ?string $first = null,
        ?string $second = null,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
        ?string $alias = null,
        ?\Closure $on = null,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($table, $first, $second, $operator, $alias, $on): void {
                $statement->innerJoin(
                    table: $table,
                    first: $first,
                    second: $second,
                    operator: $operator,
                    alias: $alias,
                    on: $on,
                );
            },
        );
    }

    /**
     * @param (\Closure(JoinConditionInterface): void)|null $on
     */
    #[\NoDiscard]
    public function leftJoin(
        string $table,
        ?string $first = null,
        ?string $second = null,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
        ?string $alias = null,
        ?\Closure $on = null,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($table, $first, $second, $operator, $alias, $on): void {
                $statement->leftJoin(
                    table: $table,
                    first: $first,
                    second: $second,
                    operator: $operator,
                    alias: $alias,
                    on: $on,
                );
            },
        );
    }

    /**
     * @param (\Closure(JoinConditionInterface): void)|null $on
     */
    #[\NoDiscard]
    public function rightJoin(
        string $table,
        ?string $first = null,
        ?string $second = null,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
        ?string $alias = null,
        ?\Closure $on = null,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($table, $first, $second, $operator, $alias, $on): void {
                $statement->rightJoin(
                    table: $table,
                    first: $first,
                    second: $second,
                    operator: $operator,
                    alias: $alias,
                    on: $on,
                );
            },
        );
    }

    #[\NoDiscard]
    public function crossJoin(
        string $table,
        ?string $alias = null,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($table, $alias): void {
                $statement->crossJoin($table, $alias);
            },
        );
    }

    #[\NoDiscard]
    public function whereColumnStartsWith(
        string $column,
        string $prefixColumn,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $prefixColumn): void {
                $statement->whereColumnStartsWith($column, $prefixColumn);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereColumnStartsWith(
        string $column,
        string $prefixColumn,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $prefixColumn): void {
                $statement->orWhereColumnStartsWith($column, $prefixColumn);
            },
        );
    }

    #[\NoDiscard]
    public function whereStartsWith(
        string $column,
        string $prefix,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $prefix): void {
                $statement->whereStartsWith($column, $prefix);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereStartsWith(
        string $column,
        string $prefix,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $prefix): void {
                $statement->orWhereStartsWith($column, $prefix);
            },
        );
    }

    #[\NoDiscard]
    public function orderBy(
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
    ): static {
        if (\is_string($direction)) {
            $direction = OrderDirection::from($direction);
        }

        $orderBy = $this->orderBy;
        $orderBy[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this->cloneWith(
            criteriaStack: $this->criteriaStack,
            orderBy: $orderBy,
            limit: $this->limit,
            offset: $this->offset,
        );
    }

    #[\NoDiscard]
    public function page(
        int $limit,
        ?int $offset = null,
    ): static {
        return $this->cloneWith(
            criteriaStack: $this->criteriaStack,
            orderBy: $this->orderBy,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * @return iterable<int, TModel>
     */
    public function slice(
        int $limit,
        int $offset,
    ): iterable {
        return $this->page($limit, $offset);
    }

    /**
     * @return \Generator<int, TModel>
     */
    #[\NoDiscard]
    public function fetchAll(): \Generator
    {
        yield from $this->materialize();
    }

    #[\NoDiscard]
    public function whereExists(
        SelectStatementInterface $subquery,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($subquery): void {
                $statement->whereExists($subquery);
            },
        );
    }

    #[\NoDiscard]
    public function whereNotExists(
        SelectStatementInterface $subquery,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($subquery): void {
                $statement->whereNotExists($subquery);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereExists(
        SelectStatementInterface $subquery,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($subquery): void {
                $statement->orWhereExists($subquery);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereNotExists(
        SelectStatementInterface $subquery,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($subquery): void {
                $statement->orWhereNotExists($subquery);
            },
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    #[\NoDiscard]
    public function whereHas(
        string $relationName,
        ?\Closure $callback = null,
        bool $includeDeleted = false,
        ConditionOperator|string $operator = ConditionOperator::GREATER_THAN_OR_EQUAL,
        int $count = 1,
    ): static {
        if (\is_string($operator)) {
            $operator = ConditionOperator::fromInput($operator);
        }

        if ($operator === ConditionOperator::GREATER_THAN_OR_EQUAL && $count === 1) {
            return $this->whereExists(
                $this->buildRelationExistsSubquery($relationName, $callback, $includeDeleted),
            );
        }

        $subquery = $this->buildRelationCountSubquery($relationName, $callback, $includeDeleted);

        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($subquery, $count, $operator): void {
                $statement->whereSubquery(
                    subquery: $subquery,
                    value: $count,
                    operator: $operator,
                );
            },
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    #[\NoDiscard]
    public function whereDoesntHave(
        string $relationName,
        ?\Closure $callback = null,
        bool $includeDeleted = false,
    ): static {
        return $this->whereNotExists(
            $this->buildRelationExistsSubquery($relationName, $callback, $includeDeleted),
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    #[\NoDiscard]
    public function orWhereHas(
        string $relationName,
        ?\Closure $callback = null,
        bool $includeDeleted = false,
    ): static {
        return $this->orWhereExists(
            $this->buildRelationExistsSubquery($relationName, $callback, $includeDeleted),
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    #[\NoDiscard]
    public function orWhereDoesntHave(
        string $relationName,
        ?\Closure $callback = null,
        bool $includeDeleted = false,
    ): static {
        return $this->orWhereNotExists(
            $this->buildRelationExistsSubquery($relationName, $callback, $includeDeleted),
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    #[\NoDiscard]
    public function withCount(
        string $relationName,
        ?string $alias = null,
        ?\Closure $callback = null,
        bool $distinct = false,
    ): static {
        return $this->withAggregate(
            function: AggregateFunction::COUNT,
            relationName: $relationName,
            column: null,
            alias: $alias,
            callback: $callback,
            distinct: $distinct,
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    #[\NoDiscard]
    public function withSum(
        string $relationName,
        string $column,
        ?string $alias = null,
        ?\Closure $callback = null,
    ): static {
        return $this->withAggregate(
            function: AggregateFunction::SUM,
            relationName: $relationName,
            column: $column,
            alias: $alias,
            callback: $callback,
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    #[\NoDiscard]
    public function withAvg(
        string $relationName,
        string $column,
        ?string $alias = null,
        ?\Closure $callback = null,
    ): static {
        return $this->withAggregate(
            function: AggregateFunction::AVG,
            relationName: $relationName,
            column: $column,
            alias: $alias,
            callback: $callback,
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    #[\NoDiscard]
    public function withMin(
        string $relationName,
        string $column,
        ?string $alias = null,
        ?\Closure $callback = null,
    ): static {
        return $this->withAggregate(
            function: AggregateFunction::MIN,
            relationName: $relationName,
            column: $column,
            alias: $alias,
            callback: $callback,
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    #[\NoDiscard]
    public function withMax(
        string $relationName,
        string $column,
        ?string $alias = null,
        ?\Closure $callback = null,
    ): static {
        return $this->withAggregate(
            function: AggregateFunction::MAX,
            relationName: $relationName,
            column: $column,
            alias: $alias,
            callback: $callback,
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    #[\NoDiscard]
    public function orderByCount(
        string $relationName,
        OrderDirection|string $direction = OrderDirection::ASC,
        ?\Closure $callback = null,
    ): static {
        return $this->orderByAggregate(
            function: AggregateFunction::COUNT,
            relationName: $relationName,
            column: null,
            direction: $direction,
            callback: $callback,
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    #[\NoDiscard]
    public function orderBySum(
        string $relationName,
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
        ?\Closure $callback = null,
    ): static {
        return $this->orderByAggregate(
            function: AggregateFunction::SUM,
            relationName: $relationName,
            column: $column,
            direction: $direction,
            callback: $callback,
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    #[\NoDiscard]
    public function orderByAvg(
        string $relationName,
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
        ?\Closure $callback = null,
    ): static {
        return $this->orderByAggregate(
            function: AggregateFunction::AVG,
            relationName: $relationName,
            column: $column,
            direction: $direction,
            callback: $callback,
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    #[\NoDiscard]
    public function orderByMin(
        string $relationName,
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
        ?\Closure $callback = null,
    ): static {
        return $this->orderByAggregate(
            function: AggregateFunction::MIN,
            relationName: $relationName,
            column: $column,
            direction: $direction,
            callback: $callback,
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    #[\NoDiscard]
    public function orderByMax(
        string $relationName,
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
        ?\Closure $callback = null,
    ): static {
        return $this->orderByAggregate(
            function: AggregateFunction::MAX,
            relationName: $relationName,
            column: $column,
            direction: $direction,
            callback: $callback,
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    private function withAggregate(
        AggregateFunction $function,
        string $relationName,
        ?string $column,
        ?string $alias,
        ?\Closure $callback,
        bool $distinct = false,
    ): static {
        $methodName = 'with' . \ucfirst(\strtolower($function->value));

        if ($this->manager === null || $this->modelClass === null) {
            throw ModelException::fromChainMethodRequiresModelContext(
                method: $methodName,
            );
        }

        $parentMetaData = $this->manager->metaData->getModel($this->modelClass);
        $subquery = $this->buildAggregateSubquery(
            function: $function,
            relationName: $relationName,
            column: $column,
            callback: $callback,
            methodName: $methodName,
            distinct: $distinct,
        );

        $matchingSlotAlias = null;

        foreach ($parentMetaData->aggregates as $aggregate) {
            if (
                $aggregate->relation === $relationName &&
                $aggregate->function === $function &&
                $aggregate->column === $column
            ) {
                $matchingSlotAlias = $aggregate->alias;

                break;
            }
        }

        $resolvedAlias = $alias ?? $matchingSlotAlias;

        if ($resolvedAlias === null) {
            throw ModelException::fromAggregateNoDeclaredSlot(
                modelClass: $parentMetaData->model,
                relation: $relationName,
                alias: $this->defaultAggregateAliasFor(
                    function: $function,
                    relationName: $relationName,
                    column: $column,
                ),
            );
        }

        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($subquery, $resolvedAlias): void {
                if (!$statement instanceof SelectStatementInterface) {
                    return; // @codeCoverageIgnore
                }

                $statement->selectSubquery(
                    subquery: $subquery,
                    alias: $resolvedAlias,
                );
            },
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    private function orderByAggregate(
        AggregateFunction $function,
        string $relationName,
        ?string $column,
        OrderDirection|string $direction,
        ?\Closure $callback,
    ): static {
        if (\is_string($direction)) {
            $direction = OrderDirection::from($direction);
        }

        $subquery = $this->buildAggregateSubquery(
            function: $function,
            relationName: $relationName,
            column: $column,
            callback: $callback,
            methodName: 'orderBy' . \ucfirst(\strtolower($function->value)),
        );

        $orderBy = $this->orderBy;
        $orderBy[] = [
            'column' => $subquery,
            'direction' => $direction,
        ];

        return $this->cloneWith(
            criteriaStack: $this->criteriaStack,
            orderBy: $orderBy,
            limit: $this->limit,
            offset: $this->offset,
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    private function buildAggregateSubquery(
        AggregateFunction $function,
        string $relationName,
        ?string $column,
        ?\Closure $callback,
        string $methodName,
        bool $distinct = false,
    ): SelectStatementInterface {
        if ($this->manager === null || $this->modelClass === null) {
            throw ModelException::fromChainMethodRequiresModelContext(
                method: $methodName,
            );
        }

        $chain = $this->buildRelationJoinChain(
            manager: $this->manager,
            parentMetaData: $this->manager->metaData->getModel($this->modelClass),
            relationName: $relationName,
            callback: $callback,
            includeDeleted: false,
            methodName: $methodName,
        );

        if ($function->requiresColumn()) {
            $aggregateColumn = $chain['qualifier'] . '.' . $column;
        } elseif ($distinct) {
            $keyColumns = $this->resolvePkColumns($chain['target']);

            if (\sizeof($keyColumns) !== 1) {
                throw ModelException::fromRelationCountDistinctOnCompositeKey(
                    modelClass: $this->modelClass,
                    relation: $relationName,
                );
            }

            $aggregateColumn = $chain['qualifier'] . '.' . $keyColumns[0];
        } else {
            $aggregateColumn = null;
        }

        return $chain['subquery']->selectAggregate(
            function: $function,
            column: $aggregateColumn,
            distinct: $distinct,
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    private function buildRelationCountSubquery(
        string $relationName,
        ?\Closure $callback,
        bool $includeDeleted,
    ): SelectStatementInterface {
        if ($this->manager === null || $this->modelClass === null) {
            throw ModelException::fromChainMethodRequiresModelContext(
                method: 'whereHas',
            );
        }

        $chain = $this->buildRelationJoinChain(
            manager: $this->manager,
            parentMetaData: $this->manager->metaData->getModel($this->modelClass),
            relationName: $relationName,
            callback: $callback,
            includeDeleted: $includeDeleted,
            methodName: 'whereHas',
        );

        return $chain['subquery']->selectAggregate(AggregateFunction::COUNT);
    }

    private function defaultAggregateAliasFor(
        AggregateFunction $function,
        string $relationName,
        ?string $column,
    ): string {
        $prefix = \str_replace('.', '_', $relationName);

        return $function->requiresColumn()
            ? $prefix . '_' . \strtolower($function->value) . '_' . ($column ?? '')
            : $prefix . '_count';
    }

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     */
    #[\NoDiscard]
    public function whereGroup(
        \Closure $callback,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($callback): void {
                $statement->whereGroup($callback);
            },
        );
    }

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     */
    #[\NoDiscard]
    public function orWhereGroup(
        \Closure $callback,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($callback): void {
                $statement->orWhereGroup($callback);
            },
        );
    }

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     */
    #[\NoDiscard]
    public function whereNot(
        \Closure $callback,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($callback): void {
                $statement->whereNot($callback);
            },
        );
    }

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     */
    #[\NoDiscard]
    public function orWhereNot(
        \Closure $callback,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($callback): void {
                $statement->orWhereNot($callback);
            },
        );
    }

    /**
     * @return TModel|null
     */
    #[\NoDiscard]
    public function first(): ?object
    {
        if ($this->cache === null && $this->loaderBuilder !== null) {
            $loaded = ($this->loaderBuilder)($this->criteriaStack, $this->orderBy, 1, $this->offset);

            foreach ($loaded as $item) {
                return $item;
            }

            return null;
        }

        foreach ($this->materialize() as $item) {
            return $item;
        }

        return null;
    }

    public function getIterator(): \Generator
    {
        return $this->fetchAll();
    }

    public function count(): int
    {
        return $this->count;
    }

    public function isMaterialized(): bool
    {
        return $this->cache !== null;
    }

    protected function computeTotalCount(): int
    {
        if ($this->countBuilder !== null) {
            return ($this->countBuilder)($this->criteriaStack);
        }

        return \sizeof($this->materialize()); // @codeCoverageIgnore
    }

    /**
     * @return array<int, TModel>
     */
    protected function materialize(): array
    {
        return $this->loadBase();
    }

    /**
     * @return array<int, TModel>
     */
    protected function loadBase(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if ($this->loaderBuilder === null) {
            return []; // @codeCoverageIgnore
        }

        $loaded = ($this->loaderBuilder)($this->criteriaStack, $this->orderBy, $this->limit, $this->offset);

        return $this->cache = \is_array($loaded)
            ? $loaded
            : \iterator_to_array($loaded);
    }

    /**
     * @param \Closure(WhereStatementInterface): void $criterion
     */
    private function extend(
        \Closure $criterion,
    ): static {
        $stack = $this->criteriaStack;
        $stack[] = $criterion;

        return $this->cloneWith(
            criteriaStack: $stack,
            orderBy: $this->orderBy,
            limit: $this->limit,
            offset: $this->offset,
        );
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    private function buildRelationExistsSubquery(
        string $relationName,
        ?\Closure $callback,
        bool $includeDeleted,
    ): SelectStatementInterface {
        if ($this->manager === null || $this->modelClass === null) {
            throw ModelException::fromChainMethodRequiresModelContext(
                method: 'whereHas',
            );
        }

        $parentMetaData = $this->manager->metaData->getModel($this->modelClass);

        return $this->buildRelationExistsChain(
            manager: $this->manager,
            parentMetaData: $parentMetaData,
            parentQualifier: $parentMetaData->table,
            scope: [
                $parentMetaData->table,
            ],
            relationName: $relationName,
            callback: $callback,
            includeDeleted: $includeDeleted,
        );
    }

    /**
     * @param list<string> $scope
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     */
    private function buildRelationExistsChain(
        ModelsManagerInterface $manager,
        ModelMetaDataInterface $parentMetaData,
        string $parentQualifier,
        array $scope,
        string $relationName,
        ?\Closure $callback,
        bool $includeDeleted,
    ): RelationSubqueryInterface {
        $root = null;
        $innermost = null;

        foreach (\explode('.', $relationName) as $segment) {
            $link = $this->relationLink(
                manager: $manager,
                parentMetaData: $parentMetaData,
                relation: $this->resolveRelation($parentMetaData, $segment),
            );

            $targetQualifier = $this->allocateQualifier($link->target->table, $scope);
            $throughQualifier = $link->throughTable !== null
                ? $this->allocateQualifier($link->throughTable, $scope)
                : null;

            $subquery = $this->createRelationSubquery(
                manager: $manager,
                table: $link->target->table,
                qualifier: $targetQualifier,
                metaData: $link->target,
                scope: $scope,
            );

            $ownerQualifier = $targetQualifier;

            if ($link->throughTable !== null && $throughQualifier !== null) {
                $this->joinLinkThrough($subquery, $link, $link->throughTable, $throughQualifier, $targetQualifier);

                $ownerQualifier = $throughQualifier;
            }

            foreach ($link->filters as [$filterColumn, $filterValue]) {
                $subquery->where(
                    column: $ownerQualifier . '.' . $filterColumn,
                    value: $filterValue,
                );
            }

            foreach ($link->parentOn as [$ownerColumn, $parentColumn]) {
                $subquery->whereColumn(
                    column: $ownerQualifier . '.' . $ownerColumn,
                    other: $parentQualifier . '.' . $parentColumn,
                );
            }

            if (!$includeDeleted) {
                $this->applyLinkSoftDeletes($manager, $subquery, $link, $targetQualifier, $throughQualifier);
            }

            if ($innermost === null) {
                $root = $subquery;
            } else {
                $innermost->whereExists($subquery);
            }

            $innermost = $subquery;
            $parentMetaData = $link->target;
            $parentQualifier = $targetQualifier;
        }

        /**
         * @var RelationSubquery $root
         * @var RelationSubquery $innermost
         */
        if ($callback !== null) {
            $innermost->whereScoped($callback);
        }

        return $root;
    }

    /**
     * @param (\Closure(RelationSubqueryInterface): void)|null $callback
     * @return array{subquery: RelationSubquery, target: ModelMetaDataInterface, qualifier: string}
     */
    private function buildRelationJoinChain(
        ModelsManagerInterface $manager,
        ModelMetaDataInterface $parentMetaData,
        string $relationName,
        ?\Closure $callback,
        bool $includeDeleted,
        string $methodName,
    ): array {
        $scope = [
            $parentMetaData->table,
        ];

        $parentQualifier = $parentMetaData->table;
        $steps = [];

        foreach (\explode('.', $relationName) as $segment) {
            foreach ($parentMetaData->morphToRelations as $morphTo) {
                if ($morphTo->property === $segment) {
                    throw ModelException::fromAggregateOnMorphToViaQueryable(
                        modelClass: $parentMetaData->model,
                        relation: $segment,
                        method: $methodName,
                    );
                }
            }

            $link = $this->relationLink(
                manager: $manager,
                parentMetaData: $parentMetaData,
                relation: $this->resolveRelation($parentMetaData, $segment),
            );

            $targetQualifier = $this->allocateQualifier($link->target->table, $scope);

            $steps[] = [
                'link' => $link,
                'parentQualifier' => $parentQualifier,
                'targetQualifier' => $targetQualifier,
                'throughQualifier' => $link->throughTable !== null
                    ? $this->allocateQualifier($link->throughTable, $scope)
                    : null,
            ];

            $parentMetaData = $link->target;
            $parentQualifier = $targetQualifier;
        }

        $first = $steps[0];

        $subquery = $this->createRelationSubquery(
            manager: $manager,
            table: $first['link']->target->table,
            qualifier: $first['targetQualifier'],
            metaData: $parentMetaData,
            scope: $scope,
            factoryQualifier: $parentQualifier,
        );

        foreach ($steps as $index => $step) {
            $link = $step['link'];
            $targetQualifier = $step['targetQualifier'];
            $throughQualifier = $step['throughQualifier'];
            $ownerQualifier = $throughQualifier ?? $targetQualifier;

            if ($index === 0) {
                if ($link->throughTable !== null && $throughQualifier !== null) {
                    $this->joinLinkThrough($subquery, $link, $link->throughTable, $throughQualifier, $targetQualifier);
                }

                foreach ($link->filters as [$filterColumn, $filterValue]) {
                    $subquery->where(
                        column: $ownerQualifier . '.' . $filterColumn,
                        value: $filterValue,
                    );
                }

                foreach ($link->parentOn as [$ownerColumn, $parentColumn]) {
                    $subquery->whereColumn(
                        column: $ownerQualifier . '.' . $ownerColumn,
                        other: $step['parentQualifier'] . '.' . $parentColumn,
                    );
                }

                continue;
            }

            $linkParentQualifier = $step['parentQualifier'];
            $ownerTable = $link->throughTable ?? $link->target->table;

            $subquery->innerJoin(
                table: $ownerTable,
                alias: $ownerQualifier !== $ownerTable ? $ownerQualifier : null,
                on: static function (JoinConditionInterface $on) use ($link, $ownerQualifier, $linkParentQualifier): void {
                    foreach ($link->parentOn as [$ownerColumn, $parentColumn]) {
                        $on->whereColumn(
                            column: $ownerQualifier . '.' . $ownerColumn,
                            other: $linkParentQualifier . '.' . $parentColumn,
                        );
                    }

                    foreach ($link->filters as [$filterColumn, $filterValue]) {
                        $on->where(
                            column: $ownerQualifier . '.' . $filterColumn,
                            value: $filterValue,
                        );
                    }
                },
            );

            if ($throughQualifier !== null) {
                $subquery->innerJoin(
                    table: $link->target->table,
                    alias: $targetQualifier !== $link->target->table ? $targetQualifier : null,
                    on: static function (JoinConditionInterface $on) use ($link, $throughQualifier, $targetQualifier): void {
                        foreach ($link->throughOn as [$throughColumn, $targetColumn]) {
                            $on->whereColumn(
                                column: $throughQualifier . '.' . $throughColumn,
                                other: $targetQualifier . '.' . $targetColumn,
                            );
                        }
                    },
                );
            }
        }

        if (!$includeDeleted) {
            foreach ($steps as $step) {
                $this->applyLinkSoftDeletes($manager, $subquery, $step['link'], $step['targetQualifier'], $step['throughQualifier']);
            }
        }

        if ($callback !== null) {
            $subquery->whereScoped($callback);
        }

        return [
            'subquery' => $subquery,
            'target' => $parentMetaData,
            'qualifier' => $parentQualifier,
        ];
    }

    /**
     * @param list<string> $scope
     */
    private function createRelationSubquery(
        ModelsManagerInterface $manager,
        string $table,
        string $qualifier,
        ModelMetaDataInterface $metaData,
        array $scope,
        ?string $factoryQualifier = null,
    ): RelationSubquery {
        $factoryQualifier ??= $qualifier;

        $subquery = RelationSubquery::createForRelation(
            table: $table,
            connection: $manager->connection,
            existsFactory: fn (string $relationName, ?\Closure $callback, bool $includeDeleted): SelectStatementInterface => $this->buildRelationExistsChain(
                manager: $manager,
                parentMetaData: $metaData,
                parentQualifier: $factoryQualifier,
                scope: $scope,
                relationName: $relationName,
                callback: $callback,
                includeDeleted: $includeDeleted,
            ),
        );

        if ($qualifier !== $table) {
            $subquery->alias($qualifier);
        }

        return $subquery;
    }

    /**
     * @param list<string> $scope
     */
    private function allocateQualifier(
        string $table,
        array &$scope,
    ): string {
        $qualifier = $table;
        $occurrence = 1;

        while (\in_array($qualifier, $scope, true)) {
            $qualifier = $table . '_' . ++$occurrence;
        }

        $scope[] = $qualifier;

        return $qualifier;
    }

    private function joinLinkThrough(
        RelationSubquery $subquery,
        ModelRelationLinkInterface $link,
        string $throughTable,
        string $throughQualifier,
        string $targetQualifier,
    ): void {
        $subquery->innerJoin(
            table: $throughTable,
            alias: $throughQualifier !== $throughTable ? $throughQualifier : null,
            on: static function (JoinConditionInterface $on) use ($link, $throughQualifier, $targetQualifier): void {
                foreach ($link->throughOn as [$throughColumn, $targetColumn]) {
                    $on->whereColumn(
                        column: $throughQualifier . '.' . $throughColumn,
                        other: $targetQualifier . '.' . $targetColumn,
                    );
                }
            },
        );
    }

    private function applyLinkSoftDeletes(
        ModelsManagerInterface $manager,
        WhereStatementInterface $subquery,
        ModelRelationLinkInterface $link,
        string $targetQualifier,
        ?string $throughQualifier,
    ): void {
        $throughMetaData = $link->throughMetaData;

        if ($throughMetaData !== null && $throughQualifier !== null) {
            $manager->applySoftDeleteFilter($subquery, $throughMetaData, $throughQualifier);
        }

        $manager->applySoftDeleteFilter($subquery, $link->target, $targetQualifier);
    }

    private function relationLink(
        ModelsManagerInterface $manager,
        ModelMetaDataInterface $parentMetaData,
        ModelRelationInterface $relation,
    ): ModelRelationLinkInterface {
        $attribute = $relation->attribute;
        $targetMetaData = $manager->metaData->getModel($relation->relatedClass);

        if ($attribute instanceof HasManyThrough || $attribute instanceof HasOneThrough) {
            $throughMetaData = $manager->metaData->getModel($attribute->through);

            return new ModelRelationLink(
                target: $targetMetaData,
                parentOn: [
                    [
                        $attribute->firstKey,
                        $attribute->localKey ?? $this->requirePrimaryKeyColumn($parentMetaData),
                    ],
                ],
                throughTable: $throughMetaData->table,
                throughMetaData: $throughMetaData,
                throughOn: [
                    [
                        $attribute->secondLocalKey ?? $this->requirePrimaryKeyColumn($throughMetaData),
                        $attribute->secondKey,
                    ],
                ],
            );
        }

        if ($attribute instanceof BelongsToMany || $attribute instanceof MorphToMany) {
            $pivotSourceColumns = $relation->pivotSourceColumns;
            $pivotTargetColumns = $relation->pivotTargetColumns;

            if ($pivotSourceColumns === null || $pivotTargetColumns === null) {
                // @codeCoverageIgnoreStart
                throw ModelException::fromRelationNotFoundOnModel(
                    modelClass: $parentMetaData->model,
                    property: $relation->property,
                );
                // @codeCoverageIgnoreEnd
            }

            $parentKeyColumns = $this->resolvePkColumns($parentMetaData);
            $targetKeyColumns = $this->resolvePkColumns($targetMetaData);

            return new ModelRelationLink(
                target: $targetMetaData,
                parentOn: $this->pairColumns($pivotSourceColumns, $parentKeyColumns),
                throughTable: $attribute->table,
                throughOn: $this->pairColumns($pivotTargetColumns, $targetKeyColumns),
                filters: $attribute instanceof MorphToMany
                    ? [
                        [
                            $attribute->typeColumn,
                            MorphTypeResolver::encode(
                                class: $parentMetaData->model,
                                typeMap: $attribute->typeMap,
                            ),
                        ],
                    ]
                    : [],
            );
        }

        $foreignKeyColumns = $relation->foreignKeyColumns;
        $referencedKeyColumns = $relation->referencedKeyColumns;

        if ($foreignKeyColumns === null || $referencedKeyColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $parentMetaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        return new ModelRelationLink(
            target: $targetMetaData,
            parentOn: $attribute instanceof BelongsTo
                ? $this->pairColumns($referencedKeyColumns, $foreignKeyColumns)
                : $this->pairColumns($foreignKeyColumns, $referencedKeyColumns),
            filters: $attribute instanceof MorphOne || $attribute instanceof MorphMany
                ? [
                    [
                        $attribute->typeColumn,
                        MorphTypeResolver::encode(
                            class: $parentMetaData->model,
                            typeMap: $attribute->typeMap,
                        ),
                    ],
                ]
                : [],
        );
    }

    /**
     * @param non-empty-list<string> $columns
     * @param non-empty-list<string> $otherColumns
     * @return list<array{string, string}>
     */
    private function pairColumns(
        array $columns,
        array $otherColumns,
    ): array {
        $pairs = [];

        foreach ($columns as $index => $column) {
            $pairs[] = [
                $column,
                $otherColumns[$index],
            ];
        }

        return $pairs;
    }

    private function resolveRelation(
        ModelMetaDataInterface $metaData,
        string $relationName,
    ): ModelRelationInterface {
        foreach ($metaData->relations as $relation) {
            if ($relation->property === $relationName) {
                return $relation;
            }
        }

        throw ModelException::fromRelationNotFoundOnModel(
            modelClass: $metaData->model,
            property: $relationName,
        );
    }

    private function requirePrimaryKeyColumn(
        ModelMetaDataInterface $metaData,
    ): string {
        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
            // @codeCoverageIgnoreEnd
        }

        return $metaData->key->column;
    }

    /**
     * @return non-empty-list<string>
     */
    private function resolvePkColumns(
        ModelMetaDataInterface $metaData,
    ): array {
        if ($metaData->key instanceof ModelPrimaryKeyInterface) {
            return [
                $metaData->key->column,
            ];
        }

        if ($metaData->key instanceof ModelCompositeKeyInterface) {
            return \array_values($metaData->key->columns);
        }

        // @codeCoverageIgnoreStart
        throw ModelException::fromCantFetchWithoutPrimaryKey(
            modelClass: $metaData->model,
        );
        // @codeCoverageIgnoreEnd
    }
}
