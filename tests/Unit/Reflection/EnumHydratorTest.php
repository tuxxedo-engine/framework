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

use Fixture\Reflection\IntBackedTestEnum;
use Fixture\Reflection\StringBackedTestEnum;
use Fixture\Reflection\UnitTestEnum;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Reflection\EnumHydrator;

class EnumHydratorTest extends TestCase
{
    public function testHydrateResolvesUnitEnumByExactName(): void
    {
        self::assertSame(
            UnitTestEnum::ALPHA,
            EnumHydrator::hydrate(
                enumClass: UnitTestEnum::class,
                value: 'ALPHA',
            ),
        );
    }

    public function testHydrateReturnsNullForUnknownUnitEnumCase(): void
    {
        self::assertNull(
            EnumHydrator::hydrate(
                enumClass: UnitTestEnum::class,
                value: 'Delta',
            ),
        );
    }

    public function testHydrateIsCaseSensitiveForUnitEnums(): void
    {
        self::assertNull(
            EnumHydrator::hydrate(
                enumClass: UnitTestEnum::class,
                value: 'alpha',
            ),
        );
    }

    public function testHydrateResolvesIntBackedEnumFromNumericString(): void
    {
        self::assertSame(
            IntBackedTestEnum::POSITIVE,
            EnumHydrator::hydrate(
                enumClass: IntBackedTestEnum::class,
                value: '1',
            ),
        );
    }

    public function testHydrateResolvesNegativeIntBackedValue(): void
    {
        self::assertSame(
            IntBackedTestEnum::NEGATIVE,
            EnumHydrator::hydrate(
                enumClass: IntBackedTestEnum::class,
                value: '-1',
            ),
        );
    }

    public function testHydrateReturnsNullForUnknownIntBackedValue(): void
    {
        self::assertNull(
            EnumHydrator::hydrate(
                enumClass: IntBackedTestEnum::class,
                value: '99',
            ),
        );
    }

    public function testHydrateRejectsFloatForIntBackedEnum(): void
    {
        self::assertNull(
            EnumHydrator::hydrate(
                enumClass: IntBackedTestEnum::class,
                value: '1.5',
            ),
        );
    }

    public function testHydrateRejectsNonNumericForIntBackedEnum(): void
    {
        self::assertNull(
            EnumHydrator::hydrate(
                enumClass: IntBackedTestEnum::class,
                value: 'Positive',
            ),
        );
    }

    public function testHydrateResolvesStringBackedEnum(): void
    {
        self::assertSame(
            StringBackedTestEnum::FOO,
            EnumHydrator::hydrate(
                enumClass: StringBackedTestEnum::class,
                value: 'foo',
            ),
        );
    }

    public function testHydrateReturnsNullForUnknownStringBackedValue(): void
    {
        self::assertNull(
            EnumHydrator::hydrate(
                enumClass: StringBackedTestEnum::class,
                value: 'baz',
            ),
        );
    }

    public function testHydrateIsCaseSensitiveForStringBackedEnum(): void
    {
        self::assertNull(
            EnumHydrator::hydrate(
                enumClass: StringBackedTestEnum::class,
                value: 'FOO',
            ),
        );
    }

    public function testHydrateCaseInsensitiveMatchesLowercaseUnitEnum(): void
    {
        self::assertSame(
            UnitTestEnum::ALPHA,
            EnumHydrator::hydrateCaseInsensitive(
                enumClass: UnitTestEnum::class,
                value: 'alpha',
            ),
        );
    }

    public function testHydrateCaseInsensitiveMatchesUppercaseUnitEnum(): void
    {
        self::assertSame(
            UnitTestEnum::BETA,
            EnumHydrator::hydrateCaseInsensitive(
                enumClass: UnitTestEnum::class,
                value: 'BETA',
            ),
        );
    }

    public function testHydrateCaseInsensitiveReturnsNullForUnknownUnitEnum(): void
    {
        self::assertNull(
            EnumHydrator::hydrateCaseInsensitive(
                enumClass: UnitTestEnum::class,
                value: 'delta',
            ),
        );
    }

    public function testHydrateCaseInsensitiveStaysExactForStringBackedEnum(): void
    {
        self::assertNull(
            EnumHydrator::hydrateCaseInsensitive(
                enumClass: StringBackedTestEnum::class,
                value: 'FOO',
            ),
        );
    }

    public function testHydrateCaseInsensitiveResolvesIntBackedEnum(): void
    {
        self::assertSame(
            IntBackedTestEnum::POSITIVE,
            EnumHydrator::hydrateCaseInsensitive(
                enumClass: IntBackedTestEnum::class,
                value: '1',
            ),
        );
    }
}
