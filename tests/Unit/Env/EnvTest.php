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

namespace Unit\Env;

use Fixture\Env\Profile;
use PHPUnit\Framework\TestCase;
use Support\Env\Source\StubEnvSource;
use Tuxxedo\Env\Env;
use Tuxxedo\Env\EnvException;

class EnvTest extends TestCase
{
    public function testHasReturnsFalseWhenNoSourcesConfigured(): void
    {
        self::assertFalse(
            (new Env())->has(
                key: 'FOO',
            ),
        );
    }

    public function testHasReturnsTrueWhenAnySourceHoldsTheKey(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'FIRST' => 'a',
                ],
            ),
            new StubEnvSource(
                values: [
                    'SECOND' => 'b',
                ],
            ),
        );

        self::assertTrue(
            $env->has(
                key: 'SECOND',
            ),
        );
    }

    public function testHasReturnsFalseWhenNoSourceHoldsTheKey(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'FOO' => 'bar',
                ],
            ),
        );

        self::assertFalse(
            $env->has(
                key: 'MISSING',
            ),
        );
    }

    public function testLookupReturnsFirstSourceHitOrder(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'FOO' => 'first',
                ],
            ),
            new StubEnvSource(
                values: [
                    'FOO' => 'second',
                ],
            ),
        );

        self::assertSame(
            'first',
            $env->string(
                key: 'FOO',
            ),
        );
    }

    public function testStringReturnsStringValue(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'FOO' => 'bar',
                ],
            ),
        );

        self::assertSame(
            'bar',
            $env->string(
                key: 'FOO',
            ),
        );
    }

    public function testStringCoercesIntegerToString(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'PORT' => 8080,
                ],
            ),
        );

        self::assertSame(
            '8080',
            $env->string(
                key: 'PORT',
            ),
        );
    }

    public function testStringCoercesTrueToLowercaseWord(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'FLAG' => true,
                ],
            ),
        );

        self::assertSame(
            'true',
            $env->string(
                key: 'FLAG',
            ),
        );
    }

    public function testStringCoercesFalseToLowercaseWord(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'FLAG' => false,
                ],
            ),
        );

        self::assertSame(
            'false',
            $env->string(
                key: 'FLAG',
            ),
        );
    }

    public function testStringReturnsDefaultWhenKeyMissing(): void
    {
        self::assertSame(
            'fallback',
            (new Env())->string(
                key: 'FOO',
                default: 'fallback',
            ),
        );
    }

    public function testStringThrowsWhenKeyMissingAndNoDefault(): void
    {
        $this->expectException(EnvException::class);

        (new Env())->string(
            key: 'FOO',
        );
    }

    public function testIntReturnsIntegerValueDirectly(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'PORT' => 8080,
                ],
            ),
        );

        self::assertSame(
            8080,
            $env->int(
                key: 'PORT',
            ),
        );
    }

    public function testIntCoercesNumericString(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'PORT' => '3000',
                ],
            ),
        );

        self::assertSame(
            3000,
            $env->int(
                key: 'PORT',
            ),
        );
    }

    public function testIntCoercesNegativeNumericString(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'OFFSET' => '-5',
                ],
            ),
        );

        self::assertSame(
            -5,
            $env->int(
                key: 'OFFSET',
            ),
        );
    }

    public function testIntReturnsDefaultWhenKeyMissing(): void
    {
        self::assertSame(
            42,
            (new Env())->int(
                key: 'FOO',
                default: 42,
            ),
        );
    }

    public function testIntThrowsWhenKeyMissingAndNoDefault(): void
    {
        $this->expectException(EnvException::class);

        (new Env())->int(
            key: 'FOO',
        );
    }

    public function testIntThrowsForNonNumericValue(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'FOO' => 'not-a-number',
                ],
            ),
        );

        $this->expectException(EnvException::class);

        $env->int(
            key: 'FOO',
        );
    }

    public function testIntThrowsForFloatValue(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'FOO' => 1.5,
                ],
            ),
        );

        $this->expectException(EnvException::class);

        $env->int(
            key: 'FOO',
        );
    }

    public function testBoolReturnsBooleanValueDirectly(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'FLAG' => true,
                ],
            ),
        );

        self::assertTrue(
            $env->bool(
                key: 'FLAG',
            ),
        );
    }

    public function testBoolCoercesIntegerOneToTrue(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'FLAG' => 1,
                ],
            ),
        );

        self::assertTrue(
            $env->bool(
                key: 'FLAG',
            ),
        );
    }

    public function testBoolCoercesIntegerZeroToFalse(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'FLAG' => 0,
                ],
            ),
        );

        self::assertFalse(
            $env->bool(
                key: 'FLAG',
            ),
        );
    }

    public function testBoolCoercesTruthyStrings(): void
    {
        foreach (['true', 'TRUE', '1', 'yes', 'on'] as $truthy) {
            $env = new Env(
                new StubEnvSource(
                    values: [
                        'FLAG' => $truthy,
                    ],
                ),
            );

            self::assertTrue(
                $env->bool(
                    key: 'FLAG',
                ),
                $truthy,
            );
        }
    }

    public function testBoolCoercesFalsyStrings(): void
    {
        foreach (['false', 'FALSE', '0', 'no', 'off', ''] as $falsy) {
            $env = new Env(
                new StubEnvSource(
                    values: [
                        'FLAG' => $falsy,
                    ],
                ),
            );

            self::assertFalse(
                $env->bool(
                    key: 'FLAG',
                ),
                $falsy,
            );
        }
    }

    public function testBoolReturnsDefaultWhenKeyMissing(): void
    {
        self::assertTrue(
            (new Env())->bool(
                key: 'FLAG',
                default: true,
            ),
        );
    }

    public function testBoolThrowsWhenKeyMissingAndNoDefault(): void
    {
        $this->expectException(EnvException::class);

        (new Env())->bool(
            key: 'FLAG',
        );
    }

    public function testBoolThrowsForAmbiguousString(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'FLAG' => 'maybe',
                ],
            ),
        );

        $this->expectException(EnvException::class);

        $env->bool(
            key: 'FLAG',
        );
    }

    public function testBoolThrowsForFloatValue(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'FLAG' => 1.5,
                ],
            ),
        );

        $this->expectException(EnvException::class);

        $env->bool(
            key: 'FLAG',
        );
    }

    public function testFloatReturnsFloatValueDirectly(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'RATIO' => 3.14,
                ],
            ),
        );

        self::assertSame(
            3.14,
            $env->float(
                key: 'RATIO',
            ),
        );
    }

    public function testFloatCoercesIntegerToFloat(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'RATIO' => 5,
                ],
            ),
        );

        self::assertSame(
            5.0,
            $env->float(
                key: 'RATIO',
            ),
        );
    }

    public function testFloatCoercesNumericString(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'RATIO' => '2.5',
                ],
            ),
        );

        self::assertSame(
            2.5,
            $env->float(
                key: 'RATIO',
            ),
        );
    }

    public function testFloatCoercesScientificNotation(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'BIG' => '1.5e3',
                ],
            ),
        );

        self::assertSame(
            1500.0,
            $env->float(
                key: 'BIG',
            ),
        );
    }

    public function testFloatReturnsDefaultWhenKeyMissing(): void
    {
        self::assertSame(
            0.5,
            (new Env())->float(
                key: 'RATIO',
                default: 0.5,
            ),
        );
    }

    public function testFloatThrowsWhenKeyMissingAndNoDefault(): void
    {
        $this->expectException(EnvException::class);

        (new Env())->float(
            key: 'RATIO',
        );
    }

    public function testFloatThrowsForNonNumericString(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'RATIO' => 'not-a-number',
                ],
            ),
        );

        $this->expectException(EnvException::class);

        $env->float(
            key: 'RATIO',
        );
    }

    public function testFloatThrowsForBooleanValue(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'RATIO' => true,
                ],
            ),
        );

        $this->expectException(EnvException::class);

        $env->float(
            key: 'RATIO',
        );
    }

    public function testEnumResolvesByCaseName(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'PROFILE' => 'PRODUCTION',
                ],
            ),
        );

        self::assertSame(
            Profile::PRODUCTION,
            $env->enum(
                key: 'PROFILE',
                enum: Profile::class,
            ),
        );
    }

    public function testEnumReturnsDefaultWhenKeyMissing(): void
    {
        self::assertSame(
            Profile::DEVELOPMENT,
            (new Env())->enum(
                key: 'PROFILE',
                enum: Profile::class,
                default: Profile::DEVELOPMENT,
            ),
        );
    }

    public function testEnumThrowsWhenKeyMissingAndNoDefault(): void
    {
        $this->expectException(EnvException::class);

        (new Env())->enum(
            key: 'PROFILE',
            enum: Profile::class,
        );
    }

    public function testEnumThrowsForUnresolvableValue(): void
    {
        $env = new Env(
            new StubEnvSource(
                values: [
                    'PROFILE' => 'STAGING',
                ],
            ),
        );

        $this->expectException(EnvException::class);

        $env->enum(
            key: 'PROFILE',
            enum: Profile::class,
        );
    }
}
