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

abstract class AbstractExistsBuilderIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    public function testExistsReturnsTrueWhenRowMatches(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'Alice',
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsReturnsFalseWhenNoRowMatches(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->exists();

        self::assertFalse(
            $exists,
        );
    }

    public function testExistsReturnsFalseOnEmptyTable(): void
    {
        $this->createUsersSchema();

        $exists = $this->connection->exists(
            table: 'users',
        )->exists();

        self::assertFalse(
            $exists,
        );
    }

    public function testExistsWithExplicitOperator(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'Alice',
                operator: ConditionOperator::NOT_EQUALS,
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithStringOperator(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'id',
                value: 2,
                operator: '>=',
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithUnknownStringOperatorThrows(): void
    {
        $this->createUsersSchema();

        $this->expectException(SqlException::class);

        $this->connection->exists(
            table: 'users',
        )->where(
            column: 'id',
            value: 1,
            operator: 'nonsense',
        );
    }

    public function testExistsWithAndChain(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'Alice',
            )
            ->where(
                column: 'email',
                value: 'alice@example.test',
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrChain(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhere(
                column: 'name',
                value: 'Alice',
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereNull(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereNull(
                column: 'email',
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereNotNull(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereNotNull(
                column: 'email',
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrWhereNull(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhereNull(
                column: 'email',
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrWhereNotNull(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhereNotNull(
                column: 'email',
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereIn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereIn(
                column: 'name',
                values: [
                    'Alice',
                    'nobody',
                ],
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereNotIn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereNotIn(
                column: 'name',
                values: [
                    'Alice',
                    'Bob',
                    'Charlie',
                ],
            )
            ->exists();

        self::assertFalse(
            $exists,
        );
    }

    public function testExistsWithOrWhereIn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhereIn(
                column: 'name',
                values: [
                    'Alice',
                ],
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrWhereNotIn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhereNotIn(
                column: 'name',
                values: [
                    'Alice',
                    'Bob',
                ],
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereBetween(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereBetween(
                column: 'id',
                from: 2,
                to: 3,
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereNotBetween(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereNotBetween(
                column: 'id',
                from: 2,
                to: 3,
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrWhereBetween(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhereBetween(
                column: 'id',
                from: 1,
                to: 2,
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrWhereNotBetween(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhereNotBetween(
                column: 'id',
                from: 100,
                to: 200,
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereLike(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereLike(
                column: 'email',
                pattern: '%alice%',
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereNotLike(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereNotLike(
                column: 'name',
                pattern: '%X%',
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrWhereLike(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhereLike(
                column: 'email',
                pattern: '%alice%',
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrWhereNotLike(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhereNotLike(
                column: 'name',
                pattern: '%X%',
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereColumn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereColumn(
                column: 'name',
                other: 'name',
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrWhereColumn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhereColumn(
                column: 'id',
                other: 'id',
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereRaw(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereRaw(
                sql: 'LOWER(name) = :name',
                bindings: [
                    'name' => 'alice',
                ],
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereGroup(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereGroup(
                callback: static function (WhereStatementInterface $q): void {
                    $q->where(
                        column: 'name',
                        value: 'Alice',
                    )
                        ->where(
                            column: 'email',
                            value: 'alice@example.test',
                        );
                },
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrWhereGroup(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhereGroup(
                callback: static function (WhereStatementInterface $q): void {
                    $q->where(
                        column: 'name',
                        value: 'Alice',
                    );
                },
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereNot(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereNot(
                callback: static function (WhereStatementInterface $q): void {
                    $q->where(
                        column: 'name',
                        value: 'nobody',
                    );
                },
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrWhereNot(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhereNot(
                callback: static function (WhereStatementInterface $q): void {
                    $q->where(
                        column: 'name',
                        value: 'nobody',
                    );
                },
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereGroupNoOpDoesNotEmitGroup(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereGroup(
                callback: static function (WhereStatementInterface $q): void {
                },
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereSubqueryUsingSelectStatement(): void
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

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'id',
                value: $subquery,
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereInSubqueryUsingSelectStatement(): void
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

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereIn(
                column: 'id',
                values: $subquery,
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereNotInSubqueryUsingSelectStatement(): void
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

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereNotIn(
                column: 'id',
                values: $subquery,
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrWhereSubqueryUsingSelectStatement(): void
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

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhere(
                column: 'id',
                value: $subquery,
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrWhereInSubqueryUsingSelectStatement(): void
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

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhereIn(
                column: 'id',
                values: $subquery,
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrWhereNotInSubqueryUsingSelectStatement(): void
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

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhereNotIn(
                column: 'id',
                values: $subquery,
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereExistsSubquery(): void
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

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereExists(
                subquery: $subquery,
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithWhereNotExistsSubquery(): void
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

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->whereNotExists(
                subquery: $subquery,
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrWhereExistsSubquery(): void
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

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhereExists(
                subquery: $subquery,
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsWithOrWhereNotExistsSubquery(): void
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

        $exists = $this->connection->exists(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->orWhereNotExists(
                subquery: $subquery,
            )
            ->exists();

        self::assertTrue(
            $exists,
        );
    }
}
