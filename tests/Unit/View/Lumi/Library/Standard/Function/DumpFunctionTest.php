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
use Tuxxedo\View\Lumi\Library\Standard\Function\DumpFunction;

class DumpFunctionTest extends TestCase
{
    public function testCallDumpsInteger(): void
    {
        self::assertSame(
            'int(42)',
            (new DumpFunction())->call(
                [
                    42,
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallDumpsString(): void
    {
        self::assertSame(
            'string(5) "hello"',
            (new DumpFunction())->call(
                [
                    'hello',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallDumpsMultipleArguments(): void
    {
        self::assertSame(
            "int(1)\nint(2)",
            (new DumpFunction())->call(
                [
                    1,
                    2,
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
