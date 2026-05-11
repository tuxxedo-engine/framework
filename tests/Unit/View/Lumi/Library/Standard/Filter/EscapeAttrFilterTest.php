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
use Tuxxedo\View\Lumi\Library\Standard\Filter\EscapeAttrFilter;

class EscapeAttrFilterTest extends TestCase
{
    public function testCallEscapesQuotes(): void
    {
        self::assertSame(
            '&lt;a href=&quot;test&quot;&gt;',
            (new EscapeAttrFilter())->call(
                '<a href="test">',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallEscapesSingleQuotes(): void
    {
        self::assertSame(
            'it&#039;s',
            (new EscapeAttrFilter())->call(
                "it's",
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
