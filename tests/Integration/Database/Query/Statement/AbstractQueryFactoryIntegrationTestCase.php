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

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Query\Statement\Query;

abstract class AbstractQueryFactoryIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    public function testInsertViaStaticFactory(): void
    {
        $this->createUsersSchema();

        $result = Query::insert(
            table: 'users',
            connection: $this->connection,
        )
            ->set(
                column: 'name',
                value: 'Alice',
            )
            ->execute();

        self::assertSame(
            1,
            $result->affectedRows,
        );
    }

    public function testInsertBulkViaStaticFactory(): void
    {
        $this->createUsersSchema();

        $result = Query::insertBulk(
            table: 'users',
            connection: $this->connection,
        )
            ->values(
                [
                    'name' => 'Alice',
                    'email' => 'alice@example.test',
                ],
                [
                    'name' => 'Bob',
                    'email' => 'bob@example.test',
                ],
            )
            ->execute();

        self::assertSame(
            2,
            $result->affectedRows,
        );
    }

    public function testDropTableViaStaticFactory(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider()->widgetsSchemaSql(),
            native: true,
        );

        Query::dropTable(
            table: 'widgets',
            connection: $this->connection,
        )->execute();

        $this->expectException(DatabaseException::class);

        $this->connection->query(
            sql: 'SELECT COUNT(*) FROM widgets',
            native: true,
        );
    }

    public function testCountViaStaticFactory(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $total = Query::count(
            table: 'users',
            connection: $this->connection,
        )->count(
            connection: $this->connection,
        );

        self::assertSame(
            3,
            $total,
        );
    }

    public function testDeleteViaStaticFactory(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = Query::delete(
            table: 'users',
            connection: $this->connection,
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

    public function testExistsViaStaticFactory(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $exists = Query::exists(
            table: 'users',
            connection: $this->connection,
        )
            ->where(
                column: 'name',
                value: 'Alice',
            )
            ->exists(
                connection: $this->connection,
            );

        self::assertTrue(
            $exists,
        );
    }

    public function testUpdateViaStaticFactory(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = Query::update(
            table: 'users',
            connection: $this->connection,
        )
            ->set(
                column: 'email',
                value: 'newalice@example.test',
            )
            ->where(
                column: 'name',
                value: 'Alice',
            )
            ->execute();

        self::assertSame(
            1,
            $result->affectedRows,
        );
    }

    public function testSelectViaStaticFactory(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = Query::select(
            table: 'users',
            connection: $this->connection,
        )->execute();

        self::assertCount(
            3,
            $result,
        );
    }

    public function testStatementExecuteWithoutConnectionThrows(): void
    {
        $statement = Query::select(
            table: 'users',
        );

        $this->expectException(DatabaseException::class);

        $statement->execute();
    }

    public function testStatementCompileWithoutConnectionThrows(): void
    {
        $statement = Query::select(
            table: 'users',
        );

        $this->expectException(DatabaseException::class);

        $statement->compile();
    }

    public function testTableStatementExecuteWithoutConnectionThrows(): void
    {
        $statement = Query::dropTable(
            table: 'widgets',
        );

        $this->expectException(DatabaseException::class);

        $statement->execute();
    }

    public function testTableStatementCompileWithoutConnectionThrows(): void
    {
        $statement = Query::dropTable(
            table: 'widgets',
        );

        $this->expectException(DatabaseException::class);

        $statement->compile();
    }

    public function testTableStatementCompileReturnsParserResult(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider()->widgetsSchemaSql(),
            native: true,
        );

        $compiled = Query::dropTable(
            table: 'widgets',
            connection: $this->connection,
        )->compile();

        self::assertStringContainsString(
            'DROP TABLE',
            $compiled->sql,
        );
    }

    public function testHasConstraintsReturnsFalseOnFreshBuilder(): void
    {
        $builder = $this->connection->select(
            table: 'users',
        );

        self::assertFalse(
            $builder->hasConstraints(),
        );

        self::assertFalse(
            $builder->hasConditionConstraints(),
        );

        self::assertFalse(
            $builder->hasJoinConstraints(),
        );
    }

    public function testHasConditionConstraintsReturnsTrueAfterWhere(): void
    {
        $builder = $this->connection->select(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'Alice',
            );

        self::assertTrue(
            $builder->hasConstraints(),
        );

        self::assertTrue(
            $builder->hasConditionConstraints(),
        );

        self::assertFalse(
            $builder->hasJoinConstraints(),
        );
    }

    public function testHasJoinConstraintsReturnsTrueAfterJoin(): void
    {
        $builder = $this->connection->select(
            table: 'users',
        )
            ->innerJoin(
                table: 'posts',
                first: 'users.id',
                second: 'posts.user_id',
            );

        self::assertTrue(
            $builder->hasConstraints(),
        );

        self::assertFalse(
            $builder->hasConditionConstraints(),
        );

        self::assertTrue(
            $builder->hasJoinConstraints(),
        );
    }
}
