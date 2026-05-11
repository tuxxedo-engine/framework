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
use Tuxxedo\View\Lumi\Library\Standard\Function\SplitFunction;

class SplitFunctionTest extends TestCase
{
    public function testCallReturnsDirectiveValue(): void
    {
        self::assertSame(
            [
                'foo',
                'bar',
                'baz',
            ],
            (new SplitFunction())->call(
                [
                    'foo|bar|baz',
                    '|',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
