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

namespace Integration\Database\Query\Statement;

use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;
use Tuxxedo\Database\SqlException;

abstract class AbstractWhereClauseBuilderIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    /**
     * @param \Closure(WhereStatementInterface $builder): void $configureBuilder
     */
    abstract protected function runWhereMatch(
        \Closure $configureBuilder,
    ): bool;

    public function testWhereMatchesRow(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'Alice',
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereWithoutMatchDoesNotMatch(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                );
            },
        );

        self::assertFalse(
            $matched,
        );
    }

    public function testEmptyTableDoesNotMatch(): void
    {
        $this->createUsersSchema();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
            },
        );

        self::assertFalse(
            $matched,
        );
    }

    public function testExplicitOperator(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'Alice',
                    operator: ConditionOperator::NOT_EQUALS,
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testStringOperator(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'id',
                    value: 2,
                    operator: '>=',
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testUnknownStringOperatorThrows(): void
    {
        $this->createUsersSchema();

        $this->expectException(SqlException::class);

        $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'id',
                    value: 1,
                    operator: 'nonsense',
                );
            },
        );
    }

    public function testAndChain(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'Alice',
                )
                    ->where(
                        column: 'email',
                        value: 'alice@example.test',
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrChain(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhere(
                        column: 'name',
                        value: 'Alice',
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereNull(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->whereNull(
                    column: 'email',
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereNotNull(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->whereNotNull(
                    column: 'email',
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrWhereNull(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhereNull(
                        column: 'email',
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrWhereNotNull(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhereNotNull(
                        column: 'email',
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereIn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->whereIn(
                    column: 'name',
                    values: [
                        'Alice',
                        'nobody',
                    ],
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereNotIn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->whereNotIn(
                    column: 'name',
                    values: [
                        'Alice',
                        'Bob',
                        'Charlie',
                    ],
                );
            },
        );

        self::assertFalse(
            $matched,
        );
    }

    public function testOrWhereIn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhereIn(
                        column: 'name',
                        values: [
                            'Alice',
                        ],
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrWhereNotIn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhereNotIn(
                        column: 'name',
                        values: [
                            'Alice',
                            'Bob',
                        ],
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereBetween(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->whereBetween(
                    column: 'id',
                    from: 2,
                    to: 3,
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereNotBetween(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->whereNotBetween(
                    column: 'id',
                    from: 2,
                    to: 3,
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrWhereBetween(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhereBetween(
                        column: 'id',
                        from: 1,
                        to: 2,
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrWhereNotBetween(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhereNotBetween(
                        column: 'id',
                        from: 100,
                        to: 200,
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereLike(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->whereLike(
                    column: 'email',
                    pattern: '%alice%',
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereNotLike(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->whereNotLike(
                    column: 'name',
                    pattern: '%X%',
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrWhereLike(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhereLike(
                        column: 'email',
                        pattern: '%alice%',
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrWhereNotLike(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhereNotLike(
                        column: 'name',
                        pattern: '%X%',
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereColumn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->whereColumn(
                    column: 'name',
                    other: 'name',
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrWhereColumn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhereColumn(
                        column: 'id',
                        other: 'id',
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereRaw(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->whereRaw(
                    sql: 'LOWER(name) = :name',
                    bindings: [
                        'name' => 'alice',
                    ],
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereGroup(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->whereGroup(
                    callback: static function (WhereStatementInterface $inner): void {
                        $inner->where(
                            column: 'name',
                            value: 'Alice',
                        )
                            ->where(
                                column: 'email',
                                value: 'alice@example.test',
                            );
                    },
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrWhereGroup(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhereGroup(
                        callback: static function (WhereStatementInterface $inner): void {
                            $inner->where(
                                column: 'name',
                                value: 'Alice',
                            );
                        },
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereNot(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->whereNot(
                    callback: static function (WhereStatementInterface $inner): void {
                        $inner->where(
                            column: 'name',
                            value: 'nobody',
                        );
                    },
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrWhereNot(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhereNot(
                        callback: static function (WhereStatementInterface $inner): void {
                            $inner->where(
                                column: 'name',
                                value: 'nobody',
                            );
                        },
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereGroupNoOpDoesNotEmitGroup(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q): void {
                $q->whereGroup(
                    callback: static function (WhereStatementInterface $inner): void {
                    },
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereSubqueryUsingSelectStatement(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $subquery = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->where(
                column: 'name',
                value: 'Alice',
            );

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q) use ($subquery): void {
                $q->where(
                    column: 'id',
                    value: $subquery,
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereInSubqueryUsingSelectStatement(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $subquery = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->whereIn(
                column: 'name',
                values: [
                    'Alice',
                    'Bob',
                ],
            );

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q) use ($subquery): void {
                $q->whereIn(
                    column: 'id',
                    values: $subquery,
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereNotInSubqueryUsingSelectStatement(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $subquery = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->where(
                column: 'name',
                value: 'Alice',
            );

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q) use ($subquery): void {
                $q->whereNotIn(
                    column: 'id',
                    values: $subquery,
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrWhereSubqueryUsingSelectStatement(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $subquery = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->where(
                column: 'name',
                value: 'Alice',
            );

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q) use ($subquery): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhere(
                        column: 'id',
                        value: $subquery,
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrWhereInSubqueryUsingSelectStatement(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $subquery = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->where(
                column: 'name',
                value: 'Alice',
            );

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q) use ($subquery): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhereIn(
                        column: 'id',
                        values: $subquery,
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrWhereNotInSubqueryUsingSelectStatement(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $subquery = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->where(
                column: 'name',
                value: 'Alice',
            );

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q) use ($subquery): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhereNotIn(
                        column: 'id',
                        values: $subquery,
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereExistsSubquery(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $subquery = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->where(
                column: 'name',
                value: 'Alice',
            );

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q) use ($subquery): void {
                $q->whereExists(
                    subquery: $subquery,
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testWhereNotExistsSubquery(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $subquery = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->where(
                column: 'name',
                value: 'nobody',
            );

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q) use ($subquery): void {
                $q->whereNotExists(
                    subquery: $subquery,
                );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrWhereExistsSubquery(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $subquery = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->where(
                column: 'name',
                value: 'Alice',
            );

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q) use ($subquery): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhereExists(
                        subquery: $subquery,
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }

    public function testOrWhereNotExistsSubquery(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $subquery = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->where(
                column: 'name',
                value: 'nobody',
            );

        $matched = $this->runWhereMatch(
            configureBuilder: static function (WhereStatementInterface $q) use ($subquery): void {
                $q->where(
                    column: 'name',
                    value: 'nobody',
                )
                    ->orWhereNotExists(
                        subquery: $subquery,
                    );
            },
        );

        self::assertTrue(
            $matched,
        );
    }
}
