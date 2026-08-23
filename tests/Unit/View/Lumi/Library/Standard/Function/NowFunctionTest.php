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
use Support\Temporal\FixedClock;
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

    public function testCallWithCustomClock(): void
    {
        $clock = new FixedClock(
            now: new \DateTimeImmutable('1989-07-02T14:02:00+01:00'),
        );

        $result = (new NowFunction($clock))->call(
            [],
            static fn () => new StubRuntimeContext(),
        );

        self::assertSame($result, (string) $clock->now()->getTimestamp());
    }
}
