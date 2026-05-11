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
use Tuxxedo\View\Lumi\Library\Standard\Function\IsOddFunction;

class IsOddFunctionTest extends TestCase
{
    /**
     * @return \Generator<array{0: int, 1: bool}>
     */
    public static function isOddDataProvider(): \Generator
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

    #[DataProvider('isOddDataProvider')]
    public function testCall(
        int $input,
        bool $expected,
    ): void {
        self::assertSame(
            $expected,
            (new IsOddFunction())->call(
                [
                    $input,
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
