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

namespace Unit\Database\Query\Dialect\Mysql;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Database\StubDialect;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Query\Dialect\Mysql\MysqlNativeColumnTypeParser;
use Tuxxedo\Database\Query\Statement\Table\Column\BigIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\BlobColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\BooleanColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\CharColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DateColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DateTimeColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DecimalColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DoubleColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\EnumerationColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\IntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\JsonColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\SmallIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TextColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TimeColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TimestampColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TinyIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\VarcharColumn;
use Tuxxedo\Database\Query\Statement\Table\ColumnDescription;

class MysqlNativeColumnTypeParserTest extends TestCase
{
    private MysqlNativeColumnTypeParser $parser;

    private StubDialect $dialect;

    protected function setUp(): void
    {
        $this->parser = new MysqlNativeColumnTypeParser();
        $this->dialect = new StubDialect();
    }

    /**
     * @return \Generator<string, array{0: string, 1: class-string}>
     */
    public static function providesTypeMappings(): \Generator
    {
        yield 'tinyint(1) as BooleanColumn' => [
            'tinyint(1)',
            BooleanColumn::class,
        ];

        yield 'tinyint(4) as TinyIntegerColumn' => [
            'tinyint(4)',
            TinyIntegerColumn::class,
        ];

        yield 'tinyint as TinyIntegerColumn' => [
            'tinyint',
            TinyIntegerColumn::class,
        ];

        yield 'smallint as SmallIntegerColumn' => [
            'smallint',
            SmallIntegerColumn::class,
        ];

        yield 'smallint(6) as SmallIntegerColumn' => [
            'smallint(6)',
            SmallIntegerColumn::class,
        ];

        yield 'mediumint as IntegerColumn' => [
            'mediumint',
            IntegerColumn::class,
        ];

        yield 'int as IntegerColumn' => [
            'int',
            IntegerColumn::class,
        ];

        yield 'int(11) as IntegerColumn' => [
            'int(11)',
            IntegerColumn::class,
        ];

        yield 'int unsigned as IntegerColumn' => [
            'int unsigned',
            IntegerColumn::class,
        ];

        yield 'int(11) unsigned as IntegerColumn' => [
            'int(11) unsigned',
            IntegerColumn::class,
        ];

        yield 'integer as IntegerColumn' => [
            'integer',
            IntegerColumn::class,
        ];

        yield 'bigint as BigIntegerColumn' => [
            'bigint',
            BigIntegerColumn::class,
        ];

        yield 'bigint(20) as BigIntegerColumn' => [
            'bigint(20)',
            BigIntegerColumn::class,
        ];

        yield 'float as DoubleColumn' => [
            'float',
            DoubleColumn::class,
        ];

        yield 'double as DoubleColumn' => [
            'double',
            DoubleColumn::class,
        ];

        yield 'real as DoubleColumn' => [
            'real',
            DoubleColumn::class,
        ];

        yield 'text as TextColumn' => [
            'text',
            TextColumn::class,
        ];

        yield 'tinytext as TextColumn' => [
            'tinytext',
            TextColumn::class,
        ];

        yield 'mediumtext as TextColumn' => [
            'mediumtext',
            TextColumn::class,
        ];

        yield 'longtext as TextColumn' => [
            'longtext',
            TextColumn::class,
        ];

        yield 'blob as BlobColumn' => [
            'blob',
            BlobColumn::class,
        ];

        yield 'tinyblob as BlobColumn' => [
            'tinyblob',
            BlobColumn::class,
        ];

        yield 'mediumblob as BlobColumn' => [
            'mediumblob',
            BlobColumn::class,
        ];

        yield 'longblob as BlobColumn' => [
            'longblob',
            BlobColumn::class,
        ];

        yield 'json as JsonColumn' => [
            'json',
            JsonColumn::class,
        ];

        yield 'datetime as DateTimeColumn' => [
            'datetime',
            DateTimeColumn::class,
        ];

        yield 'timestamp as TimestampColumn' => [
            'timestamp',
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
            description: $this->makeDescription(nativeType: 'varchar(190)'),
        );

        self::assertInstanceOf(VarcharColumn::class, $column);
        self::assertSame(190, $column->length);
    }

