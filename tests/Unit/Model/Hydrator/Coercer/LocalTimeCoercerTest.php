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
use Tuxxedo\Model\Attribute\Column\TimeFormat;
use Tuxxedo\Model\Hydrator\Coercer\LocalTimeCoercer;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Temporal\LocalTime;
use Tuxxedo\Temporal\LocalTimeInterface;

class LocalTimeCoercerTest extends TestCase
{
    public function testHydrateWithExplicitFormatProducesLocalTime(): void
    {
        $result = (new LocalTimeCoercer(format: 'H:i:s'))->hydrate(value: '09:30:15');

        self::assertInstanceOf(LocalTimeInterface::class, $result);
        self::assertSame(9, $result->hour);
        self::assertSame(30, $result->minute);
        self::assertSame(15, $result->second);
    }

    public function testHydrateWithTimeFormatEnumIsRespected(): void
    {
        $coercer = new LocalTimeCoercer(format: TimeFormat::DEFAULT);

        $result = $coercer->hydrate(value: '14:30:15');

        self::assertSame(14, $result->hour);
        self::assertSame(30, $result->minute);
        self::assertSame(15, $result->second);
    }

    public function testHydrateWithTwelveHourFormatEnumIsRespected(): void
    {
        $coercer = new LocalTimeCoercer(format: TimeFormat::TWELVE);

        $result = $coercer->hydrate(value: '02:30:15 PM');

        self::assertSame(14, $result->hour);
        self::assertSame(30, $result->minute);
        self::assertSame(15, $result->second);
    }

    public function testDehydrateProducesFormattedString(): void
    {
        $coercer = new LocalTimeCoercer(format: 'H:i:s');

        $result = $coercer->dehydrate(
            value: LocalTime::of(hour: 14, minute: 30, second: 45),
        );

        self::assertSame('14:30:45', $result);
    }

    public function testRoundTripCustomFormat(): void
    {
        $coercer = new LocalTimeCoercer(format: 'H:i:s');
        $original = '09:30:15';

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
            42,
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

        (new LocalTimeCoercer(format: 'H:i:s'))->hydrate(value: $value);
    }

    public function testHydrateUnparseableStringThrows(): void
    {
        $this->expectException(ModelException::class);

        (new LocalTimeCoercer(format: 'H:i:s'))->hydrate(value: 'not-a-time');
    }

    public function testDehydrateNonLocalTimeThrows(): void
    {
        $this->expectException(ModelException::class);

        (new LocalTimeCoercer(format: 'H:i:s'))->dehydrate(value: 'plain string');
    }
}
