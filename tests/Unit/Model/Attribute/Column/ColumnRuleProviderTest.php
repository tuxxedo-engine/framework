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

namespace Unit\Model\Attribute\Column;

use Fixture\Validator\FixtureStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Database\Query\Statement\Table\Column\CharColumn;
use Tuxxedo\Database\Query\Statement\Table\CreateTableStatement;
use Tuxxedo\Model\Attribute\Column\BigInteger;
use Tuxxedo\Model\Attribute\Column\Boolean;
use Tuxxedo\Model\Attribute\Column\Char;
use Tuxxedo\Model\Attribute\Column\Date;
use Tuxxedo\Model\Attribute\Column\DateFormat;
use Tuxxedo\Model\Attribute\Column\DateTime;
use Tuxxedo\Model\Attribute\Column\Decimal;
use Tuxxedo\Model\Attribute\Column\Double;
use Tuxxedo\Model\Attribute\Column\Enumeration;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Json;
use Tuxxedo\Model\Attribute\Column\SmallInteger;
use Tuxxedo\Model\Attribute\Column\Text;
use Tuxxedo\Model\Attribute\Column\Time;
use Tuxxedo\Model\Attribute\Column\TimeFormat;
use Tuxxedo\Model\Attribute\Column\Timestamp;
use Tuxxedo\Model\Attribute\Column\TinyInteger;
use Tuxxedo\Model\Attribute\Column\Uuid;
use Tuxxedo\Model\Attribute\Column\UuidVersion;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Validator\Rule\Boolean\BooleanRule;
use Tuxxedo\Validator\Rule\DateTime\DateTimeRule;
use Tuxxedo\Validator\Rule\Enum\EnumRule;
use Tuxxedo\Validator\Rule\Integer\IntegerRule;
use Tuxxedo\Validator\Rule\Json\JsonRule;
use Tuxxedo\Validator\Rule\Length\LengthRule;
use Tuxxedo\Validator\Rule\Numeric\NumericRule;
use Tuxxedo\Validator\Rule\Uuid\UuidRule;
use Tuxxedo\Validator\Rule\UuidV4\UuidV4Rule;
use Tuxxedo\Validator\Rule\UuidV7\UuidV7Rule;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\RuleProviderInterface;

class ColumnRuleProviderTest extends TestCase
{
    /**
     * @return \Generator<array{0: RuleProviderInterface, 1: class-string<RuleInterface>}>
     */
    public static function providesColumnRuleShape(): \Generator
    {
        yield 'Integer emits IntegerRule' => [
            new Integer(),
            IntegerRule::class,
        ];

        yield 'BigInteger emits IntegerRule' => [
            new BigInteger(),
            IntegerRule::class,
        ];

        yield 'SmallInteger emits IntegerRule' => [
            new SmallInteger(),
            IntegerRule::class,
        ];

        yield 'TinyInteger emits IntegerRule' => [
            new TinyInteger(),
            IntegerRule::class,
        ];

        yield 'Decimal emits NumericRule' => [
            new Decimal(
                precision: 10,
                scale: 2,
            ),
            NumericRule::class,
        ];

        yield 'Double emits NumericRule' => [
            new Double(),
            NumericRule::class,
        ];

        yield 'Char emits LengthRule' => [
            new Char(
                length: 3,
            ),
            LengthRule::class,
        ];

        yield 'Varchar emits LengthRule' => [
            new Varchar(
                length: 255,
            ),
            LengthRule::class,
        ];

        yield 'Text emits LengthRule' => [
            new Text(),
            LengthRule::class,
        ];

        yield 'Boolean emits BooleanRule' => [
            new Boolean(),
            BooleanRule::class,
        ];

        yield 'Date emits DateTimeRule' => [
            new Date(),
            DateTimeRule::class,
        ];

        yield 'DateTime emits DateTimeRule' => [
            new DateTime(),
            DateTimeRule::class,
        ];

        yield 'Time emits DateTimeRule' => [
            new Time(),
            DateTimeRule::class,
        ];

        yield 'Timestamp emits DateTimeRule' => [
            new Timestamp(),
            DateTimeRule::class,
        ];

        yield 'Json emits JsonRule' => [
            new Json(),
            JsonRule::class,
        ];

        yield 'Enumeration emits EnumRule' => [
            new Enumeration(
                enum: FixtureStatus::class,
            ),
            EnumRule::class,
        ];

        yield 'Uuid default emits UuidRule' => [
            new Uuid(),
            UuidRule::class,
        ];

        yield 'Uuid Any emits UuidRule' => [
            new Uuid(
                version: UuidVersion::ANY,
            ),
            UuidRule::class,
        ];

        yield 'Uuid V4 emits UuidV4Rule' => [
            new Uuid(
                version: UuidVersion::V4,
            ),
            UuidV4Rule::class,
        ];

        yield 'Uuid V7 emits UuidV7Rule' => [
            new Uuid(
                version: UuidVersion::V7,
            ),
            UuidV7Rule::class,
        ];
    }

