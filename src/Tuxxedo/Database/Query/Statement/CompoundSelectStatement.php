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

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;
use Tuxxedo\Database\Hydrator\HydratorInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;
use Tuxxedo\Database\Query\Statement\Compound\CompoundOperator;
use Tuxxedo\Database\Query\Statement\Order\OrderDirection;

class CompoundSelectStatement implements CompoundSelectStatementInterface
{
    /**
     * @var non-empty-list<array{operator: CompoundOperator|null, statement: SubqueryRenderableInterface}>
     */
    private array $members;

    /**
     * @var list<array{column: string, direction: OrderDirection}>
     */
    private array $orderBy = [];

    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(
        SubqueryRenderableInterface $first,
        public readonly ?ConnectionInterface $connection = null,
    ) {
        $this->members = [
            [
                'operator' => null,
                'statement' => $first,
            ],
        ];
    }

    public function union(
        SubqueryRenderableInterface $member,
        SubqueryRenderableInterface ...$members,
    ): static {
        return $this->add(CompoundOperator::UNION, $member, ...$members);
    }

    public function unionAll(
        SubqueryRenderableInterface $member,
        SubqueryRenderableInterface ...$members,
    ): static {
        return $this->add(CompoundOperator::UNION_ALL, $member, ...$members);
    }

    public function orderBy(
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
    ): static {
        if (\is_string($direction)) {
            $direction = OrderDirection::fromInput($direction);
        }

        foreach ($this->orderBy as $index => $entry) {
            if ($entry['column'] === $column) {
                $this->orderBy[$index]['direction'] = $direction;

                return $this;
            }
        }

        $this->orderBy[] = [
            'column' => $column,
            'direction' => $direction,
        ];

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

    public function compile(
        ?ConnectionInterface $connection = null,
    ): StatementParserResultInterface {
        $resolvedConnection = $connection ?? $this->connection;

        if ($resolvedConnection === null) {
            throw DatabaseException::fromNoConnectionAvailable();
        }

        $rendered = $this->renderSubquery(
            dialect: $resolvedConnection->dialect,
            parameterPrefix: '',
        );

        return $resolvedConnection->statementParser->parse(
            sql: $rendered['sql'],
            parameters: $rendered['parameters'],
        );
    }

    public function execute(
        ?ConnectionInterface $connection = null,
    ): ResultSetInterface {
        $resolvedConnection = $connection ?? $this->connection;

        if ($resolvedConnection === null) {
            throw DatabaseException::fromNoConnectionAvailable();
        }

        $statement = $this->compile($resolvedConnection);

        return $resolvedConnection->query(
            sql: $statement->sql,
            parameters: \sizeof($statement->parameters) > 0
                ? \array_combine(\range(1, \sizeof($statement->parameters)), $statement->parameters)
                : [],
            native: true,
        );
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
        $parts = [];
        $parameters = [];

        foreach ($this->members as $index => $member) {
            $alias = 'cq_' . $index;
            $rendered = $member['statement']->renderSubquery(
                dialect: $dialect,
                parameterPrefix: $parameterPrefix . $alias . '_',
            );

            $operator = match ($member['operator']) {
                null => '',
                CompoundOperator::UNION => 'UNION ',
                CompoundOperator::UNION_ALL => 'UNION ALL ',
            };

            $parts[] = $operator . 'SELECT * FROM (' . $rendered['sql'] . ') AS ' . $dialect->identifier($alias);
            $parameters = [
                ...$parameters,
                ...$rendered['parameters'],
            ];
        }

        $sql = \join(' ', $parts);

        if (\sizeof($this->orderBy) > 0) {
            $clauses = [];

            foreach ($this->orderBy as $entry) {
                $clauses[] = $dialect->orderTerm(
                    $dialect->qualifiedIdentifier($entry['column']),
                    $entry['direction'],
                );
            }

            $sql .= ' ORDER BY ' . \join(', ', $clauses);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;

            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }

        return [
            'sql' => $sql,
            'parameters' => $parameters,
        ];
    }

    private function add(
        CompoundOperator $operator,
        SubqueryRenderableInterface ...$members,
    ): static {
        foreach ($members as $member) {
            $this->members[] = [
                'operator' => $operator,
                'statement' => $member,
            ];
        }

        return $this;
    }
}
