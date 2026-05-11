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
use Tuxxedo\View\Lumi\Library\Standard\Filter\EscapeJsFilter;

class EscapeJsFilterTest extends TestCase
{
    public function testCallEscapesSingleQuotes(): void
    {
        self::assertSame(
            "it\\'s",
            (new EscapeJsFilter())->call(
                "it's",
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallLeavesStringWithoutQuotesUnchanged(): void
    {
        self::assertSame(
            'hello',
            (new EscapeJsFilter())->call(
                'hello',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
