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
use Tuxxedo\View\Lumi\Library\Standard\Function\ReverseFunction;

class ReverseFunctionTest extends TestCase
{
    public function testCallReversesString(): void
    {
        self::assertSame(
            'olleh',
            (new ReverseFunction())->call(
                [
                    'hello',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReversesArray(): void
    {
        self::assertSame(
            [
                3,
                2,
                1,
            ],
            (new ReverseFunction())->call(
                [
                    [
                        1,
                        2,
                        3,
                    ],
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
