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
use Tuxxedo\View\Lumi\Library\Standard\Filter\JsonFilter;

class JsonFilterTest extends TestCase
{
    public function testCallEncodesArray(): void
    {
        self::assertSame(
            '{"key":"value"}',
            (new JsonFilter())->call(
                [
                    'key' => 'value',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallEncodesString(): void
    {
        self::assertSame(
            '"hello"',
            (new JsonFilter())->call(
                'hello',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
