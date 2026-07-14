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

namespace Integration\Database;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;

abstract class AbstractConnectionIntegrationTestCase extends TestCase
{
    protected ConnectionInterface $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createConnection(
            role: ConnectionRole::DEFAULT,
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->connection) && $this->connection->isConnected()) {
            $this->connection->close();
        }
    }

    abstract protected function createConnection(
        ConnectionRole $role = ConnectionRole::DEFAULT,
    ): ConnectionInterface;

    public function testIsConnectedFalseBeforeExplicitConnect(): void
    {
        self::assertFalse(
            $this->connection->isConnected(),
        );
    }

    public function testConnectMakesConnectionAlive(): void
    {
        $this->connection->connect();

        self::assertTrue(
            $this->connection->isConnected(),
        );
    }

    public function testCloseTearsDownConnection(): void
    {
        $this->connection->connect();
        $this->connection->close();

        self::assertFalse(
            $this->connection->isConnected(),
        );
    }

    public function testCloseIsIdempotentBeforeConnect(): void
    {
        $this->connection->close();

        self::assertFalse(
            $this->connection->isConnected(),
        );
    }

    public function testConnectIsIdempotentWithoutReconnectFlag(): void
    {
        $this->connection->connect();
        $driverBefore = $this->connection->getDriverInstance();

        $this->connection->connect();
        $driverAfter = $this->connection->getDriverInstance();

        self::assertSame(
            $driverBefore,
            $driverAfter,
        );
    }

    public function testReconnectReplacesDriverInstance(): void
    {
        $this->connection->connect();
        $driverBefore = $this->connection->getDriverInstance();

        $this->connection->connect(
            reconnect: true,
        );
        $driverAfter = $this->connection->getDriverInstance();

        self::assertNotSame(
            $driverBefore,
            $driverAfter,
        );
    }

    public function testReconnectAfterCloseSucceeds(): void
    {
        $this->connection->connect();
        $this->connection->close();

        $this->connection->connect();

        self::assertTrue(
            $this->connection->isConnected(),
        );
    }

    public function testGetDriverInstanceLazilyConnects(): void
    {
        self::assertFalse(
            $this->connection->isConnected(),
        );

        $this->connection->getDriverInstance();

        self::assertTrue(
            $this->connection->isConnected(),
        );
    }

    public function testPingReturnsTrueOnActiveConnection(): void
    {
        $this->connection->connect();

        self::assertTrue(
            $this->connection->ping(),
        );
    }

    public function testPingLazilyConnects(): void
    {
        self::assertFalse(
            $this->connection->isConnected(),
        );

        self::assertTrue(
            $this->connection->ping(),
        );

        self::assertTrue(
            $this->connection->isConnected(),
        );
    }

    public function testServerVersionReturnsNonEmptyString(): void
    {
        self::assertNotSame(
            '',
            $this->connection->serverVersion(),
        );
    }

    public function testNameFromConfigIsExposed(): void
    {
        self::assertSame(
            'test',
            $this->connection->name,
        );
    }

    public function testRoleFromConfigIsExposed(): void
    {
        $readConnection = $this->createConnection(
            role: ConnectionRole::DEFAULT_READ,
        );

        self::assertSame(
            ConnectionRole::DEFAULT_READ,
            $readConnection->role,
        );
    }

    public function testInTransactionFalseBeforeBegin(): void
    {
        self::assertFalse(
            $this->connection->inTransaction(),
        );
    }

    public function testBeginPutsConnectionInTransaction(): void
    {
        $this->connection->begin();

        self::assertTrue(
            $this->connection->inTransaction(),
        );
    }

    public function testCommitEndsTransaction(): void
    {
        $this->connection->begin();
        $this->connection->commit();

        self::assertFalse(
            $this->connection->inTransaction(),
        );
    }

    public function testRollbackEndsTransaction(): void
    {
        $this->connection->begin();
        $this->connection->rollback();

        self::assertFalse(
            $this->connection->inTransaction(),
        );
    }

    public function testBeginThrowsWhenAlreadyInTransaction(): void
    {
        $this->connection->begin();

        $this->expectException(DatabaseException::class);

        $this->connection->begin();
    }

    public function testCommitThrowsWhenNotInTransaction(): void
    {
        $this->expectException(DatabaseException::class);

        $this->connection->commit();
    }

    public function testRollbackThrowsWhenNotInTransaction(): void
    {
        $this->expectException(DatabaseException::class);

        $this->connection->rollback();
    }

    public function testLastInsertIdIsNullBeforeAnyInsert(): void
    {
        $this->connection->connect();

        self::assertNull(
            $this->connection->lastInsertIdAsInt(),
        );

        self::assertNull(
            $this->connection->lastInsertIdAsString(),
        );
    }

    abstract protected function createUsersSchema(): void;

    public function testQueryExecutesRawDdlAndAllowsSubsequentQueries(): void
    {
        $this->createUsersSchema();

        $result = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM users',
            native: true,
        );

        $row = $result->fetchAssoc();

        self::assertEquals(
            0,
            $row['c'],
        );
    }

    public function testInsertViaBuilderWritesRow(): void
    {
        $this->createUsersSchema();

        $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'Alice',
            )
            ->set(
                column: 'email',
                value: 'alice@example.test',
            )
            ->execute();

        $result = $this->connection->query(
            sql: 'SELECT name, email FROM users',
            native: true,
        );

        $row = $result->fetchAssoc();

        self::assertSame(
            'Alice',
            $row['name'],
        );

        self::assertSame(
            'alice@example.test',
            $row['email'],
        );
    }

    public function testLastInsertIdReturnsPositiveIntAfterInsert(): void
    {
        $this->createUsersSchema();

        $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'Alice',
            )
            ->execute();

        $id = $this->connection->lastInsertIdAsInt();

        self::assertNotNull(
            $id,
        );

        self::assertGreaterThan(
            0,
            $id,
        );

        self::assertSame(
            (string) $id,
            $this->connection->lastInsertIdAsString(),
        );
    }

    public function testSelectViaBuilderReadsInsertedRow(): void
    {
        $this->createUsersSchema();

        $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'Alice',
            )
            ->execute();

        $result = $this->connection->select(
            table: 'users',
        )->execute();

        $row = $result->fetchAssoc();

        self::assertSame(
            'Alice',
            $row['name'],
        );
    }

    public function testUpdateAffectsOnlyMatchingRows(): void
    {
        $this->createUsersSchema();

        foreach (['Alice', 'Bob', 'Charlie'] as $name) {
            $this->connection->insert(
                table: 'users',
            )
                ->set(
                    column: 'name',
                    value: $name,
                )
                ->execute();
        }

        $result = $this->connection->update(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'Renamed',
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

    public function testDeleteAffectsOnlyMatchingRows(): void
    {
        $this->createUsersSchema();

        foreach (['Alice', 'Bob', 'Charlie'] as $name) {
            $this->connection->insert(
                table: 'users',
            )
                ->set(
                    column: 'name',
                    value: $name,
                )
                ->execute();
        }

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

        $remaining = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM users',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            2,
            $remaining['c'],
        );
    }

    public function testTransactionCommitMakesInsertVisible(): void
    {
        $this->createUsersSchema();

        $this->connection->begin();

        $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'Alice',
            )
            ->execute();

        $this->connection->commit();

        $count = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM users',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            1,
            $count['c'],
        );
    }

    public function testTransactionRollbackDiscardsInsert(): void
    {
        $this->createUsersSchema();

        $this->connection->begin();

        $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'Alice',
            )
            ->execute();

        $this->connection->rollback();

        $count = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM users',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            0,
            $count['c'],
        );
    }

    public function testTransactionClosureCommitsOnReturn(): void
    {
        $this->createUsersSchema();

        $this->connection->transaction(
            transaction: function ($connection): void {
                $connection->insert(
                    table: 'users',
                )
                    ->set(
                        column: 'name',
                        value: 'Alice',
                    )
                    ->execute();
            },
        );

        self::assertFalse(
            $this->connection->inTransaction(),
        );

        $count = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM users',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            1,
            $count['c'],
        );
    }

    public function testTransactionClosureRollsBackOnException(): void
    {
        $this->createUsersSchema();

        $caught = null;

        try {
            $this->connection->transaction(
                transaction: function ($connection): void {
                    $connection->insert(
                        table: 'users',
                    )
                        ->set(
                            column: 'name',
                            value: 'Alice',
                        )
                        ->execute();

                    throw new \RuntimeException(
                        message: 'user closure aborts',
                    );
                },
            );
        } catch (\RuntimeException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(
            \RuntimeException::class,
            $caught,
        );

        self::assertSame(
            'user closure aborts',
            $caught->getMessage(),
        );

        self::assertFalse(
            $this->connection->inTransaction(),
        );

        $count = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM users',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            0,
            $count['c'],
        );
    }

    public function testTransactionClosureReturnsClosureResult(): void
    {
        $this->createUsersSchema();

        $returned = $this->connection->transaction(
            transaction: static fn (): string => 'closure-return-value',
        );

        self::assertSame(
            'closure-return-value',
            $returned,
        );
    }

    public function testNestedTransactionCommitsInnerViaSavepoint(): void
    {
        $this->createUsersSchema();

        $this->connection->begin();

        $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'outer',
            )
            ->execute();

        $this->connection->nestedTransaction(
            transaction: function ($connection): void {
                $connection->insert(
                    table: 'users',
                )
                    ->set(
                        column: 'name',
                        value: 'inner',
                    )
                    ->execute();
            },
        );

        $this->connection->commit();

        $count = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM users',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            2,
            $count['c'],
        );
    }

    public function testNestedTransactionRollsBackInnerButKeepsOuter(): void
    {
        $this->createUsersSchema();

        $this->connection->begin();

        $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'outer',
            )
            ->execute();

        $caught = null;

        try {
            $this->connection->nestedTransaction(
                transaction: function ($connection): void {
                    $connection->insert(
                        table: 'users',
                    )
                        ->set(
                            column: 'name',
                            value: 'inner',
                        )
                        ->execute();

                    throw new \RuntimeException(
                        message: 'inner aborts',
                    );
                },
            );
        } catch (\RuntimeException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(
            \RuntimeException::class,
            $caught,
        );

        self::assertTrue(
            $this->connection->inTransaction(),
        );

        $this->connection->commit();

        $rows = $this->connection->query(
            sql: 'SELECT name FROM users',
            native: true,
        );

        $names = [];

        foreach ($rows as $row) {
            $names[] = $row->properties['name'];
        }

        self::assertSame(
            ['outer'],
            $names,
        );
    }

    public function testNestedTransactionWithoutOuterOpensOwnTransaction(): void
    {
        $this->createUsersSchema();

        self::assertFalse(
            $this->connection->inTransaction(),
        );

        $this->connection->nestedTransaction(
            transaction: function ($connection): void {
                $connection->insert(
                    table: 'users',
                )
                    ->set(
                        column: 'name',
                        value: 'Alice',
                    )
                    ->execute();
            },
        );

        self::assertFalse(
            $this->connection->inTransaction(),
        );

        $count = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM users',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            1,
            $count['c'],
        );
    }

    public function testDirectSavepointRollbackDiscardsOnlyPostSavepointChanges(): void
    {
        $this->createUsersSchema();

        $this->connection->begin();

        $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'before-savepoint',
            )
            ->execute();

        $savepoint = $this->connection->savepoint();

        $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'after-savepoint',
            )
            ->execute();

        $this->connection->rollbackToSavepoint(
            name: $savepoint,
        );

        $this->connection->releaseSavepoint(
            name: $savepoint,
        );

        $this->connection->commit();

        $rows = $this->connection->query(
            sql: 'SELECT name FROM users',
            native: true,
        );

        $names = [];

        foreach ($rows as $row) {
            $names[] = $row->properties['name'];
        }

        self::assertSame(
            ['before-savepoint'],
            $names,
        );
    }

    public function testSavepointThrowsOutsideTransaction(): void
    {
        $this->expectException(DatabaseException::class);

        (void) $this->connection->savepoint();
    }

    public function testTransactionClosureRollsBackOnError(): void
    {
        $this->createUsersSchema();

        $caught = null;

        try {
            $this->connection->transaction(
                transaction: function ($connection): void {
                    $connection->insert(
                        table: 'users',
                    )
                        ->set(
                            column: 'name',
                            value: 'Alice',
                        )
                        ->execute();

                    throw new \Error(
                        message: 'unrecoverable closure error',
                    );
                },
            );
        } catch (\Error $error) {
            $caught = $error;
        }

        self::assertInstanceOf(
            \Error::class,
            $caught,
        );

        self::assertSame(
            'unrecoverable closure error',
            $caught->getMessage(),
        );

        self::assertFalse(
            $this->connection->inTransaction(),
        );

        $count = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM users',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            0,
            $count['c'],
        );
    }

    public function testNestedTransactionClosureRollsBackOnErrorButKeepsOuter(): void
    {
        $this->createUsersSchema();

        $this->connection->begin();

        $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'outer',
            )
            ->execute();

        $caught = null;

        try {
            $this->connection->nestedTransaction(
                transaction: function ($connection): void {
                    $connection->insert(
                        table: 'users',
                    )
                        ->set(
                            column: 'name',
                            value: 'inner',
                        )
                        ->execute();

                    throw new \Error(
                        message: 'inner error',
                    );
                },
            );
        } catch (\Error $error) {
            $caught = $error;
        }

        self::assertInstanceOf(
            \Error::class,
            $caught,
        );

        self::assertTrue(
            $this->connection->inTransaction(),
        );

        $this->connection->commit();

        $rows = $this->connection->query(
            sql: 'SELECT name FROM users',
            native: true,
        );

        $names = [];

        foreach ($rows as $row) {
            $names[] = $row->properties['name'];
        }

        self::assertSame(
            ['outer'],
            $names,
        );
    }

    public function testInsertBulkWritesMultipleRows(): void
    {
        $this->createUsersSchema();

        $this->connection->insertBulk(
            table: 'users',
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
                [
                    'name' => 'Charlie',
                    'email' => null,
                ],
            )
            ->execute();

        $count = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM users',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            3,
            $count['c'],
        );
    }

    public function testExistsReturnsTrueForMatchingRow(): void
    {
        $this->createUsersSchema();

        $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'Alice',
            )
            ->execute();

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

    public function testExistsReturnsFalseForMissingRow(): void
    {
        $this->createUsersSchema();

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

    public function testCountReturnsRowCount(): void
    {
        $this->createUsersSchema();

        foreach (['Alice', 'Bob', 'Charlie'] as $name) {
            $this->connection->insert(
                table: 'users',
            )
                ->set(
                    column: 'name',
                    value: $name,
                )
                ->execute();
        }

        $total = $this->connection->count(
            table: 'users',
        )->count();

        self::assertSame(
            3,
            $total,
        );
    }

    public function testCountWithWhereReturnsFilteredCount(): void
    {
        $this->createUsersSchema();

        foreach (['Alice', 'Bob', 'Bob'] as $name) {
            $this->connection->insert(
                table: 'users',
            )
                ->set(
                    column: 'name',
                    value: $name,
                )
                ->execute();
        }

        $bobs = $this->connection->count(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'Bob',
            )
            ->count();

        self::assertSame(
            2,
            $bobs,
        );
    }

    public function testCreateTableViaBuilderCreatesUsableTable(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->text(
            name: 'label',
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'id',
                value: 1,
            )
            ->set(
                column: 'label',
                value: 'first',
            )
            ->execute();

        $count = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            1,
            $count['c'],
        );
    }

    public function testDropTableRemovesTable(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->execute();

        $this->connection->dropTable(
            table: 'widgets',
        )->execute();

        $this->expectException(DatabaseException::class);

        $this->connection->query(
            sql: 'SELECT COUNT(*) FROM widgets',
            native: true,
        );
    }
}