    public function testCharParsesLength(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'char(36)'),
        );

        self::assertInstanceOf(CharColumn::class, $column);
        self::assertSame(36, $column->length);
    }

    public function testDecimalParsesPrecisionAndScale(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'decimal(10,2)'),
        );

        self::assertInstanceOf(DecimalColumn::class, $column);
        self::assertSame(10, $column->precision);
        self::assertSame(2, $column->scale);
    }

    public function testNumericAliasesDecimal(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'numeric(5,3)'),
        );

        self::assertInstanceOf(DecimalColumn::class, $column);
        self::assertSame(5, $column->precision);
        self::assertSame(3, $column->scale);
    }

    public function testEnumParsesValues(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: "enum('active','pending','archived')"),
        );

        self::assertInstanceOf(EnumerationColumn::class, $column);
        self::assertSame(
            [
                'active',
                'pending',
                'archived',
            ],
            $column->values,
        );
    }

    public function testEnumUnescapesEmbeddedQuotes(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: "enum('don''t','ok')"),
        );

        self::assertInstanceOf(EnumerationColumn::class, $column);
        self::assertSame(
            [
                "don't",
                'ok',
            ],
            $column->values,
        );
    }

    public function testPropagatesNameAndFlags(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(
                nativeType: 'int(11)',
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
            description: $this->makeDescription(nativeType: 'int', default: '42'),
        );

        self::assertInstanceOf(IntegerColumn::class, $column);
        self::assertSame(42, $column->default);
    }

    public function testCoercesBooleanDefault(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'tinyint(1)', default: '1'),
        );

        self::assertInstanceOf(BooleanColumn::class, $column);
        self::assertTrue($column->default);
    }

    public function testPassesThroughVarcharDefault(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'varchar(20)', default: 'active'),
        );

        self::assertInstanceOf(VarcharColumn::class, $column);
        self::assertSame('active', $column->default);
    }

    public function testNullDefaultStaysNull(): void
    {
        $column = $this->parser->parse(
            description: $this->makeDescription(nativeType: 'int'),
        );

        self::assertInstanceOf(IntegerColumn::class, $column);
        self::assertNull($column->default);
    }

    public function testUnknownTypeThrows(): void
    {
        try {
            $this->parser->parse(
                description: $this->makeDescription(nativeType: 'geometry'),
            );

            self::fail('Expected DatabaseException was not thrown');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString(
                'geometry',
                $exception->getMessage(),
            );

            self::assertStringContainsString(
                'mysql',
                $exception->getMessage(),
            );
        }
    }

    public function testMalformedDecimalThrows(): void
    {
        $this->expectException(DatabaseException::class);

        $this->parser->parse(
            description: $this->makeDescription(nativeType: 'decimal(10)'),
        );
    }

    public function testMalformedVarcharThrows(): void
    {
        $this->expectException(DatabaseException::class);

        $this->parser->parse(
            description: $this->makeDescription(nativeType: 'varchar'),
        );
    }

    public function testMalformedEnumThrows(): void
    {
        $this->expectException(DatabaseException::class);

        $this->parser->parse(
            description: $this->makeDescription(nativeType: 'enum()'),
        );
    }

    public function testEnumWithoutQuotedValuesThrows(): void
    {
        $this->expectException(DatabaseException::class);

        $this->parser->parse(
            description: $this->makeDescription(nativeType: 'enum(a,b,c)'),
        );
    }

    public function testMalformedCharThrows(): void
    {
        $this->expectException(DatabaseException::class);

        $this->parser->parse(
            description: $this->makeDescription(nativeType: 'char'),
        );
    }

    public function testMissingClosingParenTreatedAsMissingArguments(): void
    {
        $this->expectException(DatabaseException::class);

        $this->parser->parse(
            description: $this->makeDescription(nativeType: 'varchar(20'),
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
