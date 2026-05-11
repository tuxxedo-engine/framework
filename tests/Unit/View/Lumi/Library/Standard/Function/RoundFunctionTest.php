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
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\View\Lumi\Library\Standard\Function\RoundFunction;

class RoundFunctionTest extends TestCase
{
    /**
     * @return \Generator<array{0: int|float, 1: float}>
     */
    public static function roundDataProvider(): \Generator
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

    /**
     * @param int|float $input
     */
    #[DataProvider('roundDataProvider')]
    public function testCall(
        int|float $input,
        float $expected,
    ): void {
        self::assertSame(
            $expected,
            (new RoundFunction())->call(
                [
                    $input,
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
