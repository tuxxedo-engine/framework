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
use Tuxxedo\View\Lumi\Library\Standard\Filter\EscapeHtmlCommentFilter;

class EscapeHtmlCommentFilterTest extends TestCase
{
    public function testCallBreaksDoubleHyphens(): void
    {
        self::assertSame(
            'hello- -world',
            (new EscapeHtmlCommentFilter())->call(
                'hello--world',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallAppendsSpaceToTrailingHyphen(): void
    {
        self::assertSame(
            'hello- ',
            (new EscapeHtmlCommentFilter())->call(
                'hello-',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallLeavesPlainTextUnchanged(): void
    {
        self::assertSame(
            'hello',
            (new EscapeHtmlCommentFilter())->call(
                'hello',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
