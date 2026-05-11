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

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\View\Lumi\Library\Standard\Function\NumberFunction;

class NumberFunctionTest extends TestCase
{
    public function testCallFormatsWithDefaultSeparators(): void
    {
        self::assertSame(
            '1,234,567.89',
            (new NumberFunction())->call(
                [
                    1234567.89,
                    2,
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallFormatsWithNoDecimals(): void
    {
        self::assertSame(
            '1,234',
            (new NumberFunction())->call(
                [
                    1234,
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallFormatsWithCustomSeparators(): void
    {
        self::assertSame(
            '1.234.567,89',
            (new NumberFunction())->call(
                [
                    1234567.89,
                    2,
                    ',',
                    '.',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallFormatsWithExplicitDecimals(): void
    {
        self::assertSame(
            '42.00',
            (new NumberFunction())->call(
                [
                    42,
                    2,
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
