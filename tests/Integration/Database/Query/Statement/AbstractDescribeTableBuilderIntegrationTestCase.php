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

use Tuxxedo\Database\Query\Statement\Table\ColumnDescriptionInterface;

abstract class AbstractDescribeTableBuilderIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    protected function buildWidgetsTable(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
            autoIncrement: true,
        );

        $table->varchar(
            name: 'email',
            length: 190,
            nullable: false,
        );

        $table->varchar(
            name: 'status',
            length: 20,
            nullable: true,
            default: 'active',
        );

        $table->execute();
    }

    public function testDescribeTableReportsPrimaryKeyColumn(): void
    {
        $this->buildWidgetsTable();

        $columns = $this->connection->describeTable(
            table: 'widgets',
        )->all();

        $id = $this->findByName(
            columns: $columns,
            name: 'id',
        );

        self::assertNotNull($id, 'id column should exist');
        self::assertTrue($id->primary);
        self::assertFalse($id->nullable);
    }

    public function testDescribeTableReportsNonNullColumn(): void
    {
        $this->buildWidgetsTable();

        $columns = $this->connection->describeTable(
            table: 'widgets',
        )->all();

        $email = $this->findByName(
            columns: $columns,
            name: 'email',
        );

        self::assertNotNull($email, 'email column should exist');
        self::assertFalse($email->nullable);
        self::assertFalse($email->primary);
    }

    public function testDescribeTableReportsNullableColumnWithDefault(): void
    {
        $this->buildWidgetsTable();

        $columns = $this->connection->describeTable(
            table: 'widgets',
        )->all();

        $status = $this->findByName(
            columns: $columns,
            name: 'status',
        );

        self::assertNotNull($status, 'status column should exist');
        self::assertTrue($status->nullable);
        self::assertNotNull($status->default);
        self::assertStringContainsString('active', $status->default);
    }

    public function testDescribeTableReportsNonEmptyNativeType(): void
    {
        $this->buildWidgetsTable();

        $columns = $this->connection->describeTable(
            table: 'widgets',
        )->all();

        foreach ($columns as $column) {
            self::assertNotSame('', $column->nativeType);
        }
    }

    public function testByNameReturnsMapKeyedByColumnName(): void
    {
        $this->buildWidgetsTable();

        $byName = $this->connection->describeTable(
            table: 'widgets',
        )->byName();

        self::assertArrayHasKey('id', $byName);
        self::assertArrayHasKey('email', $byName);
        self::assertArrayHasKey('status', $byName);

        foreach ($byName as $name => $column) {
            self::assertSame($name, $column->name);
        }
    }

    /**
     * @param list<ColumnDescriptionInterface> $columns
     */
    private function findByName(
        array $columns,
        string $name,
    ): ?ColumnDescriptionInterface {
        foreach ($columns as $column) {
            if ($column->name === $name) {
                return $column;
            }
        }

        return null;
    }
}
