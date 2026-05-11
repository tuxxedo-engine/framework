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
use Tuxxedo\View\Lumi\Library\Standard\Function\KSortFunction;

class KSortFunctionTest extends TestCase
{
    public function testCallSortsByKey(): void
    {
        self::assertSame(
            [
                'a' => 2,
                'b' => 3,
                'c' => 1,
            ],
            (new KSortFunction())->call(
                [
                    [
                        'b' => 3,
                        'c' => 1,
                        'a' => 2,
                    ],
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
