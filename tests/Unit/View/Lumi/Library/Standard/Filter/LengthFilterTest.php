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
use Tuxxedo\View\Lumi\Library\Standard\Filter\LengthFilter;

class LengthFilterTest extends TestCase
{
    public function testCallReturnsStringLength(): void
    {
        self::assertSame(
            5,
            (new LengthFilter())->call(
                'hello',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReturnsArrayCount(): void
    {
        self::assertSame(
            3,
            (new LengthFilter())->call(
                [
                    'a',
                    'b',
                    'c',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReturnsCountableCount(): void
    {
        $countable = new \ArrayObject(
            [
                1,
                2,
                3,
                4,
            ],
        );

        self::assertSame(
            4,
            (new LengthFilter())->call(
                $countable,
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallHandlesMbString(): void
    {
        self::assertSame(
            5,
            (new LengthFilter())->call(
                'héllo',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
