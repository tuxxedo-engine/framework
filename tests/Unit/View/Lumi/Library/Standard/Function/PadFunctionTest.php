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
use Tuxxedo\View\Lumi\Library\Standard\Function\PadFunction;

class PadFunctionTest extends TestCase
{
    public function testCallPadsOnBothSides(): void
    {
        self::assertSame(
            '  hi  ',
            (new PadFunction())->call(
                [
                    'hi',
                    6,
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallUsesCustomPadString(): void
    {
        self::assertSame(
            '--hi--',
            (new PadFunction())->call(
                [
                    'hi',
                    6,
                    '-',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
