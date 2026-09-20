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

namespace Unit\Database\Query\Statement\Table\Column;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Dialect\PgsqlDialect;
use Tuxxedo\Database\Query\Dialect\SqliteDialect;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\UuidColumn;

class UuidColumnTest extends TestCase
{
    public function testTypeStringUsesPgsqlNativeType(): void
    {
        $column = new UuidColumn(name: 'id');

        self::assertSame('UUID', $column->typeString(new PgsqlDialect()));
    }

    public function testTypeStringUsesSqliteNativeType(): void
    {
        $column = new UuidColumn(name: 'id');

        self::assertSame('TEXT', $column->typeString(new SqliteDialect()));
    }

    public function testTypeStringFallsBackToChar36WhenDialectHasNoNativeType(): void
    {
        $column = new UuidColumn(name: 'id');
        $dialect = $this->makeNullNativeTypeDialect();

        self::assertSame('CHAR(36)', $column->typeString($dialect));
    }

    private function makeNullNativeTypeDialect(): DialectInterface
    {
        return new class () implements DialectInterface {
            public array $quotations = [
                '\'',
            ];

            public function placeholder(
                int $position,
            ): string {
                return '?';
            }

            public function identifier(
                string $name,
            ): string {
                return $name;
            }

            public function qualifiedIdentifier(
                string $name,
            ): string {
                return $name;
            }

            public function nativeColumnType(
                ColumnInterface $column,
            ): ?string {
                return null;
            }

            public function autoIncrementClause(): string
            {
                return '';
            }

            public function interpretBoolean(
                mixed $value,
            ): bool {
                return (bool) $value;
            }

            public function alterTable(
                string $table,
                array $operations,
            ): array {
                return [];
            }

            public function tableExists(
                string $table,
            ): StatementParserResultInterface {
                throw new \LogicException('not used');
            }

            public function columnExists(
                string $table,
                string $column,
            ): StatementParserResultInterface {
                throw new \LogicException('not used');
            }

            public function listDatabases(): StatementParserResultInterface
            {
                throw new \LogicException('not used');
            }

            public function listTables(): StatementParserResultInterface
            {
                throw new \LogicException('not used');
            }

            public function listIndexes(
                string $table,
            ): StatementParserResultInterface {
                throw new \LogicException('not used');
            }

            public function listForeignKeys(
                string $table,
            ): StatementParserResultInterface {
                throw new \LogicException('not used');
            }

            public function describeTable(
                string $table,
            ): StatementParserResultInterface {
                throw new \LogicException('not used');
            }
        };
    }
}
