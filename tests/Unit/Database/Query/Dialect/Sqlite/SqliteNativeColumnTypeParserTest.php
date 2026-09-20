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

namespace Unit\Database\Query\Dialect\Sqlite;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Database\StubDialect;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Query\Dialect\Sqlite\SqliteNativeColumnTypeParser;
use Tuxxedo\Database\Query\Statement\Table\Column\BigIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\BlobColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\CharColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DateColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DateTimeColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DecimalColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DoubleColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\IntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\SmallIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TextColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TimeColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TimestampColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TinyIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\VarcharColumn;
use Tuxxedo\Database\Query\Statement\Table\ColumnDescription;

class SqliteNativeColumnTypeParserTest extends TestCase
{
    private SqliteNativeColumnTypeParser $parser;

    private StubDialect $dialect;

    protected function setUp(): void
    {
        $this->parser = new SqliteNativeColumnTypeParser();
        $this->dialect = new StubDialect();
    }

    /**
     * @return \Generator<string, array{0: string, 1: class-string}>
     */
    public static function providesTypeMappings(): \Generator
    {
        yield 'INTEGER as IntegerColumn' => [
            'INTEGER',
            IntegerColumn::class,
        ];

        yield 'integer lowercase as IntegerColumn' => [
            'integer',
            IntegerColumn::class,
        ];

        yield 'int as IntegerColumn' => [
            'int',
            IntegerColumn::class,
        ];

        yield 'TINYINT as TinyIntegerColumn' => [
            'TINYINT',
            TinyIntegerColumn::class,
        ];

        yield 'SMALLINT as SmallIntegerColumn' => [
            'SMALLINT',
            SmallIntegerColumn::class,
        ];

        yield 'BIGINT as BigIntegerColumn' => [
            'BIGINT',
            BigIntegerColumn::class,
        ];

        yield 'REAL as DoubleColumn' => [
            'REAL',
            DoubleColumn::class,
        ];

        yield 'FLOAT as DoubleColumn' => [
            'FLOAT',
            DoubleColumn::class,
        ];

        yield 'DOUBLE as DoubleColumn' => [
            'DOUBLE',
            DoubleColumn::class,
        ];

        yield 'TEXT as TextColumn' => [
            'TEXT',
            TextColumn::class,
        ];

        yield 'BLOB as BlobColumn' => [
            'BLOB',
            BlobColumn::class,
        ];

        yield 'DATETIME as DateTimeColumn' => [
            'DATETIME',
            DateTimeColumn::class,
        ];

        yield 'TIMESTAMP as TimestampColumn' => [
            'TIMESTAMP',
            TimestampColumn::class,
        ];

        yield 'DATE as DateColumn' => [
            'DATE',
            DateColumn::class,
        ];

        yield 'TIME as TimeColumn' => [
            'TIME',
            TimeColumn::class,
        ];
    }

    /**
     * @param class-string $expectedClass
     */
    #[DataProvider('providesTypeMappings')]
    public function testMapsNativeTypeToExpectedColumnClass(
        string $nativeType,
        string $expectedClass,
    ): void {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: $nativeType),
        );

        self::assertInstanceOf($expectedClass, $column);
    }

    public function testVarcharParsesLength(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'VARCHAR(255)'),
        );

        self::assertInstanceOf(VarcharColumn::class, $column);
        self::assertSame(255, $column->length);
    }

    public function testCharParsesLength(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'CHAR(26)'),
        );

        self::assertInstanceOf(CharColumn::class, $column);
        self::assertSame(26, $column->length);
    }

    public function testDecimalParsesPrecisionAndScale(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'DECIMAL(10,2)'),
        );

        self::assertInstanceOf(DecimalColumn::class, $column);
        self::assertSame(10, $column->precision);
        self::assertSame(2, $column->scale);
    }

    public function testNumericAliasesDecimal(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'NUMERIC(5,3)'),
        );

        self::assertInstanceOf(DecimalColumn::class, $column);
        self::assertSame(5, $column->precision);
        self::assertSame(3, $column->scale);
    }

    public function testPropagatesNameAndFlags(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(
                nativeType: 'INTEGER',
                name: 'user_id',
                nullable: false,
                primary: true,
                autoIncrement: true,
            ),
        );

        self::assertInstanceOf(IntegerColumn::class, $column);
        self::assertSame('user_id', $column->name);
        self::assertFalse($column->nullable);
        self::assertTrue($column->primaryKey);
        self::assertTrue($column->autoIncrement);
    }

    public function testCoercesIntegerDefault(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'INTEGER', default: '10'),
        );

        self::assertInstanceOf(IntegerColumn::class, $column);
        self::assertSame(10, $column->default);
    }

    public function testUnknownTypeThrows(): void
    {
        try {
            $this->parser->parse(
                description: $this->makeDescription(nativeType: 'GEOMETRY'),
            );

            self::fail('Expected DatabaseException was not thrown');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString(
                'GEOMETRY',
                $exception->getMessage(),
            );

            self::assertStringContainsString(
                'sqlite',
                $exception->getMessage(),
            );
        }
    }

    public function testMalformedDecimalThrows(): void
    {
        $this->expectException(DatabaseException::class);

        $this->parser->parse(
            description: $this->makeDescription(nativeType: 'DECIMAL(10)'),
        );
    }

    public function testMalformedVarcharThrows(): void
    {
        $this->expectException(DatabaseException::class);

        $this->parser->parse(
            description: $this->makeDescription(nativeType: 'VARCHAR'),
        );
    }

    public function testMalformedCharThrows(): void
    {
        $this->expectException(DatabaseException::class);

        $this->parser->parse(
            description: $this->makeDescription(nativeType: 'CHAR'),
        );
    }

    public function testMissingClosingParenTreatedAsMissingArguments(): void
    {
        $this->expectException(DatabaseException::class);

        $this->parser->parse(
            description: $this->makeDescription(nativeType: 'VARCHAR(20'),
        );
    }

    private function makeDescription(
        string $nativeType,
        string $name = 'test_column',
        bool $nullable = true,
        ?string $default = null,
        bool $primary = false,
        bool $autoIncrement = false,
    ): ColumnDescription {
        return new ColumnDescription(
            name: $name,
            nativeType: $nativeType,
            dialect: $this->dialect,
            nullable: $nullable,
            default: $default,
            primary: $primary,
            autoIncrement: $autoIncrement,
        );
    }
}
