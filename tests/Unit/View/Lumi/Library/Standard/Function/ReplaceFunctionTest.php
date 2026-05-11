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

namespace Unit\View\Lumi\Library\Standard\Function;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\View\Lumi\Library\Standard\Function\ReplaceFunction;

class ReplaceFunctionTest extends TestCase
{
    public function testCallReplacesSubstring(): void
    {
        self::assertSame(
            'hello world',
            (new ReplaceFunction())->call(
                [
                    'hello earth',
                    'earth',
                    'world',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReplacesMultipleOccurrences(): void
    {
        self::assertSame(
            'b b b',
            (new ReplaceFunction())->call(
                [
                    'a a a',
                    'a',
                    'b',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReplacesArrayOfSearchTerms(): void
    {
        self::assertSame(
            'x x',
            (new ReplaceFunction())->call(
                [
                    'a b',
                    [
                        'a',
                        'b',
                    ],
                    'x',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
