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
use Tuxxedo\View\Lumi\Library\Standard\Function\DateFunction;

class DateFunctionTest extends TestCase
{
    public function testCallFormatsDateWithExplicitTimestamp(): void
    {
        self::assertSame(
            '1970-01-01',
            (new DateFunction())->call(
                [
                    'Y-m-d',
                    0,
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallUsesCurrentTimeWhenNoTimestampProvided(): void
    {
        $result = (new DateFunction())->call(
            [
                'Y',
            ],
            static fn () => new StubRuntimeContext(),
        );

        self::assertMatchesRegularExpression('/^\d{4}$/', $result);
    }
}
