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
use Tuxxedo\Model\Hydrator\Coercer\DateCoercer;
use Tuxxedo\Model\ModelException;

class DateCoercerTest extends TestCase
{
    public function testHydrateWithDefaultFormatProducesImmutableAtMidnight(): void
    {
        $result = (new DateCoercer())->hydrate(value: '2026-07-16');

        self::assertSame(
            '2026-07-16',
            $result->format('Y-m-d'),
        );
    }

    public function testDehydrateProducesFormattedString(): void
    {
        $result = (new DateCoercer())->dehydrate(
            value: new \DateTimeImmutable('2026-07-16'),
        );

        self::assertSame(
            '2026-07-16',
            $result,
        );
    }

    public function testRoundTripDefaultFormat(): void
    {
        $coercer = new DateCoercer();
        $original = '2026-07-16';

        $rehydrated = $coercer->dehydrate(
            value: $coercer->hydrate($original),
        );

        self::assertSame(
            $original,
            $rehydrated,
        );
    }

    public function testExplicitDateFormatEnumIsRespected(): void
    {
        $coercer = new DateCoercer(DateFormat::US);
        $result = $coercer->hydrate(value: '07/16/2026');

        self::assertSame(
            '07/16/2026',
            $coercer->dehydrate(value: $result),
        );
    }

    public function testCustomStringFormatIsRespected(): void
    {
        $coercer = new DateCoercer('d.m.Y');

        $result = $coercer->hydrate('16.07.2026');

        self::assertSame(
            '16.07.2026',
            $coercer->dehydrate(value: $result),
        );
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
            1234567890,
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

        (new DateCoercer())->hydrate(value: $value);
    }

    public function testHydrateUnparseableStringThrows(): void
    {
        $this->expectException(ModelException::class);

        (new DateCoercer())->hydrate(value: 'not-a-date');
    }

    public function testDehydrateNonDateTimeInterfaceThrows(): void
    {
        $this->expectException(ModelException::class);

        (new DateCoercer())->dehydrate(value: 'plain string');
    }

    public function testDehydrateMutableDateTimeIsAlsoAccepted(): void
    {
        $result = (new DateCoercer())->dehydrate(
            value: new \DateTime(datetime: '2026-07-16'),
        );

        self::assertSame(
            '2026-07-16',
            $result,
        );
    }
}
