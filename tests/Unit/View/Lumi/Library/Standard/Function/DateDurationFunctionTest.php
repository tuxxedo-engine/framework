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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\View\Lumi\Library\Standard\Function\DateDurationFunction;

class DateDurationFunctionTest extends TestCase
{
    private const int SECONDS_3D_4H = 3 * 86_400 + 4 * 3_600;

    /**
     * @return iterable<string, array{list<mixed>, string}>
     */
    public static function styleMatrix(): iterable
    {
        yield 'default long from duration value' => [
            [
                Duration::fromSeconds(self::SECONDS_3D_4H),
            ],
            '3 days, 4 hours',
        ];

        yield 'short style' => [
            [
                Duration::fromSeconds(self::SECONDS_3D_4H),
                'short',
            ],
            '3d 4h',
        ];

        yield 'narrow style' => [
            [
                Duration::fromSeconds(self::SECONDS_3D_4H),
                'narrow',
            ],
            '3d4h',
        ];

        yield 'unknown style falls back to long' => [
            [
                Duration::fromSeconds(self::SECONDS_3D_4H),
                'gibberish',
            ],
            '3 days, 4 hours',
        ];

        yield 'style is case insensitive' => [
            [
                Duration::fromSeconds(self::SECONDS_3D_4H),
                'SHORT',
            ],
            '3d 4h',
        ];

        yield 'int seconds value' => [
            [
                300,
            ],
            '5 minutes',
        ];

        yield 'iso duration string value' => [
            [
                'PT76H',
            ],
            '3 days, 4 hours',
        ];
    }

    /**
     * @param list<mixed> $arguments
     */
    #[DataProvider('styleMatrix')]
    public function testCall(
        array $arguments,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            (new DateDurationFunction())->call($arguments, static fn () => new StubRuntimeContext()),
        );
    }
}
