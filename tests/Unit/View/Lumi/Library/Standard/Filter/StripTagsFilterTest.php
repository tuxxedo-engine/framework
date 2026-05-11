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
use Tuxxedo\View\Lumi\Library\Standard\Filter\StripTagsFilter;

class StripTagsFilterTest extends TestCase
{
    public function testCallStripsHtmlTags(): void
    {
        self::assertSame(
            'hello world',
            (new StripTagsFilter())->call(
                '<p>hello <strong>world</strong></p>',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallLeavesPlainTextUnchanged(): void
    {
        self::assertSame(
            'hello',
            (new StripTagsFilter())->call(
                'hello',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
