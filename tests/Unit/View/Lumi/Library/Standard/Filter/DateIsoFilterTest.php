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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\LocalDate;
use Tuxxedo\Temporal\LocalTime;
use Tuxxedo\View\Lumi\Library\Standard\Filter\DateIsoFilter;

class DateIsoFilterTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function isoInputs(): iterable
    {
        yield 'InstantInterface' => [
            Instant::parse('2026-07-16T12:00:00Z'),
            '2026-07-16T12:00:00+00:00',
        ];

        yield 'LocalDateInterface' => [
            LocalDate::of(year: 2026, month: 7, day: 16),
            '2026-07-16',
        ];

        yield 'LocalTimeInterface' => [
            LocalTime::of(hour: 9, minute: 30, second: 15),
            '09:30:15',
        ];

        yield 'DateTimeImmutable' => [
            new \DateTimeImmutable('2026-07-16T12:00:00Z'),
            '2026-07-16T12:00:00+00:00',
        ];

        yield 'DateTime mutable' => [
            new \DateTime('2026-07-16T12:00:00Z'),
            '2026-07-16T12:00:00+00:00',
        ];

        yield 'unix timestamp' => [
            1_784_203_200,
            '2026-07-16T12:00:00+00:00',
        ];

        yield 'iso string' => [
            '2026-07-16T12:00:00Z',
            '2026-07-16T12:00:00+00:00',
        ];
    }

    #[DataProvider('isoInputs')]
    public function testCallProducesIsoAcrossInputTypes(
        mixed $value,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            (new DateIsoFilter())->call($value, static fn () => new StubRuntimeContext()),
        );
    }
}
