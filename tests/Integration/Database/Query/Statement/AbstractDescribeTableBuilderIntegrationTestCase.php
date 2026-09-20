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

use Tuxxedo\Database\Query\Statement\Table\Column\CharColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DecimalColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\IntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\VarcharColumn;
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

    public function testToColumnReversesIntegerPrimaryKey(): void
    {
        $this->buildWidgetsTable();

        $byName = $this->connection->describeTable(
            table: 'widgets',
        )->byName();

        $column = $byName['id']->toColumn();

        self::assertInstanceOf(IntegerColumn::class, $column);
        self::assertSame('id', $column->name);
        self::assertTrue($column->primaryKey);
    }

    public function testToColumnReversesVarcharLength(): void
    {
        $this->buildWidgetsTable();

        $byName = $this->connection->describeTable(
            table: 'widgets',
        )->byName();

        $column = $byName['email']->toColumn();

        self::assertInstanceOf(VarcharColumn::class, $column);
        self::assertSame('email', $column->name);
        self::assertSame(190, $column->length);
        self::assertFalse($column->nullable);
    }

    public function testToColumnReversesNullableVarchar(): void
    {
        $this->buildWidgetsTable();

        $byName = $this->connection->describeTable(
            table: 'widgets',
        )->byName();

        $column = $byName['status']->toColumn();

        self::assertInstanceOf(VarcharColumn::class, $column);
        self::assertSame(20, $column->length);
        self::assertTrue($column->nullable);
    }

    public function testToColumnReversesDecimalPrecisionScale(): void
    {
        $table = $this->connection->createTable(
            table: 'reverse_decimals',
        );
        $table->integer(name: 'id', primaryKey: true, autoIncrement: true);
        $table->decimal(name: 'amount', precision: 10, scale: 2);
        $table->execute();

        $byName = $this->connection->describeTable(
            table: 'reverse_decimals',
        )->byName();

        $column = $byName['amount']->toColumn();

        self::assertInstanceOf(DecimalColumn::class, $column);
        self::assertSame(10, $column->precision);
        self::assertSame(2, $column->scale);
    }

    public function testToColumnReversesCharLength(): void
    {
        $table = $this->connection->createTable(
            table: 'reverse_chars',
        );
        $table->integer(name: 'id', primaryKey: true, autoIncrement: true);
        $table->char(name: 'code', length: 8);
        $table->execute();

        $byName = $this->connection->describeTable(
            table: 'reverse_chars',
        )->byName();

        $column = $byName['code']->toColumn();

        self::assertInstanceOf(CharColumn::class, $column);
        self::assertSame(8, $column->length);
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
