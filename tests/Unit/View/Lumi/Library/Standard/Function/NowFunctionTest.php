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
use Tuxxedo\View\Lumi\Library\Standard\Function\NowFunction;

class NowFunctionTest extends TestCase
{
    public function testCallReturnsCurrentTimestampAsString(): void
    {
        $result = (new NowFunction())->call(
            [],
            static fn () => new StubRuntimeContext(),
        );

        self::assertMatchesRegularExpression('/^\d+$/', $result);
    }
}
