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

namespace Unit\View\Lumi\Library\Standard\Function;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Library\Standard\Function\MathFunctions;

class MathFunctionsTest extends TestCase
{
    private MathFunctions $functions;

    protected function setUp(): void
    {
        $this->functions = new MathFunctions();
    }

    /**
     * @return iterable<array{int, bool}>
     */
    public static function isEvenMatrix(): iterable
    {
        yield [
            0,
            true,
        ];

        yield [
            2,
            true,
        ];

        yield [
            1,
            false,
        ];

        yield [
            3,
            false,
        ];
    }

    #[DataProvider('isEvenMatrix')]
    public function testIsEven(
        int $input,
        bool $expected,
    ): void {
        self::assertSame($expected, $this->functions->isEven($input));
    }

    /**
     * @return iterable<array{int, bool}>
     */
    public static function isOddMatrix(): iterable
    {
        yield [
            1,
            true,
        ];

        yield [
            3,
            true,
        ];

        yield [
            0,
            false,
        ];

        yield [
            2,
            false,
        ];
    }

    #[DataProvider('isOddMatrix')]
    public function testIsOdd(
        int $input,
        bool $expected,
    ): void {
        self::assertSame($expected, $this->functions->isOdd($input));
    }

    /**
     * @return iterable<array{int|float, float}>
     */
    public static function roundMatrix(): iterable
    {
        yield [
            3.4,
            3.0,
        ];

        yield [
            3.5,
            4.0,
        ];

        yield [
            -2.5,
            -3.0,
        ];

        yield [
            42,
            42.0,
        ];
    }

    #[DataProvider('roundMatrix')]
    public function testRound(
        int|float $input,
        float $expected,
    ): void {
        self::assertSame($expected, $this->functions->round($input));
    }
}
