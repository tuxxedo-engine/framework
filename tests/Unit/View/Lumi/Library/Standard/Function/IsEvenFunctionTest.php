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
use Tuxxedo\View\Lumi\Library\Standard\Function\IsEvenFunction;

class IsEvenFunctionTest extends TestCase
{
    /**
     * @return \Generator<array{0: int, 1: bool}>
     */
    public static function isEvenDataProvider(): \Generator
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

    #[DataProvider('isEvenDataProvider')]
    public function testCall(
        int $input,
        bool $expected,
    ): void {
        self::assertSame(
            $expected,
            (new IsEvenFunction())->call(
                [
                    $input,
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
