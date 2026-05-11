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
use Tuxxedo\View\Lumi\Library\Standard\Function\SortFunction;

class SortFunctionTest extends TestCase
{
    public function testCallSortsByValuePreservingKeys(): void
    {
        self::assertSame(
            [
                'a' => 1,
                'b' => 2,
                'c' => 3,
            ],
            (new SortFunction())->call(
                [
                    [
                        'c' => 3,
                        'a' => 1,
                        'b' => 2,
                    ],
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
