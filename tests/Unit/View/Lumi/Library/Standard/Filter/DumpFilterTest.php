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
use Tuxxedo\View\Lumi\Library\Standard\Filter\DumpFilter;

class DumpFilterTest extends TestCase
{
    public function testCallDumpsInteger(): void
    {
        self::assertSame(
            'int(42)',
            (new DumpFilter())->call(
                42,
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallDumpsString(): void
    {
        self::assertSame(
            'string(5) "hello"',
            (new DumpFilter())->call(
                'hello',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
