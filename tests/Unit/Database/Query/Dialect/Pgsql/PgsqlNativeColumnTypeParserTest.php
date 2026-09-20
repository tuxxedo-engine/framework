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

namespace Unit\Database\Query\Dialect\Pgsql;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Database\StubDialect;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Query\Dialect\Pgsql\PgsqlNativeColumnTypeParser;
use Tuxxedo\Database\Query\Statement\Table\Column\BigIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\BlobColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\BooleanColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\CharColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DateColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DecimalColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DoubleColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\IntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\JsonColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\SmallIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TextColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TimeColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TimestampColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\UuidColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\VarcharColumn;
use Tuxxedo\Database\Query\Statement\Table\ColumnDescription;

class PgsqlNativeColumnTypeParserTest extends TestCase
{
    private PgsqlNativeColumnTypeParser $parser;

    private StubDialect $dialect;

    protected function setUp(): void
    {
        $this->parser = new PgsqlNativeColumnTypeParser();
        $this->dialect = new StubDialect();
    }

    /**
     * @return \Generator<string, array{0: string, 1: class-string}>
     */
    public static function providesTypeMappings(): \Generator
    {
        yield 'smallint as SmallIntegerColumn' => [
            'smallint',
            SmallIntegerColumn::class,
        ];

        yield 'int2 as SmallIntegerColumn' => [
            'int2',
            SmallIntegerColumn::class,
        ];

        yield 'integer as IntegerColumn' => [
            'integer',
            IntegerColumn::class,
        ];

        yield 'int as IntegerColumn' => [
            'int',
            IntegerColumn::class,
        ];

        yield 'int4 as IntegerColumn' => [
            'int4',
            IntegerColumn::class,
        ];

        yield 'bigint as BigIntegerColumn' => [
            'bigint',
            BigIntegerColumn::class,
        ];

        yield 'int8 as BigIntegerColumn' => [
            'int8',
            BigIntegerColumn::class,
        ];

        yield 'boolean as BooleanColumn' => [
            'boolean',
            BooleanColumn::class,
        ];

        yield 'bool as BooleanColumn' => [
            'bool',
            BooleanColumn::class,
        ];

        yield 'real as DoubleColumn' => [
            'real',
            DoubleColumn::class,
        ];

        yield 'double precision as DoubleColumn' => [
            'double precision',
            DoubleColumn::class,
        ];

        yield 'float4 as DoubleColumn' => [
            'float4',
            DoubleColumn::class,
        ];

        yield 'float8 as DoubleColumn' => [
            'float8',
            DoubleColumn::class,
        ];

        yield 'text as TextColumn' => [
            'text',
            TextColumn::class,
        ];

        yield 'bytea as BlobColumn' => [
            'bytea',
            BlobColumn::class,
        ];

        yield 'json as JsonColumn' => [
            'json',
            JsonColumn::class,
        ];

        yield 'jsonb as JsonColumn' => [
            'jsonb',
            JsonColumn::class,
        ];

        yield 'uuid as UuidColumn' => [
            'uuid',
            UuidColumn::class,
        ];

        yield 'timestamp as TimestampColumn' => [
            'timestamp',
            TimestampColumn::class,
        ];

        yield 'timestamp without time zone as TimestampColumn' => [
            'timestamp without time zone',
            TimestampColumn::class,
        ];

        yield 'timestamp with time zone as TimestampColumn' => [
            'timestamp with time zone',
            TimestampColumn::class,
        ];

        yield 'timestamptz as TimestampColumn' => [
            'timestamptz',
            TimestampColumn::class,
        ];

        yield 'date as DateColumn' => [
            'date',
            DateColumn::class,
        ];

        yield 'time as TimeColumn' => [
            'time',
            TimeColumn::class,
        ];

        yield 'time without time zone as TimeColumn' => [
            'time without time zone',
            TimeColumn::class,
        ];

        yield 'time with time zone as TimeColumn' => [
            'time with time zone',
            TimeColumn::class,
        ];

        yield 'timetz as TimeColumn' => [
            'timetz',
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

    public function testCharacterVaryingParsesLength(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'character varying(255)'),
        );

        self::assertInstanceOf(VarcharColumn::class, $column);
        self::assertSame(255, $column->length);
    }

    public function testVarcharAlias(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'varchar(64)'),
        );

        self::assertInstanceOf(VarcharColumn::class, $column);
        self::assertSame(64, $column->length);
    }

    public function testCharacterParsesLength(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'character(36)'),
        );

        self::assertInstanceOf(CharColumn::class, $column);
        self::assertSame(36, $column->length);
    }

    public function testBpcharAlias(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'bpchar(10)'),
        );

        self::assertInstanceOf(CharColumn::class, $column);
        self::assertSame(10, $column->length);
    }

    public function testNumericParsesPrecisionAndScale(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'numeric(10,2)'),
        );

        self::assertInstanceOf(DecimalColumn::class, $column);
        self::assertSame(10, $column->precision);
        self::assertSame(2, $column->scale);
    }

    public function testDecimalAliasesNumeric(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'decimal(5,3)'),
        );

        self::assertInstanceOf(DecimalColumn::class, $column);
    }

    public function testCoercesBooleanDefaultTrue(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'boolean', default: 'true'),
        );

        self::assertInstanceOf(BooleanColumn::class, $column);
        self::assertTrue($column->default);
    }

    public function testCoercesBooleanDefaultFalse(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'boolean', default: 'false'),
        );

        self::assertInstanceOf(BooleanColumn::class, $column);
        self::assertFalse($column->default);
    }

    public function testPropagatesNameAndFlags(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(
                nativeType: 'integer',
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

    public function testUnknownTypeThrows(): void
    {
        try {
            $this->parser->parse(
                description: $this->makeDescription(nativeType: 'inet'),
            );

            self::fail('Expected DatabaseException was not thrown');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString(
                'inet',
                $exception->getMessage(),
            );

            self::assertStringContainsString(
                'pgsql',
                $exception->getMessage(),
            );
        }
    }

    public function testMalformedNumericThrows(): void
    {
        $this->expectException(DatabaseException::class);

        $this->parser->parse(
            description: $this->makeDescription(nativeType: 'numeric(10)'),
        );
    }

    public function testMalformedVarcharThrows(): void
    {
        $this->expectException(DatabaseException::class);

        $this->parser->parse(
            description: $this->makeDescription(nativeType: 'character varying'),
        );
    }

    public function testMalformedCharThrows(): void
    {
        $this->expectException(DatabaseException::class);

        $this->parser->parse(
            description: $this->makeDescription(nativeType: 'character'),
        );
    }

    public function testMissingClosingParenTreatedAsMissingArguments(): void
    {
        $this->expectException(DatabaseException::class);

        $this->parser->parse(
            description: $this->makeDescription(nativeType: 'character varying(20'),
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
