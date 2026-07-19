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

abstract class AbstractQueryIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createUsersTable();
        $this->createProfilesTable();
        $this->seedUsers();
    }

    private function seedUsers(): void
    {
        $this->seedUser(
            id: 1,
            name: 'Alice',
            isActive: 1,
            postCount: 5,
            score: 12.5,
        );

        $this->seedUser(
            id: 2,
            name: 'Bob',
            isActive: 1,
            postCount: 2,
            score: 3.0,
        );

        $this->seedUser(
            id: 3,
            name: 'Charlie',
            isActive: 0,
            postCount: 0,
            score: 0.0,
        );
    }

    private function seedUser(
        int $id,
        string $name,
        int $isActive,
        int $postCount,
        float $score,
    ): void {
        $this->connection->insert(
            table: 'users',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->set(column: 'email', value: \strtolower($name) . '@example.test')
            ->set(column: 'isActive', value: $isActive)
            ->set(column: 'postCount', value: $postCount)
            ->set(column: 'score', value: $score)
            ->execute();
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
            $this->modelsManager->findAll(User::class),
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
        $user = $this->modelsManager->query(User::class)
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
            $this->modelsManager->query(User::class)
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
            $this->modelsManager->query(User::class)
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
        $count = $this->modelsManager->query(User::class)
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
        $base = $this->modelsManager->query(User::class);

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
            $this->modelsManager->query(User::class)
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
