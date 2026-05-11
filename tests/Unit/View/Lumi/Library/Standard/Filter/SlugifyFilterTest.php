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
use Tuxxedo\View\Lumi\Library\Standard\Filter\SlugifyFilter;

class SlugifyFilterTest extends TestCase
{
    public function testCallSlugifiesSimpleString(): void
    {
        self::assertSame(
            'hello-world',
            (new SlugifyFilter())->call(
                'Hello World',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReplacesSpecialCharacters(): void
    {
        self::assertSame(
            'hello-world',
            (new SlugifyFilter())->call(
                'Hello, World',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallLowercasesResult(): void
    {
        self::assertSame(
            'foo-bar',
            (new SlugifyFilter())->call(
                'FOO BAR',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallPreservesUnicodeLetters(): void
    {
        self::assertSame(
            'héllo',
            (new SlugifyFilter())->call(
                'Héllo',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
