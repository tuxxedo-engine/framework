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
use Tuxxedo\View\Lumi\Library\Standard\Function\JsonFunction;

class JsonFunctionTest extends TestCase
{
    public function testCallEncodesString(): void
    {
        self::assertSame(
            '"hello"',
            (new JsonFunction())->call(
                [
                    'hello',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallEncodesArray(): void
    {
        self::assertSame(
            '{"key":"value"}',
            (new JsonFunction())->call(
                [
                    [
                        'key' => 'value',
                    ],
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
