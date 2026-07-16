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

namespace Integration\Model;

use Fixture\Model\User;
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Model\ModelException;

class QueryIntegrationTest extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createUsersTable();
        $this->seedUsers();
    }

    private function seedUsers(): void
    {
        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score) VALUES (1, 'Alice', 'alice@example.test', 1, 5, 12.5)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score) VALUES (2, 'Bob', 'bob@example.test', 1, 2, 3.0)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score) VALUES (3, 'Charlie', 'charlie@example.test', 0, 0, 0.0)",
            native: true,
        );
    }

    public function testFindByIdentifierReturnsHydratedModel(): void
    {
        $user = $this->modelsManager->findByIdentifier(
            class: User::class,
            id: 1,
        );

        self::assertNotNull(
            $user,
        );

        self::assertSame(
            'Alice',
            $user->name,
        );

        self::assertSame(
            'alice@example.test',
            $user->email,
        );
    }

    public function testFindByIdentifierReturnsNullForUnknownId(): void
    {
        $user = $this->modelsManager->findByIdentifier(
            class: User::class,
            id: 999,
        );

        self::assertNull(
            $user,
        );
    }

    public function testFetchByIdentifierThrowsForUnknownId(): void
    {
        $this->expectException(ModelException::class);

        (void) $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 999,
        );
    }

    public function testFindFirstReturnsFirstMatchingRow(): void
    {
        $user = $this->modelsManager->findFirst(
            class: User::class,
            criteria: static function (SelectStatementInterface $statement): void {
                $statement->where(
                    column: 'name',
                    value: 'Bob',
                );
            },
        );

        self::assertNotNull(
            $user,
        );

        self::assertSame(
            2,
            $user->id,
        );
    }

    public function testFindFirstReturnsNullWhenNoRowsMatch(): void
    {
        $user = $this->modelsManager->findFirst(
            class: User::class,
            criteria: static function (SelectStatementInterface $statement): void {
                $statement->where(
                    column: 'name',
                    value: 'nobody',
                );
            },
        );

        self::assertNull(
            $user,
        );
    }

    public function testFetchThrowsWhenNoRowsMatch(): void
    {
        $this->expectException(ModelException::class);

        (void) $this->modelsManager->fetch(
            class: User::class,
            criteria: static function (SelectStatementInterface $statement): void {
                $statement->where(
                    column: 'name',
                    value: 'nobody',
                );
            },
        );
    }

    public function testFindAllYieldsAllRows(): void
    {
        $users = \iterator_to_array(
            $this->modelsManager->findAll(class: User::class),
        );

        self::assertCount(
            3,
            $users,
        );
    }

    public function testFindAllWithCriteriaFiltersRows(): void
    {
        $users = \iterator_to_array(
            $this->modelsManager->findAll(
                class: User::class,
                criteria: static function (SelectStatementInterface $statement): void {
                    $statement->where(
                        column: 'isActive',
                        value: 1,
                    );
                },
            ),
        );

        self::assertCount(
            2,
            $users,
        );
    }

    public function testCountWithCriteriaReturnsFilteredCount(): void
    {
        $count = $this->modelsManager->count(
            class: User::class,
            criteria: static function ($statement): void {
                $statement->where(
                    column: 'isActive',
                    value: 1,
                );
            },
        );

        self::assertSame(
            2,
            $count,
        );
    }

    public function testExistsReturnsTrueForMatchingRow(): void
    {
        $exists = $this->modelsManager->exists(
            class: User::class,
            criteria: static function ($statement): void {
                $statement->where(
                    column: 'email',
                    value: 'alice@example.test',
                );
            },
        );

        self::assertTrue(
            $exists,
        );
    }

    public function testExistsReturnsFalseForMissingRow(): void
    {
        $exists = $this->modelsManager->exists(
            class: User::class,
            criteria: static function ($statement): void {
                $statement->where(
                    column: 'email',
                    value: 'nobody@example.test',
                );
            },
        );

        self::assertFalse(
            $exists,
        );
    }

    public function testExistsByIdentifierReturnsTrueForKnownId(): void
    {
        self::assertTrue(
            $this->modelsManager->existsByIdentifier(
                class: User::class,
                id: 1,
            ),
        );
    }

    public function testExistsByIdentifierReturnsFalseForUnknownId(): void
    {
        self::assertFalse(
            $this->modelsManager->existsByIdentifier(
                class: User::class,
                id: 999,
            ),
        );
    }

    public function testQueryBuilderFirstMatchesWhere(): void
    {
        $user = $this->modelsManager->query(class: User::class)
            ->where(
                column: 'name',
                value: 'Alice',
            )
            ->first();

        self::assertNotNull(
            $user,
        );

        self::assertSame(
            1,
            $user->id,
        );
    }

    public function testQueryBuilderChainedWhereReturnsFilteredResults(): void
    {
        $users = \iterator_to_array(
            $this->modelsManager->query(class: User::class)
                ->where(
                    column: 'isActive',
                    value: 1,
                )
                ->where(
                    column: 'postCount',
                    value: 5,
                )
                ->fetchAll(),
        );

        $users = \array_values($users);

        self::assertCount(
            1,
            $users,
        );

        self::assertSame(
            'Alice',
            $users[0]->name,
        );
    }

    public function testQueryBuilderOrderByAscending(): void
    {
        $users = \iterator_to_array(
            $this->modelsManager->query(class: User::class)
                ->orderBy(column: 'postCount')
                ->fetchAll(),
        );

        $ids = \array_map(
            static fn (User $user): int => (int) $user->id,
            \array_values($users),
        );

        self::assertSame(
            [
                3,
                2,
                1,
            ],
            $ids,
        );
    }

    public function testQueryBuilderCountReflectsCriteria(): void
    {
        $count = $this->modelsManager->query(class: User::class)
            ->where(
                column: 'isActive',
                value: 1,
            )
            ->count();

        self::assertSame(
            2,
            $count,
        );
    }

    public function testQueryBuilderIsImmutable(): void
    {
        $base = $this->modelsManager->query(class: User::class);

        $filtered = $base->where(
            column: 'name',
            value: 'Alice',
        );

        self::assertNotSame(
            $base,
            $filtered,
        );

        self::assertSame(
            3,
            $base->count(),
        );

        self::assertSame(
            1,
            $filtered->count(),
        );
    }

    public function testQueryBuilderWhereInFiltersMultipleIdentifiers(): void
    {
        $users = \iterator_to_array(
            $this->modelsManager->query(class: User::class)
                ->whereIn(
                    column: 'id',
                    values: [
                        1,
                        3,
                    ],
                )
                ->fetchAll(),
        );

        self::assertCount(
            2,
            $users,
        );
    }
}
