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

use Tuxxedo\Database\Query\Statement\CountStatement;

abstract class AbstractCountBuilderIntegrationTestCase extends AbstractWhereClauseBuilderIntegrationTestCase
{
    protected function runWhereMatch(
        \Closure $configureBuilder,
    ): bool {
        $builder = $this->connection->count(
            table: 'users',
        );

        $configureBuilder($builder);

        /** @var CountStatement $builder */
        return $builder->count() > 0;
    }

    public function testCountDefaultsToStar(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $total = $this->connection->count(
            table: 'users',
        )->count();

        self::assertSame(
            3,
            $total,
        );
    }

    public function testCountWithSpecificColumnIgnoresNulls(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $total = $this->connection->count(
            table: 'users',
        )
            ->column(
                column: 'email',
            )
            ->count();

        self::assertSame(
            2,
            $total,
        );
    }

    public function testCountWithDistinctFoldsDuplicates(): void
    {
        $this->createUsersSchema();

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Alice', 'a1@example.test')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Alice', 'a2@example.test')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Bob', 'b@example.test')",
            native: true,
        );

        $distinct = $this->connection->count(
            table: 'users',
        )
            ->column(
                column: 'name',
            )
            ->distinct()
            ->count();

        self::assertSame(
            2,
            $distinct,
        );
    }

    public function testCountReturnsZeroOnEmptyTable(): void
    {
        $this->createUsersSchema();

        $total = $this->connection->count(
            table: 'users',
        )->count();

        self::assertSame(
            0,
            $total,
        );
    }

    public function testCountReturnsInt(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $total = $this->connection->count(
            table: 'users',
        )->count();

        self::assertSame(
            3,
            $total,
        );
    }
}
