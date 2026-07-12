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

use Fixture\Database\HydratableTestUser;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\ResultRow;
use Tuxxedo\Database\Driver\ResultRowInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;

abstract class AbstractResultSetIntegrationTestCase extends TestCase
{
    protected ConnectionInterface $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createConnection();
        $this->connection->connect();

        $this->createUsersSchemaWithSampleRows();
    }

    protected function tearDown(): void
    {
        if ($this->connection->isConnected()) {
            $this->connection->close();
        }
    }

    abstract protected function createConnection(): ConnectionInterface;

    abstract protected function createUsersSchemaWithSampleRows(): void;

    protected function selectAllUsers(): ResultSetInterface
    {
        return $this->connection->query(
            sql: 'SELECT id, name, email FROM users ORDER BY id',
            native: true,
        );
    }

    protected function selectNoUsers(): ResultSetInterface
    {
        return $this->connection->query(
            sql: 'SELECT id, name, email FROM users WHERE 1 = 0',
            native: true,
        );
    }

    public function testFetchAssocReturnsAssocArrayWithColumnNames(): void
    {
        $row = $this->selectAllUsers()->fetchAssoc();

        self::assertArrayHasKey(
            'id',
            $row,
        );

        self::assertArrayHasKey(
            'name',
            $row,
        );

        self::assertArrayHasKey(
            'email',
            $row,
        );

        self::assertSame(
            'Alice',
            $row['name'],
        );
    }

    public function testFetchRowReturnsNumericallyIndexedArray(): void
    {
        $row = $this->selectAllUsers()->fetchRow();

        self::assertArrayHasKey(
            0,
            $row,
        );

        self::assertArrayHasKey(
            1,
            $row,
        );

        self::assertArrayHasKey(
            2,
            $row,
        );

        self::assertArrayNotHasKey(
            'name',
            $row,
        );

        self::assertSame(
            'Alice',
            $row[1],
        );
    }

    public function testFetchObjectWithDefaultClassReturnsResultRow(): void
    {
        $row = $this->selectAllUsers()->fetchObject();

        self::assertInstanceOf(
            ResultRow::class,
            $row,
        );

        self::assertSame(
            'Alice',
            $row->properties['name'],
        );
    }

    public function testFetchObjectWithClosureAppliesClosure(): void
    {
        $row = $this->selectAllUsers()->fetchObject(
            class: static function (array $properties): object {
                /** @var string $name */
                $name = $properties['name'];

                return (object) [
                    'boxedName' => '[' . $name . ']',
                ];
            },
        );

        self::assertSame(
            '[Alice]',
            $row->boxedName,
        );
    }

    public function testFetchObjectWithHydratableClassResolvesHydratorFromContainer(): void
    {
        $row = $this->selectAllUsers()->fetchObject(
            class: HydratableTestUser::class,
        );

        self::assertInstanceOf(
            HydratableTestUser::class,
            $row,
        );

        self::assertSame(
            'Alice',
            $row->name,
        );
    }

    public function testFetchReturnsResultRowInstance(): void
    {
        $row = $this->selectAllUsers()->fetch();

        self::assertInstanceOf(
            ResultRow::class,
            $row,
        );

        self::assertSame(
            'Alice',
            $row->properties['name'],
        );
    }

    public function testConsecutiveFetchesAdvancePointer(): void
    {
        $result = $this->selectAllUsers();

        self::assertSame(
            'Alice',
            $result->fetchAssoc()['name'],
        );

        self::assertSame(
            'Bob',
            $result->fetchAssoc()['name'],
        );

        self::assertSame(
            'Charlie',
            $result->fetchAssoc()['name'],
        );
    }

    public function testFetchAfterLastRowThrowsFromCannotFetch(): void
    {
        $result = $this->selectAllUsers();

        $result->fetchAssoc();
        $result->fetchAssoc();
        $result->fetchAssoc();

        $this->expectException(DatabaseException::class);

        $result->fetchAssoc();
    }

    public function testFetchAssocOnEmptyResultSetThrowsFromCannotFetch(): void
    {
        $result = $this->selectNoUsers();

        $this->expectException(DatabaseException::class);

        $result->fetchAssoc();
    }

    public function testFetchAllYieldsAllRowsAsResultRowInstances(): void
    {
        $names = [];

        foreach ($this->selectAllUsers()->fetchAll() as $row) {
            self::assertInstanceOf(
                ResultRowInterface::class,
                $row,
            );

            /** @var ResultRowInterface $row */
            $names[] = $row->properties['name'];
        }

        self::assertSame(
            [
                'Alice',
                'Bob',
                'Charlie',
            ],
            $names,
        );
    }

    public function testFetchAllWithHydratableClassProducesHydratedObjects(): void
    {
        $users = [];

        foreach (
            $this->selectAllUsers()->fetchAll(
                class: HydratableTestUser::class,
            ) as $user
        ) {
            self::assertInstanceOf(
                HydratableTestUser::class,
                $user,
            );

            /** @var HydratableTestUser $user */
            $users[] = $user->name;
        }

        self::assertSame(
            [
                'Alice',
                'Bob',
                'Charlie',
            ],
            $users,
        );
    }

    public function testForeachYieldsAllRows(): void
    {
        $collected = [];

        foreach ($this->selectAllUsers() as $key => $row) {
            $collected[$key] = $row->properties['name'];
        }

        self::assertSame(
            [
                0 => 'Alice',
                1 => 'Bob',
                2 => 'Charlie',
            ],
            $collected,
        );
    }

    public function testIteratorKeyStartsAtZeroAndIncrements(): void
    {
        $keys = [];

        foreach ($this->selectAllUsers() as $key => $row) {
            $keys[] = $key;
        }

        self::assertSame(
            [
                0,
                1,
                2,
            ],
            $keys,
        );
    }

    public function testCurrentReturnsResultRowShapeAfterRewind(): void
    {
        $result = $this->selectAllUsers();

        $result->rewind();

        self::assertTrue(
            $result->valid(),
        );

        self::assertInstanceOf(
            ResultRowInterface::class,
            $result->current(),
        );
    }

    public function testValidReturnsFalseAfterExhaustion(): void
    {
        $result = $this->selectAllUsers();

        $result->rewind();

        while ($result->valid()) {
            $result->next();
        }

        self::assertFalse(
            $result->valid(),
        );
    }

    public function testCountReturnsRowCountForNonEmptyResult(): void
    {
        self::assertCount(
            3,
            $this->selectAllUsers(),
        );
    }

    public function testCountReturnsZeroForEmptyResult(): void
    {
        self::assertCount(
            0,
            $this->selectNoUsers(),
        );
    }

    public function testCountAfterPartialFetchStillReturnsFullCount(): void
    {
        $result = $this->selectAllUsers();

        $result->fetchAssoc();

        self::assertCount(
            3,
            $result,
        );
    }

    public function testAffectedRowsIsSetFromInsertResultSet(): void
    {
        $result = $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Dave', 'dave@example.test')",
            native: true,
        );

        self::assertSame(
            1,
            $result->affectedRows,
        );
    }
}
