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

namespace Unit\View\Lumi\Library\Standard\Filter;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\View\Lumi\Library\Standard\Filter\CountFilter;

class CountFilterTest extends TestCase
{
    public function testCallCountsElements(): void
    {
        self::assertSame(
            3,
            (new CountFilter())->call(
                [
                    'a',
                    'b',
                    'c',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReturnsZeroForEmptyArray(): void
    {
        self::assertSame(
            0,
            (new CountFilter())->call(
                [],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
