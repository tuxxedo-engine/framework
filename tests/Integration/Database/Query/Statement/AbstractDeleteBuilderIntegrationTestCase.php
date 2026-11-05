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

    public function testDeleteWithCommonTableExpressionFiltersRows(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        self::assertSame(
            2,
            $this->connection->delete(
                table: 'posts',
            )
                ->with(
                    name: 'bobs',
                    query: $this->connection->select(
                        table: 'users',
                    )
                        ->select('id')
                        ->where('name', 'Bob'),
                )
                ->whereIn(
                    column: 'user_id',
                    values: $this->connection->select(
                        table: 'bobs',
                    )
                        ->select('bobs.id'),
                )
                ->execute()
                ->affectedRows,
        );
    }

    public function testDeleteWithRecursiveRemovesSubtree(): void
    {
        $this->createNodesSchema();
        $this->seedNodes();

        $this->connection->delete(
            table: 'nodes',
        )
            ->withRecursive(
                name: 'tree',
                anchor: $this->connection->select(
                    table: 'nodes',
                )
                    ->select('id')
                    ->where('id', 2),
                recursive: $this->connection->select(
                    table: 'nodes',
                    alias: 'n',
                )
                    ->select('n.id')
                    ->innerJoin(
                        table: 'tree',
                        first: 'tree.id',
                        second: 'n.parent_id',
                    ),
            )
            ->whereIn(
                column: 'id',
                values: $this->connection->select(
                    table: 'tree',
                )
                    ->select('tree.id'),
            )
            ->execute();

        $names = [];
        $rows = $this->connection->query(
            sql: 'SELECT name FROM nodes ORDER BY id',
            native: true,
        );

        foreach ($rows as $row) {
            $names[] = $row->properties['name'];
        }

        self::assertSame(
            [
                'root',
                'b',
                'loop1',
                'loop2',
            ],
            $names,
        );
    }
}