    /**
     * @param class-string<RuleInterface> $expected
     */
    #[DataProvider('providesColumnRuleShape')]
    public function testColumnEmitsExpectedRule(
        RuleProviderInterface $column,
        string $expected,
    ): void {
        $rules = \iterator_to_array($column->toRules(), false);

        self::assertSame(1, \sizeof($rules));
        self::assertInstanceOf($expected, $rules[0]);
    }

    public function testVarcharPropagatesLengthIntoLengthRule(): void
    {
        $rules = \iterator_to_array(
            (new Varchar(
                length: 42,
            ))->toRules(),
            false,
        );

        self::assertCount(1, $rules);
        self::assertInstanceOf(LengthRule::class, $rules[0]);
        self::assertSame(42, $rules[0]->max);
    }

    public function testCharPropagatesLengthIntoLengthRule(): void
    {
        $rules = \iterator_to_array(
            (new Char(
                length: 5,
            ))->toRules(),
            false,
        );

        self::assertCount(1, $rules);
        self::assertInstanceOf(LengthRule::class, $rules[0]);
        self::assertSame(5, $rules[0]->max);
    }

    public function testDateResolvesDateFormatEnumToStringFormat(): void
    {
        $rules = \iterator_to_array(
            (new Date(
                format: DateFormat::EUROPEAN,
            ))->toRules(),
            false,
        );

        self::assertCount(1, $rules);
        self::assertInstanceOf(DateTimeRule::class, $rules[0]);
        self::assertSame('d/m/Y', $rules[0]->format);
    }

    public function testDatePassesRawStringFormatThroughToDateTimeRule(): void
    {
        $rules = \iterator_to_array(
            (new Date(
                format: 'Y-m',
            ))->toRules(),
            false,
        );

        self::assertCount(1, $rules);
        self::assertInstanceOf(DateTimeRule::class, $rules[0]);
        self::assertSame('Y-m', $rules[0]->format);
    }

    public function testDateTimeResolvesDateFormatEnumToStringFormat(): void
    {
        $rules = \iterator_to_array(
            (new DateTime(
                format: DateFormat::US,
            ))->toRules(),
            false,
        );

        self::assertCount(1, $rules);
        self::assertInstanceOf(DateTimeRule::class, $rules[0]);
        self::assertSame('m/d/Y', $rules[0]->format);
    }

    public function testDateTimePassesRawStringFormatThroughToDateTimeRule(): void
    {
        $rules = \iterator_to_array(
            (new DateTime(
                format: 'Y-m-d H:i',
            ))->toRules(),
            false,
        );

        self::assertCount(1, $rules);
        self::assertInstanceOf(DateTimeRule::class, $rules[0]);
        self::assertSame('Y-m-d H:i', $rules[0]->format);
    }

    public function testTimeResolvesTimeFormatEnumToStringFormat(): void
    {
        $rules = \iterator_to_array(
            (new Time(
                format: TimeFormat::DEFAULT,
            ))->toRules(),
            false,
        );

        self::assertCount(1, $rules);
        self::assertInstanceOf(DateTimeRule::class, $rules[0]);
        self::assertSame(TimeFormat::DEFAULT->value, $rules[0]->format);
    }

    public function testTimePassesRawStringFormatThroughToDateTimeRule(): void
    {
        $rules = \iterator_to_array(
            (new Time(
                format: 'H:i',
            ))->toRules(),
            false,
        );

        self::assertCount(1, $rules);
        self::assertInstanceOf(DateTimeRule::class, $rules[0]);
        self::assertSame('H:i', $rules[0]->format);
    }

