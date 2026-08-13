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

use Tuxxedo\Database\Query\Statement\Table\ForeignKeyAction;
use Tuxxedo\Database\Query\Statement\Table\ForeignKeyMetadataInterface;

abstract class AbstractListForeignKeysBuilderIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    protected function buildOwnersAndOrders(): void
    {
        $owners = $this->connection->createTable(
            table: 'owners',
        );

        $owners->integer(
            name: 'id',
            primaryKey: true,
        );

        $owners->execute();

        $orders = $this->connection->createTable(
            table: 'orders',
        );

        $orders->integer(
            name: 'id',
            primaryKey: true,
        );

        $orders->integer(
            name: 'owner_id',
        );

        $orders->foreignKey(
            columns: [
                'owner_id',
            ],
            referencedTable: 'owners',
            referencedColumns: [
                'id',
            ],
            onDelete: ForeignKeyAction::CASCADE,
            onUpdate: ForeignKeyAction::RESTRICT,
        );

        $orders->execute();
    }

    public function testListForeignKeysReportsSingleColumnFk(): void
    {
        $this->buildOwnersAndOrders();

        $foreignKeys = $this->connection->listForeignKeys(
            table: 'orders',
        )->all();

        self::assertNotEmpty($foreignKeys);

        $fk = $this->findByColumns(
            foreignKeys: $foreignKeys,
            columns: [
                'owner_id',
            ],
        );

        self::assertNotNull($fk, 'FK on owner_id should exist');
        self::assertSame('owners', $fk->referencedTable);
        self::assertSame(
            [
                'id',
            ],
            $fk->referencedColumns,
        );
        self::assertSame(ForeignKeyAction::CASCADE, $fk->onDelete);
        self::assertSame(ForeignKeyAction::RESTRICT, $fk->onUpdate);
    }

    public function testByNameReturnsMapKeyedByConstraintName(): void
    {
        $this->buildOwnersAndOrders();

        $byName = $this->connection->listForeignKeys(
            table: 'orders',
        )->byName();

        self::assertNotEmpty($byName);

        foreach ($byName as $name => $fk) {
            self::assertSame($name, $fk->name);
        }
    }

    public function testListForeignKeysReturnsEmptyForTableWithoutFks(): void
    {
        $table = $this->connection->createTable(
            table: 'lonely',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->execute();

        $foreignKeys = $this->connection->listForeignKeys(
            table: 'lonely',
        )->all();

        self::assertSame([], $foreignKeys);
    }

    /**
     * @param list<ForeignKeyMetadataInterface> $foreignKeys
     * @param list<string> $columns
     */
    private function findByColumns(
        array $foreignKeys,
        array $columns,
    ): ?ForeignKeyMetadataInterface {
        foreach ($foreignKeys as $fk) {
            if ($fk->columns === $columns) {
                return $fk;
            }
        }

        return null;
    }
}
