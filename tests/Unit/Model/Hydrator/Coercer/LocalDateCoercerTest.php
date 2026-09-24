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

namespace Unit\Model\Hydrator\Coercer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\Attribute\Column\DateFormat;
use Tuxxedo\Model\Hydrator\Coercer\LocalDateCoercer;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Temporal\LocalDate;
use Tuxxedo\Temporal\LocalDateInterface;

class LocalDateCoercerTest extends TestCase
{
    public function testHydrateWithDefaultFormatProducesLocalDate(): void
    {
        $result = (new LocalDateCoercer())->hydrate(value: '2026-07-16');

        self::assertInstanceOf(LocalDateInterface::class, $result);
        self::assertSame('2026-07-16', $result->toIso8601());
    }

    public function testHydrateWithDateFormatEnumIsRespected(): void
    {
        $coercer = new LocalDateCoercer(format: DateFormat::US);

        $result = $coercer->hydrate(value: '07/16/2026');

        self::assertSame('2026-07-16', $result->toIso8601());
    }

    public function testHydrateWithCustomStringFormatIsRespected(): void
    {
        $coercer = new LocalDateCoercer(format: 'd.m.Y');

        $result = $coercer->hydrate(value: '16.07.2026');

        self::assertSame('2026-07-16', $result->toIso8601());
    }

    public function testDehydrateProducesFormattedString(): void
    {
        $coercer = new LocalDateCoercer();

        $result = $coercer->dehydrate(
            value: LocalDate::of(year: 2026, month: 7, day: 16),
        );

        self::assertSame('2026-07-16', $result);
    }

    public function testRoundTripDefaultFormat(): void
    {
        $coercer = new LocalDateCoercer();
        $original = '2026-07-16';

        $rehydrated = $coercer->dehydrate(
            value: $coercer->hydrate(value: $original),
        );

        self::assertSame($original, $rehydrated);
    }

    /**
     * @return \Generator<array{0: int|float|bool}>
     */
    public static function nonStringHydrateInputDataProvider(): \Generator
    {
        yield [
            0,
        ];

        yield [
            1_234_567_890,
        ];

        yield [
            1.5,
        ];

        yield [
            true,
        ];

        yield [
            false,
        ];
    }

    #[DataProvider('nonStringHydrateInputDataProvider')]
    public function testHydrateNonStringInputThrows(
        int|float|bool $value,
    ): void {
        $this->expectException(ModelException::class);

        (new LocalDateCoercer())->hydrate(value: $value);
    }

    public function testHydrateUnparseableStringThrows(): void
    {
        $this->expectException(ModelException::class);

        (new LocalDateCoercer())->hydrate(value: 'not-a-date');
    }

    public function testDehydrateNonLocalDateThrows(): void
    {
        $this->expectException(ModelException::class);

        (new LocalDateCoercer())->dehydrate(value: 'plain string');
    }
}
