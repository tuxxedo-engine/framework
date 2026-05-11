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
use Tuxxedo\View\Lumi\Library\Standard\Filter\UpperFilter;

class UpperFilterTest extends TestCase
{
    public function testCallUppercasesString(): void
    {
        self::assertSame(
            'HELLO',
            (new UpperFilter())->call(
                'hello',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallHandlesUnicode(): void
    {
        self::assertSame(
            'HÉLLO',
            (new UpperFilter())->call(
                'héllo',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
