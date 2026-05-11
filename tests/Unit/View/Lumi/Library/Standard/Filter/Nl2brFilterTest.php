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
use Tuxxedo\View\Lumi\Library\Standard\Filter\Nl2brFilter;

class Nl2brFilterTest extends TestCase
{
    public function testCallInsertsBreakBeforeNewline(): void
    {
        self::assertSame(
            "hello<br>\nworld",
            (new Nl2brFilter())->call(
                "hello\nworld",
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallLeavesStringWithoutNewlinesUnchanged(): void
    {
        self::assertSame(
            'hello world',
            (new Nl2brFilter())->call(
                'hello world',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
