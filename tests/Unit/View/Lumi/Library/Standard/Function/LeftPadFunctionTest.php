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
use Tuxxedo\View\Lumi\Library\Standard\Function\LeftPadFunction;

class LeftPadFunctionTest extends TestCase
{
    public function testCallPadsOnLeft(): void
    {
        self::assertSame(
            '   hi',
            (new LeftPadFunction())->call(
                [
                    'hi',
                    5,
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallUsesCustomPadString(): void
    {
        self::assertSame(
            '***hi',
            (new LeftPadFunction())->call(
                [
                    'hi',
                    5,
                    '*',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
