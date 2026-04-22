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
use Tuxxedo\Reflection\PropertyReflector;
use Unit\Fixture\Reflection\AnotherSimpleAttribute;
use Unit\Fixture\Reflection\PropertyIntrospector;
use Unit\Fixture\Reflection\SimpleAttribute;

class ReflectionPropertyTest extends TestCase
{
    /**
     * @return \Generator<array{0: string, 1: bool, 2: bool, 3: bool}>
     */
    public static function typeCheckerMatrixDataProvider(): \Generator
    {
        yield [
            'one',
            true,
            false,
            false,
        ];

        yield [
            'two',
            true,
            false,
            true,
        ];

        yield [
            'a',
            false,
            true,
            false,
        ];

        yield [
            'b',
            false,
            true,
            true,
        ];

        yield [
            'c',
            false,
            false,
            false,
        ];

        yield [
            'd',
            false,
            false,
            false,
        ];

        yield [
            'e',
            false,
            false,
            true,
        ];
    }

    #[DataProvider('typeCheckerMatrixDataProvider')]
    public function test(
        string $property,
        bool $expectsBuiltin,
        bool $expectsDefaultType,
        bool $expectsNullable,
    ): void {
        $reflection = new PropertyReflector(
            reflector: (new \ReflectionClass(PropertyIntrospector::class)->getProperty($property)),
        );

        self::assertSame($reflection->getBuiltinType() !== null, $expectsBuiltin);
        self::assertSame($reflection->getDefaultType() !== null, $expectsDefaultType);
        self::assertSame($reflection->isNullable(), $expectsNullable);
    }

    public function testPropertyHasAttribute(): void
    {
        $reflection = new PropertyReflector(
            reflector: (new \ReflectionClass(PropertyIntrospector::class)->getProperty('one')),
        );

        self::assertTrue($reflection->hasAttribute(SimpleAttribute::class));
        self::assertFalse($reflection->hasAttribute(AnotherSimpleAttribute::class));
    }

    public function testPropertyGetAttribute(): void
    {
        $reflection = new PropertyReflector(
            reflector: (new \ReflectionClass(PropertyIntrospector::class)->getProperty('one')),
        );

        self::assertSame($reflection->getAttribute(SimpleAttribute::class)->value, 'zero');
    }

    public function testPropertyGetAttributes(): void
    {
        $reflection = new PropertyReflector(
            reflector: (new \ReflectionClass(PropertyIntrospector::class)->getProperty('two')),
        );

        /** @var array{0: SimpleAttribute, 1: SimpleAttribute} $attributes */
        $attributes = \iterator_to_array($reflection->getAttributes(SimpleAttribute::class));

        self::assertSame($attributes[0]->value, 'one');
        self::assertSame($attributes[1]->value, 'two');
    }
}
