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
use Tuxxedo\View\Lumi\Library\Standard\Filter\EscapeHtmlFilter;

class EscapeHtmlFilterTest extends TestCase
{
    public function testCallEscapesHtmlTags(): void
    {
        self::assertSame(
            '&lt;b&gt;hello&lt;/b&gt;',
            (new EscapeHtmlFilter())->call(
                '<b>hello</b>',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallLeavesPlainTextUnchanged(): void
    {
        self::assertSame(
            'hello',
            (new EscapeHtmlFilter())->call(
                'hello',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
