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

namespace Unit\Serializer\Converter;

use Fixture\Serializer\Customer;
use Fixture\Serializer\Level;
use Fixture\Serializer\Priority;
use Fixture\Serializer\Status;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Serializer\Casting;
use Tuxxedo\Serializer\Converter\EnumConverter;
use Tuxxedo\Serializer\PropertyContext;
use Tuxxedo\Serializer\SerializerException;

class EnumConverterTest extends TestCase
{
    /**
     * @param class-string<\UnitEnum> $enum
     */
    #[DataProvider('decodeProvider')]
    public function testDecode(
        string $enum,
        Casting $casting,
        mixed $value,
        \UnitEnum $expected,
    ): void {
        self::assertSame($expected, new EnumConverter()->decode($value, self::context($enum, $casting)));
    }

    /**
     * @return \Generator<string, array{class-string<\UnitEnum>, Casting, mixed, \UnitEnum}>
     */
    public static function decodeProvider(): \Generator
    {
        yield 'string backed' => [
            Status::class,
            Casting::STRICT,
            'blocked',
            Status::BLOCKED,
        ];

        yield 'int backed' => [
            Priority::class,
            Casting::STRICT,
            2,
            Priority::HIGH,
        ];

        yield 'int backed lenient string' => [
            Priority::class,
            Casting::LENIENT,
            '1',
            Priority::LOW,
        ];

        yield 'unit by name' => [
            Level::class,
            Casting::STRICT,
            'EXPERT',
            Level::EXPERT,
        ];
    }

    /**
     * @param class-string<\UnitEnum> $enum
     */
    #[DataProvider('invalidProvider')]
    public function testDecodeRejectsInvalidValues(
        string $enum,
        Casting $casting,
        mixed $value,
    ): void {
        try {
            new EnumConverter()->decode($value, self::context($enum, $casting));

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString($enum, $exception->getMessage());
        }
    }

    /**
     * @return \Generator<string, array{class-string<\UnitEnum>, Casting, mixed}>
     */
    public static function invalidProvider(): \Generator
    {
        yield 'int backed strict string' => [
            Priority::class,
            Casting::STRICT,
            '2',
        ];

        yield 'string backed lenient int' => [
            Status::class,
            Casting::LENIENT,
            1,
        ];

        yield 'unknown backing value' => [
            Status::class,
            Casting::STRICT,
            'deleted',
        ];

        yield 'unit unknown name' => [
            Level::class,
            Casting::STRICT,
            'NOVICE',
        ];

        yield 'unit non string' => [
            Level::class,
            Casting::LENIENT,
            1,
        ];
    }

    public function testEncode(): void
    {
        $converter = new EnumConverter();
        $context = self::context(Status::class, Casting::STRICT);

        self::assertSame('active', $converter->encode(Status::ACTIVE, $context));
        self::assertSame(2, $converter->encode(Priority::HIGH, $context));
        self::assertSame('BEGINNER', $converter->encode(Level::BEGINNER, $context));
    }

    public function testEncodeRejectsNonEnums(): void
    {
        try {
            new EnumConverter()->encode('active', self::context(Status::class, Casting::STRICT));

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('enum case', $exception->getMessage());
        }
    }

    /**
     * @param class-string<\UnitEnum> $enum
     */
    private static function context(
        string $enum,
        Casting $casting,
    ): PropertyContext {
        return new PropertyContext(
            className: Customer::class,
            propertyName: 'status',
            path: 'status',
            typeName: $enum,
            nullable: false,
            casting: $casting,
        );
    }
}
