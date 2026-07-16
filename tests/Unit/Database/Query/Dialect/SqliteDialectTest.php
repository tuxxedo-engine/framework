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

namespace Unit\Database\Query\Dialect;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Database\Query\Dialect\SqliteDialect;

class SqliteDialectTest extends TestCase
{
    /**
     * @return \Generator<array{0: mixed}>
     */
    public static function interpretBooleanTrueDataProvider(): \Generator
    {
        yield [
            '1',
        ];

        yield [
            1,
        ];

        yield [
            true,
        ];
    }

    /**
     * @return \Generator<array{0: mixed}>
     */
    public static function interpretBooleanFalseDataProvider(): \Generator
    {
        yield [
            '0',
        ];

        yield [
            'anything',
        ];

        yield [
            0,
        ];

        yield [
            false,
        ];

        yield [
            null,
        ];
    }

    #[DataProvider('interpretBooleanTrueDataProvider')]
    public function testInterpretBooleanReturnsTrue(
        mixed $value,
    ): void {
        self::assertTrue(
            (new SqliteDialect())->interpretBoolean($value),
        );
    }

    #[DataProvider('interpretBooleanFalseDataProvider')]
    public function testInterpretBooleanReturnsFalse(
        mixed $value,
    ): void {
        self::assertFalse(
            (new SqliteDialect())->interpretBoolean($value),
        );
    }
}
