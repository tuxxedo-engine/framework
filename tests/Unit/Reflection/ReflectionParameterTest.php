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

namespace Unit\Reflection;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Reflection\ParameterReflector;
use Unit\Fixture\Reflection\AnotherSimpleAttribute;
use Unit\Fixture\Reflection\DefaultType;
use Unit\Fixture\Reflection\DefaultTypeInterfaceA;
use Unit\Fixture\Reflection\DefaultTypeInterfaceB;
use Unit\Fixture\Reflection\SimpleAttribute;

class ReflectionParameterTest extends TestCase
{
    /**
     * @return \Generator<array{0: \Closure, 1: bool}>
     */
    public static function defaultTypeDataProvider(): \Generator
    {
        yield [
            static fn (DefaultTypeInterfaceA $a): DefaultTypeInterfaceA => $a,
            true,
        ];

        yield [
            static fn (?DefaultTypeInterfaceA $a): ?DefaultTypeInterfaceA => $a,
            true,
        ];

        yield [
            static fn (DefaultTypeInterfaceA|DefaultTypeInterfaceB $a): DefaultTypeInterfaceA|DefaultTypeInterfaceB => $a,
            false,
        ];

        yield [
            static fn ((DefaultType&DefaultTypeInterfaceB)|DefaultTypeInterfaceA $a): (DefaultType&DefaultTypeInterfaceB)|DefaultTypeInterfaceA => $a,
            false,
        ];

        yield [
            static fn (string $a): string => $a,
            false,
        ];
    }

    #[DataProvider('defaultTypeDataProvider')]
    public function testParameterDefaultType(
        \Closure $object,
        bool $expected,
    ): void {
        $reflection = new ParameterReflector(
            reflector: (new \ReflectionFunction($object)->getParameters()[0]),
        );

        self::assertSame($expected, $reflection->getDefaultType() !== null);
    }

    /**
     * @return \Generator<array{0: \Closure, 1: bool}>
     */
    public static function builtinTypeDataProvider(): \Generator
    {
        yield [
            static fn (string $a): string => $a,
            true,
        ];

        yield [
            static fn (int $a): int => $a,
            true,
        ];

        yield [
            static fn (float $a): float => $a,
            true,
        ];

        yield [
            static fn (null $a): null => $a,
            true,
        ];

        yield [
            static fn (bool $a): bool => $a,
            true,
        ];

        yield [
            static fn (true $a): true => $a,
            true,
        ];

        yield [
            static fn (false $a): false => $a,
            true,
        ];

        yield [
            static fn (array $a): array => $a,
            true,
        ];

        yield [
            static fn (object $a): object => $a,
            true,
        ];

        yield [
            static fn (DefaultTypeInterfaceA $a): DefaultTypeInterfaceA => $a,
            false,
        ];

        yield [
            static fn (?DefaultTypeInterfaceA $a): ?DefaultTypeInterfaceA => $a,
            false,
        ];

        yield [
            static fn (DefaultTypeInterfaceA|DefaultTypeInterfaceB $a): DefaultTypeInterfaceA|DefaultTypeInterfaceB => $a,
            false,
        ];

        yield [
            static fn ((DefaultType&DefaultTypeInterfaceB)|DefaultTypeInterfaceA $a): (DefaultType&DefaultTypeInterfaceB)|DefaultTypeInterfaceA => $a,
            false,
        ];
    }

    #[DataProvider('builtinTypeDataProvider')]
    public function testParameterBuiltinType(
        \Closure $object,
        bool $expected,
    ): void {
        $reflection = new ParameterReflector(
            reflector: (new \ReflectionFunction($object)->getParameters()[0]),
        );

        self::assertSame($expected, $reflection->getBuiltinType() !== null);
    }

    /**
     * @return \Generator<array{0: \Closure, 1: bool}>
     */
    public static function nullabilityDataProvider(): \Generator
    {
        yield [
            static fn (string $a): string => $a,
            false,
        ];

        yield [
            static fn (?string $a): ?string => $a,
            true,
        ];

        yield [
            static fn ((DefaultTypeInterfaceA&DefaultTypeInterfaceB)|DefaultType|null $a): (DefaultTypeInterfaceA&DefaultTypeInterfaceB)|DefaultType|null => $a,
            true,
        ];

        yield [
            static fn (DefaultTypeInterfaceA&DefaultTypeInterfaceB $a): DefaultTypeInterfaceA&DefaultTypeInterfaceB => $a,
            false,
        ];
    }

    #[DataProvider('nullabilityDataProvider')]
    public function testParameterNullability(
        \Closure $object,
        bool $expected,
    ): void {
        $reflection = new ParameterReflector(
            reflector: (new \ReflectionFunction($object)->getParameters()[0]),
        );

        self::assertSame($expected, $reflection->isNullable());
    }

    public function testParameterHasAttribute(): void
    {
        $object = static function (
            #[SimpleAttribute(value: 'one')] string $value,
        ): void {
        };

        $reflection = new ParameterReflector(
            reflector: (new \ReflectionFunction($object)->getParameters()[0]),
        );

        self::assertTrue($reflection->hasAttribute(SimpleAttribute::class));
        self::assertFalse($reflection->hasAttribute(AnotherSimpleAttribute::class));
    }

    public function testParameterGetAttributeAlwaysFirst(): void
    {
        $object = static function (
            #[SimpleAttribute(value: 'one')] #[SimpleAttribute(value: 'two')] string $value,
        ): void {
        };

        $reflection = new ParameterReflector(
            reflector: (new \ReflectionFunction($object)->getParameters()[0]),
        );

        self::assertSame($reflection->getAttribute(SimpleAttribute::class)->value, 'one');
    }

    public function testParameterGetAttributeFailure(): void
    {
        $object = static function (
            #[SimpleAttribute(value: 'one')] string $value,
        ): void {
        };

        $reflection = new ParameterReflector(
            reflector: (new \ReflectionFunction($object)->getParameters()[0]),
        );

        self::expectException(\ReflectionException::class);

        $reflection->getAttribute(AnotherSimpleAttribute::class);
    }

    public function testParameterGetAttributes(): void
    {
        $object = static function (
            #[SimpleAttribute(value: 'one')] #[SimpleAttribute(value: 'two')] string $value,
        ): void {
        };

        $reflection = new ParameterReflector(
            reflector: (new \ReflectionFunction($object)->getParameters()[0]),
        );

        /** @var array{0: SimpleAttribute, 1: SimpleAttribute} $attributes */
        $attributes = \iterator_to_array($reflection->getAttributes(SimpleAttribute::class));

        self::assertSame($attributes[0]->value, 'one');
        self::assertSame($attributes[1]->value, 'two');
    }
}