    public function testTimestampResolvesDateFormatEnumToStringFormat(): void
    {
        $rules = \iterator_to_array(
            (new Timestamp(
                format: DateFormat::UNIX,
            ))->toRules(),
            false,
        );

        self::assertCount(1, $rules);
        self::assertInstanceOf(DateTimeRule::class, $rules[0]);
        self::assertSame('U', $rules[0]->format);
    }

    public function testTimestampPassesRawStringFormatThroughToDateTimeRule(): void
    {
        $rules = \iterator_to_array(
            (new Timestamp(
                format: 'Y-m-d\TH:i:sP',
            ))->toRules(),
            false,
        );

        self::assertCount(1, $rules);
        self::assertInstanceOf(DateTimeRule::class, $rules[0]);
        self::assertSame('Y-m-d\TH:i:sP', $rules[0]->format);
    }

    public function testEnumerationPropagatesEnumClassIntoEnumRule(): void
    {
        $rules = \iterator_to_array(
            (new Enumeration(
                enum: FixtureStatus::class,
            ))->toRules(),
            false,
        );

        self::assertCount(1, $rules);
        self::assertInstanceOf(EnumRule::class, $rules[0]);
        self::assertSame(FixtureStatus::class, $rules[0]->enum);
    }

    public function testIntegerEmitsStrictIntegerRule(): void
    {
        $rules = \iterator_to_array(
            (new Integer())->toRules(),
            false,
        );

        self::assertCount(1, $rules);
        self::assertInstanceOf(IntegerRule::class, $rules[0]);
        self::assertTrue($rules[0]->strict);
    }

    public function testDecimalEmitsLenientNumericRule(): void
    {
        $rules = \iterator_to_array(
            (new Decimal(
                precision: 10,
                scale: 2,
            ))->toRules(),
            false,
        );

        self::assertCount(1, $rules);
        self::assertInstanceOf(NumericRule::class, $rules[0]);
        self::assertFalse($rules[0]->strict);
    }

    public function testBooleanEmitsStrictBooleanRule(): void
    {
        $rules = \iterator_to_array(
            (new Boolean())->toRules(),
            false,
        );

        self::assertCount(1, $rules);
        self::assertInstanceOf(BooleanRule::class, $rules[0]);
        self::assertTrue($rules[0]->strict);
    }

    public function testUuidExposesFixedLengthOfThirtySix(): void
    {
        $column = new Uuid();

        self::assertSame(36, $column->length);
    }

    public function testUuidDefaultsToAnyVersion(): void
    {
        $column = new Uuid();

        self::assertSame(UuidVersion::ANY, $column->version);
    }

    public function testUuidHasNullCoercerByDefault(): void
    {
        $column = new Uuid();

        self::assertNull($column->coercer);
    }

    public function testUuidToColumnTypeProducesCharColumnOfLength36(): void
    {
        $statement = new CreateTableStatement(
            table: 'entities',
        );

        $column = (new Uuid())->toColumnType(
            statement: $statement,
            propertyName: 'id',
        );

        self::assertInstanceOf(CharColumn::class, $column);
        self::assertSame('id', $column->name);
        self::assertSame(36, $column->length);
        self::assertFalse($column->nullable);
        self::assertFalse($column->primaryKey);
        self::assertFalse($column->unique);
        self::assertNull($column->default);
    }

    public function testUuidToColumnTypeUsesExplicitNameOverride(): void
    {
        $statement = new CreateTableStatement(
            table: 'entities',
        );

        $column = (new Uuid(
            name: 'entity_uuid',
            nullable: true,
            primaryKey: true,
            unique: true,
            default: '00000000-0000-0000-0000-000000000000',
        ))->toColumnType(
            statement: $statement,
            propertyName: 'id',
        );

        self::assertInstanceOf(CharColumn::class, $column);
        self::assertSame('entity_uuid', $column->name);
        self::assertSame(36, $column->length);
        self::assertTrue($column->nullable);
        self::assertTrue($column->primaryKey);
        self::assertTrue($column->unique);
        self::assertSame('00000000-0000-0000-0000-000000000000', $column->default);
    }
}
