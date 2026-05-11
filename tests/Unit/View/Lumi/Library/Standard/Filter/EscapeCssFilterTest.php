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
use Tuxxedo\View\Lumi\Library\Standard\Filter\EscapeCssFilter;

class EscapeCssFilterTest extends TestCase
{
    public function testCallLeavesAlphanumericUnchanged(): void
    {
        self::assertSame(
            'color',
            (new EscapeCssFilter())->call(
                'color',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallEscapesSpecialCharacters(): void
    {
        self::assertSame(
            'color\3A red',
            (new EscapeCssFilter())->call(
                'color:red',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
