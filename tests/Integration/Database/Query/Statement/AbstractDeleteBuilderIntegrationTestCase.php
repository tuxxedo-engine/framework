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

use Tuxxedo\Database\Query\Statement\DeleteStatement;

abstract class AbstractDeleteBuilderIntegrationTestCase extends AbstractWhereClauseBuilderIntegrationTestCase
{
    protected function runWhereMatch(
        \Closure $configureBuilder,
    ): bool {
        $builder = $this->connection->delete(
            table: 'users',
        );

        $configureBuilder($builder);

        /** @var DeleteStatement $builder */
        return $builder->execute()->affectedRows > 0;
    }

    public function testDeleteWithoutWhereRemovesAllRows(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->delete(
            table: 'users',
        )->execute();

        self::assertSame(
            3,
            $result->affectedRows,
        );
    }

    public function testDeleteFilteredWithWhereRemovesOnlyMatchingRows(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->delete(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'Bob',
            )
            ->execute();

        self::assertSame(
            1,
            $result->affectedRows,
        );
    }

    public function testDeleteNoMatchReturnsZeroAffectedRows(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->delete(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->execute();

        self::assertSame(
            0,
            $result->affectedRows,
        );
    }

    public function testDeleteActuallyRemovesRowFromTable(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $this->connection->delete(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'Bob',
            )
            ->execute();

        $names = [];
        $rows = $this->connection->query(
            sql: 'SELECT name FROM users ORDER BY id',
            native: true,
        );

        foreach ($rows as $row) {
            $names[] = $row->properties['name'];
        }

        self::assertSame(
            [
                'Alice',
                'Charlie',
            ],
            $names,
        );
    }

}
