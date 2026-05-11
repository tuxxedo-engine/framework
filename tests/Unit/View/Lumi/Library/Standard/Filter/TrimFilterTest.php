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
use Tuxxedo\View\Lumi\Library\Standard\Filter\TrimFilter;

class TrimFilterTest extends TestCase
{
    public function testCallTrimsBothSides(): void
    {
        self::assertSame(
            'hello',
            (new TrimFilter())->call(
                '  hello  ',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallLeavesInnerWhitespaceIntact(): void
    {
        self::assertSame(
            'hello world',
            (new TrimFilter())->call(
                '  hello world  ',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
